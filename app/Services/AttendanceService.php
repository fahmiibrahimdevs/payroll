<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\OvertimePeriodStatus;
use App\Enums\OvertimeStatus;
use App\Enums\OvertimeSubmissionSource;
use App\Enums\OvertimeType;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Models\OvertimeSubmission;
use App\Models\User;
use App\Models\WorkLocation;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceService
{
    private const MAX_STORED_GPS_ACCURACY_METERS = 4_294_967_295;

    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Validasi rentang tanggal import fingerprint: maksimal 1 bulan.
     *
     * Pemetaan nomor hari pada format Kartu Laporan hanya aman untuk rentang
     * dalam 1 bulan; rentang yang lebih panjang berisiko menimpa data bulan
     * lain / kehilangan bulan tengah, sehingga import ditolak.
     *
     * @throws InvalidArgumentException bila rentang melebihi 1 bulan
     */
    public function validateImportRange(Carbon $startDate, Carbon $endDate): void
    {
        // addMonthNoOverflow() aman dari overflow (mis. 31 Jan + 1 bulan = 28 Feb)
        $maxEndDate = $startDate->copy()->startOfDay()->addMonthNoOverflow();

        if ($endDate->copy()->startOfDay()->gt($maxEndDate)) {
            $months = [];
            $curr = $startDate->copy()->startOfMonth();
            $lastMonth = $endDate->copy()->startOfMonth();
            while ($curr->lte($lastMonth)) {
                $months[] = $curr->locale('id')->isoFormat('MMMM Y');
                $curr->addMonthNoOverflow();
            }

            $monthList = count($months) > 3
                ? implode(', ', array_slice($months, 0, 3)).', ... ('.count($months).' bulan)'
                : implode(', ', $months);

            throw new InvalidArgumentException(
                'Rentang tanggal import maksimal 1 bulan. Silakan impor per bulan. Bulan terdeteksi: '.$monthList.'.'
            );
        }
    }

    /**
     * Import attendance records from a standard fingerprint Excel report (.xls / .xlsx).
     *
     * @param  string|UploadedFile  $file
     */
    public function importFingerprintReport(mixed $file, ?User $actor = null): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $spreadsheet = IOFactory::load($filePath);

        // Deteksi apakah berkas berisi lembar kerja "Kartu Laporan"
        $cardSheets = [];
        foreach ($spreadsheet->getAllSheets() as $s) {
            $title = strtolower(trim($s->getTitle()));
            if (in_array($title, ['jadwal info', 'stat. absen', 'lap. log absen', 'exception stat.'])) {
                continue;
            }
            $a1 = trim((string) $s->getCell('A1')->getValue());
            $a2 = trim((string) $s->getCell('A2')->getValue());
            if (str_contains($a1, 'Kartu Laporan') || str_contains($a2, 'Kartu Laporan') || str_contains($a2, 'Tgl Absen') || preg_match('/^\d+(\.\d+)*$/', $s->getTitle())) {
                $cardSheets[] = $s;
            }
        }

        // Cache master karyawan berdasarkan fingerprint_id (PIN mesin)
        $employeesByFid = Employee::whereNotNull('fingerprint_id')
            ->where('fingerprint_id', '!=', '')
            ->get()
            ->keyBy(fn ($e) => (string) $e->fingerprint_id);

        $timeFormatter = function (mixed $val): ?string {
            if ($val === null || $val === '' || strtolower(trim((string) $val)) === 'absen') {
                return null;
            }

            $raw = trim((string) $val);

            // Fallback waktu serial Excel (numerik): 0..1 adalah fraksi hari
            // (0.354166 = 08:30), >1 adalah serial tanggal + fraksi jam
            // (45000.5 = 12:00). Konversi fraksi hari menjadi jam. Perilaku
            // string "HH:MM" existing tidak berubah.
            if (preg_match('/^\d+(?:\.\d+)?$/', $raw)) {
                $fraction = fmod((float) $raw, 1.0);
                if ($fraction <= 0.0) {
                    return null;
                }

                $totalSeconds = (int) round($fraction * 86400);
                if ($totalSeconds >= 86400) {
                    $totalSeconds = 86399;
                }

                return sprintf(
                    '%02d:%02d:%02d',
                    intdiv($totalSeconds, 3600),
                    intdiv($totalSeconds % 3600, 60),
                    $totalSeconds % 60
                );
            }

            preg_match('/\d{1,2}:\d{2}/', $raw, $m);
            if (empty($m[0])) {
                return null;
            }
            $t = $m[0];
            if (strlen($t) === 4) {
                $t = '0'.$t;
            }

            return $t.':00';
        };

        // =========================================================================
        // JALUR 1: PARSING DARI LEMBAR KERJA "KARTU LAPORAN" (DINAMIS 1 S/D N ID)
        // =========================================================================
        if (! empty($cardSheets)) {
            // 1. Deteksi rentang tanggal dari lembar pertama
            $firstSheet = $cardSheets[0];
            $rangeText = (string) $firstSheet->getCell('D2')->getValue()
                ?: (string) $firstSheet->getCell('B2')->getValue()
                ?: (string) $firstSheet->getCell('B4')->getValue()
                ?: (string) $firstSheet->getCell('Q4')->getValue();

            if (! preg_match('/(\d{4}[-\/]\d{2}[-\/]\d{2})\s*~\s*(\d{4}[-\/]\d{2}[-\/]\d{2})/', $rangeText, $rangeMatch)) {
                // Pindai beberapa sel baris 2-4 untuk mencari rentang tanggal YYYY-MM-DD ~ YYYY-MM-DD
                for ($r = 2; $r <= 4; $r++) {
                    for ($c = 1; $c <= 20; $c++) {
                        $chkVal = (string) $firstSheet->getCell(Coordinate::stringFromColumnIndex($c).$r)->getValue();
                        if (preg_match('/(\d{4}[-\/]\d{2}[-\/]\d{2})\s*~\s*(\d{4}[-\/]\d{2}[-\/]\d{2})/', $chkVal, $rangeMatch)) {
                            break 2;
                        }
                    }
                }
            }

            if (empty($rangeMatch)) {
                throw new InvalidArgumentException('Format rentang tanggal absensi pada berkas Kartu Laporan tidak terbaca.');
            }

            $startDate = Carbon::parse(trim($rangeMatch[1]));
            $endDate = Carbon::parse(trim($rangeMatch[2]));

            // Guard: rentang import lebih dari 1 bulan akan merusak pemetaan
            // nomor hari (menimpa bulan lain / kehilangan bulan tengah)
            $this->validateImportRange($startDate, $endDate);

            // 2. Deteksi dinamis blok karyawan di setiap sheet Kartu Laporan
            $detectedBlocks = [];
            foreach ($cardSheets as $sheet) {
                $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
                for ($c = 1; $c <= $highestColIndex; $c++) {
                    $colStr = Coordinate::stringFromColumnIndex($c);
                    $val4 = trim((string) $sheet->getCell($colStr.'4')->getValue());
                    if ($val4 === 'ID') {
                        $nextColStr = Coordinate::stringFromColumnIndex($c + 1);
                        $fId = trim((string) $sheet->getCell($nextColStr.'4')->getValue());
                        $rawName = trim((string) $sheet->getCell($nextColStr.'3')->getValue());

                        if ($fId !== '' && $rawName !== '') {
                            $baseColIdx = max(1, $c - 8);
                            $deptColStr = Coordinate::stringFromColumnIndex($baseColIdx + 1);
                            $dept = trim((string) $sheet->getCell($deptColStr.'3')->getValue());

                            $detectedBlocks[] = [
                                'sheet' => $sheet,
                                'fingerprint_id' => $fId,
                                'raw_name' => $rawName,
                                'department' => $dept,
                                'base_col_idx' => $baseColIdx,
                            ];
                        }
                    }
                }
            }

            $totalParsed = count($detectedBlocks);
            $matchedRows = [];
            $unregisteredEmployees = [];

            // 3. Pre-validation Pass: Pindai seluruh PIN terlebih dahulu
            foreach ($detectedBlocks as $block) {
                $cleanName = trim(preg_replace('/^(DESTROY|ADMIN|USER|DEV)~+/i', '', $block['raw_name']));
                $employee = ! empty($block['fingerprint_id']) ? $employeesByFid->get($block['fingerprint_id']) : null;

                if (! $employee) {
                    $unregisteredEmployees[] = [
                        'fingerprint_id' => $block['fingerprint_id'] ?: '-',
                        'name' => $cleanName ?: $block['raw_name'],
                        'raw_name' => $block['raw_name'],
                        'department' => $block['department'] ?: '-',
                    ];
                } else {
                    $matchedRows[] = [
                        'block' => $block,
                        'employee' => $employee,
                    ];
                }
            }

            if (! empty($unregisteredEmployees)) {
                Log::warning('Import absensi dibatalkan: Ditemukan ID Fingerprint yang belum terdaftar di Data Karyawan', [
                    'unregistered_count' => count($unregisteredEmployees),
                    'unregistered_list' => $unregisteredEmployees,
                    'actor_id' => $actor?->id,
                ]);

                $this->logger->warning(
                    action: 'ATTENDANCE_IMPORT_REJECTED',
                    module: 'attendances',
                    message: 'Import absensi dibatalkan karena ditemukan PIN fingerprint yang belum terdaftar',
                    context: [
                        'unregistered_count' => count($unregisteredEmployees),
                        'unregistered_sample' => array_slice($unregisteredEmployees, 0, 5),
                        'actor_id' => $actor?->id,
                    ],
                    userId: $actor?->id
                );

                return [
                    'status' => 'unregistered_fingerprint',
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_parsed' => $totalParsed,
                    'matched_count' => count($matchedRows),
                    'unmatched_count' => count($unregisteredEmployees),
                    'unregistered_list' => $unregisteredEmployees,
                    'records_saved' => 0,
                ];
            }

            // 4. Database Insertion Pass (Optimized Batch Pre-fetch & Bulk Upsert)
            $recordsSaved = 0;
            $manualPreserved = 0;
            $matched = [];

            DB::transaction(function () use (
                $matchedRows,
                $startDate,
                $endDate,
                $timeFormatter,
                &$recordsSaved,
                &$manualPreserved,
                &$matched
            ) {
                $matchedEmployeeIds = collect($matchedRows)->pluck('employee.id')->unique()->all();

                // Pre-fetch seluruh catatan presensi yang sudah ada dalam rentang tanggal untuk mencegah N+1 queries
                $existingAttendances = Attendance::whereIn('employee_id', $matchedEmployeeIds)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get()
                    ->keyBy(fn ($a) => $a->employee_id.'_'.$a->date->toDateString());

                $company = CompanyProfile::getProfile();
                $isRoster = $company->isRosterMode();
                $schedulesMap = $isRoster
                    ? EmployeeSchedule::schedulesForEmployees($matchedEmployeeIds, $startDate->toDateString(), $endDate->toDateString())
                    : collect();

                $upsertRows = [];
                $now = now()->toDateTimeString();

                foreach ($matchedRows as $item) {
                    $employee = $item['employee'];
                    $block = $item['block'];
                    $sheet = $block['sheet'];
                    $baseCol = $block['base_col_idx'];
                    $isHarian = $employee->employment_status === EmploymentStatus::HARIAN;

                    $matched[$employee->id] = $employee->full_name;

                    for ($r = 12; $r <= 45; $r++) {
                        $dateColStr = Coordinate::stringFromColumnIndex($baseCol);
                        $tglStr = trim((string) $sheet->getCell($dateColStr.$r)->getValue());
                        if (empty($tglStr) || ! preg_match('/^(\d{1,2})/', $tglStr, $m)) {
                            continue;
                        }

                        $dayNum = (int) $m[1];
                        if ($dayNum < 1 || $dayNum > 31) {
                            continue;
                        }

                        $dateObj = $startDate->copy();
                        if ($dayNum < $startDate->day && $endDate->gt($startDate) && $endDate->month !== $startDate->month) {
                            $dateObj = $endDate->copy();
                        }

                        // Hari tidak ada pada bulan kandidat (mis. 31 di April/Februari) - coba bulan lainnya dalam rentang
                        if ($dayNum > $dateObj->daysInMonth) {
                            $alt = $dateObj->isSameMonth($startDate) ? $endDate->copy() : $startDate->copy();
                            if ($dayNum > $alt->daysInMonth || $alt->lt($startDate) || $alt->gt($endDate)) {
                                continue;
                            }
                            $dateObj = $alt;
                        }
                        $dateObj->day($dayNum);
                        $dateString = $dateObj->toDateString();

                        // Proteksi strictly: abaikan tanggal di luar rentang $startDate s.d. $endDate
                        if ($dateObj->lt($startDate) || $dateObj->gt($endDate)) {
                            continue;
                        }

                        $regInRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 1).$r)->getValue());
                        $regOutRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 3).$r)->getValue());
                        $lemburInRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 10).$r)->getValue());
                        $lemburOutRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 12).$r)->getValue());

                        $clockIn = $timeFormatter($regInRaw);
                        $clockOut = $timeFormatter($regOutRaw);
                        $overtimeIn = $timeFormatter($lemburInRaw);
                        $overtimeOut = $timeFormatter($lemburOutRaw);

                        $empSched = $isRoster ? $schedulesMap->get($employee->id)?->get($dateString) : null;
                        $isScheduledOff = $empSched ? $empSched->isOff() : $dateObj->isSunday();

                        // Proteksi Hari Minggu untuk Karyawan Tetap & Kontrak (Hanya pada mode Fixed standar)
                        if (! $isRoster && $dateObj->isSunday() && ! $isHarian) {
                            $clockIn = null;
                            $clockOut = null;
                        }

                        // Jika tidak ada tap reguler dan tidak ada tap lembur:
                        if ($clockIn === null && $overtimeIn === null) {
                            if (strtolower($regInRaw) === 'absen' || $isScheduledOff) {
                                $status = $isScheduledOff ? 'libur' : 'absen';
                            } else {
                                continue;
                            }
                        } elseif ($clockIn !== null) {
                            $status = 'hadir';
                        } else {
                            $status = $isScheduledOff ? 'libur' : 'absen';
                        }

                        // Durasi jam kerja reguler jika clock_in dan clock_out ada
                        $durationMins = 0;
                        if ($clockIn && $clockOut) {
                            $inSec = Carbon::parse($clockIn)->secondsSinceMidnight();
                            $outSec = Carbon::parse($clockOut)->secondsSinceMidnight();
                            if ($outSec < $inSec) {
                                // Melintasi tengah malam (misal 22:00 - 06:00)
                                $durationMins = (int) round((($outSec + 86400) - $inSec) / 60);
                            } else {
                                $durationMins = (int) round(($outSec - $inSec) / 60);
                            }
                        }

                        // Durasi jam lembur mesin jika overtime_in dan overtime_out ada
                        $overtimeMins = 0;
                        if ($overtimeIn && $overtimeOut) {
                            $otInSec = Carbon::parse($overtimeIn)->secondsSinceMidnight();
                            $otOutSec = Carbon::parse($overtimeOut)->secondsSinceMidnight();
                            if ($otOutSec < $otInSec) {
                                // Lembur melintasi tengah malam (misal 22:00 - 02:00)
                                $overtimeMins = (int) round((($otOutSec + 86400) - $otInSec) / 60);
                            } else {
                                $overtimeMins = (int) round(($otOutSec - $otInSec) / 60);
                            }
                        }

                        $rawLog = trim("Reg: {$regInRaw}".($regOutRaw ? " {$regOutRaw}" : '').($lemburInRaw ? " | OT: {$lemburInRaw}" : '').($lemburOutRaw ? " {$lemburOutRaw}" : ''));

                        // Cek perlindungan koreksi manual HR/Admin via in-memory keyBy (O(1))
                        $key = $employee->id.'_'.$dateString;
                        $existing = $existingAttendances->get($key);

                        if ($existing && $existing->source === 'manual') {
                            $existing->update([
                                'raw_log' => $rawLog,
                                'overtime_in' => $overtimeIn ?: $existing->overtime_in,
                                'overtime_out' => $overtimeOut ?: $existing->overtime_out,
                                'overtime_minutes' => $overtimeMins ?: $existing->overtime_minutes,
                            ]);
                            $manualPreserved++;
                        } else {
                            $upsertRows[] = [
                                'employee_id' => $employee->id,
                                'date' => $dateString,
                                'clock_in' => $clockIn,
                                'clock_out' => $clockOut,
                                'overtime_in' => $overtimeIn,
                                'overtime_out' => $overtimeOut,
                                'status' => $status,
                                'duration_minutes' => $durationMins,
                                'overtime_minutes' => $overtimeMins,
                                'late_minutes' => 0,
                                'early_leave_minutes' => 0,
                                'source' => 'fingerprint',
                                'raw_log' => $rawLog,
                                'notes' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                }

                // Batch upsert atomic dalam chunk 500 untuk efisiensi memori & eksekusi cepat
                if (! empty($upsertRows)) {
                    foreach (array_chunk($upsertRows, 500) as $chunk) {
                        Attendance::upsert(
                            $chunk,
                            ['employee_id', 'date'],
                            [
                                'clock_in',
                                'clock_out',
                                'overtime_in',
                                'overtime_out',
                                'status',
                                'duration_minutes',
                                'overtime_minutes',
                                'late_minutes',
                                'early_leave_minutes',
                                'source',
                                'raw_log',
                                'updated_at',
                            ]
                        );
                    }
                    $recordsSaved += count($upsertRows);
                }
            });

            $refreshedWeeklyPeriods = $this->refreshDraftWeeklyPeriodsForRange(
                $startDate->toDateString(),
                $endDate->toDateString(),
                $actor
            );

            Log::info('Laporan absensi fingerprint (Kartu Laporan) berhasil diimpor', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_parsed' => $totalParsed,
                'matched_count' => count($matched),
                'records_saved' => $recordsSaved,
                'manual_preserved' => $manualPreserved,
                'actor_id' => $actor?->id,
            ]);

            return [
                'status' => 'success',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_parsed' => $totalParsed,
                'matched_count' => count($matched),
                'unmatched_count' => 0,
                'records_saved' => $recordsSaved,
                'manual_preserved' => $manualPreserved,
                'weekly_periods_refreshed' => $refreshedWeeklyPeriods,
                'matched_names' => array_values($matched),
            ];
        }

        // =========================================================================
        // JALUR 2: FALLBACK UNTUK FORMAT "LAP. LOG ABSEN" (KOMPATIBILITAS FILE LAMA)
        // =========================================================================
        $sheetLog = $spreadsheet->getSheetByName('Lap. Log Absen');
        if (! $sheetLog) {
            foreach ($spreadsheet->getAllSheets() as $s) {
                $name = strtolower($s->getTitle());
                if (str_contains($name, 'log') || str_contains($name, 'absen')) {
                    $sheetLog = $s;
                    break;
                }
            }
        }
        if (! $sheetLog) {
            $sheetLog = $spreadsheet->getSheet(0);
        }

        if (! $sheetLog) {
            throw new InvalidArgumentException('Format berkas tidak valid: Lembar kerja laporan absensi tidak ditemukan.');
        }

        // 1. Detect date range
        $rangeText = $sheetLog->getCell('C3')->getValue() ?: $sheetLog->getCell('A3')->getValue();
        if (! $rangeText || ! str_contains((string) $rangeText, '~')) {
            for ($c = 1; $c <= 10; $c++) {
                $checkCol = Coordinate::stringFromColumnIndex($c);
                $val = (string) $sheetLog->getCell($checkCol.'3')->getValue();
                if (str_contains($val, '~')) {
                    $rangeText = $val;
                    break;
                }
            }
        }

        if (! $rangeText || ! str_contains((string) $rangeText, '~')) {
            throw new InvalidArgumentException('Format rentang tanggal absensi pada berkas tidak terbaca.');
        }

        [$startStr, $endStr] = explode('~', (string) $rangeText);
        $startDate = Carbon::parse(trim($startStr));
        $endDate = Carbon::parse(trim($endStr));

        // Guard: rentang import maksimal 1 bulan (sama seperti jalur Kartu Laporan)
        $this->validateImportRange($startDate, $endDate);

        // 2. Map header columns to dates (supports full month spanning up to column AE / 31 days)
        $dateCols = [];
        $cur = $startDate->copy();
        $highestColIndex = Coordinate::columnIndexFromString($sheetLog->getHighestColumn());
        for ($colIdx = 1; $colIdx <= $highestColIndex; $colIdx++) {
            if ($cur->gt($endDate)) {
                break;
            }
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $dayNum = (int) $sheetLog->getCell($col.'4')->getValue();
            if ($dayNum === $cur->day) {
                $dateCols[$col] = $cur->toDateString();
                $cur->addDay();
            }
        }

        $highestRow = $sheetLog->getHighestRow();
        $totalParsed = 0;
        $matchedRows = [];
        $unregisteredEmployees = [];

        // 1. Pre-validation Pass: Pindai seluruh baris karyawan terlebih dahulu
        for ($r = 5; $r <= $highestRow; $r += 2) {
            $idLabel = trim((string) $sheetLog->getCell('A'.$r)->getValue());
            if ($idLabel !== 'ID:') {
                continue;
            }

            $totalParsed++;
            $fId = trim((string) $sheetLog->getCell('C'.$r)->getValue());
            $rawName = trim((string) $sheetLog->getCell('K'.$r)->getValue());
            $dept = trim((string) $sheetLog->getCell('U'.$r)->getValue());

            // Bersihkan prefix nama bawaan mesin fingerprint jika ada (misal: DESTROY~, ADMIN~)
            $cleanName = trim(preg_replace('/^(DESTROY|ADMIN|USER|DEV)~+/i', '', $rawName));

            // Strict Match: 100% wajib cocok dengan fingerprint_id (PIN mesin)
            $employee = ! empty($fId) ? $employeesByFid->get($fId) : null;

            if (! $employee) {
                $unregisteredEmployees[] = [
                    'fingerprint_id' => $fId ?: '-',
                    'name' => $cleanName ?: $rawName,
                    'raw_name' => $rawName,
                    'department' => $dept ?: '-',
                ];
            } else {
                $matchedRows[] = [
                    'row' => $r,
                    'employee' => $employee,
                ];
            }
        }

        // JIKA DITEMUKAN PIN YANG BELUM TERDAFTAR:
        if (! empty($unregisteredEmployees)) {
            Log::warning('Import absensi dibatalkan: Ditemukan ID Fingerprint yang belum terdaftar di Data Karyawan', [
                'unregistered_count' => count($unregisteredEmployees),
                'unregistered_list' => $unregisteredEmployees,
                'actor_id' => $actor?->id,
            ]);

            $this->logger->warning(
                action: 'ATTENDANCE_IMPORT_REJECTED',
                module: 'attendances',
                message: 'Import absensi dibatalkan karena ditemukan PIN fingerprint yang belum terdaftar',
                context: [
                    'unregistered_count' => count($unregisteredEmployees),
                    'unregistered_sample' => array_slice($unregisteredEmployees, 0, 5),
                    'actor_id' => $actor?->id,
                ],
                userId: $actor?->id
            );

            return [
                'status' => 'unregistered_fingerprint',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_parsed' => $totalParsed,
                'matched_count' => count($matchedRows),
                'unmatched_count' => count($unregisteredEmployees),
                'unregistered_list' => $unregisteredEmployees,
                'records_saved' => 0,
            ];
        }

        // 2. Database Insertion Pass
        $recordsSaved = 0;
        $manualPreserved = 0;
        $matched = [];

        DB::transaction(function () use (
            $sheetLog,
            $matchedRows,
            $dateCols,
            $startDate,
            $endDate,
            &$matched,
            &$recordsSaved,
            &$manualPreserved
        ) {
            $matchedEmployeeIds = collect($matchedRows)->pluck('employee.id')->unique()->all();

            $existingAttendances = Attendance::whereIn('employee_id', $matchedEmployeeIds)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->keyBy(fn ($a) => $a->employee_id.'_'.$a->date->toDateString());

            $company = CompanyProfile::getProfile();
            $isRoster = $company->isRosterMode();
            $schedulesMap = $isRoster
                ? EmployeeSchedule::schedulesForEmployees($matchedEmployeeIds, $startDate->toDateString(), $endDate->toDateString())
                : collect();

            $upsertRows = [];
            $now = now()->toDateTimeString();

            foreach ($matchedRows as $item) {
                $employee = $item['employee'];
                $r = $item['row'];
                $dataRow = $r + 1;

                $matched[$employee->id] = $employee->full_name;

                foreach ($dateCols as $col => $dateString) {
                    $dateObj = Carbon::parse($dateString);
                    $isSunday = $dateObj->isSunday();
                    $isHarian = $employee->employment_status === EmploymentStatus::HARIAN;

                    $empSched = $isRoster ? $schedulesMap->get($employee->id)?->get($dateString) : null;
                    $isScheduledOff = $empSched ? $empSched->isOff() : ($isSunday && ! $isHarian);

                    if (! $isRoster && $isSunday && ! $isHarian) {
                        continue;
                    }

                    $val = trim((string) $sheetLog->getCell($col.$dataRow)->getValue());

                    if (empty($val) || strtolower($val) === 'absen') {
                        continue;
                    }

                    preg_match_all('/\d{1,2}:\d{2}/', $val, $timeMatches);
                    $times = $timeMatches[0] ?? [];

                    if (empty($times)) {
                        continue;
                    }

                    $clockIn = strlen($times[0]) === 4 ? '0'.$times[0] : $times[0];
                    $latestTime = end($times);
                    if (strlen($latestTime) === 4) {
                        $latestTime = '0'.$latestTime;
                    }

                    $clockOut = ($latestTime !== $clockIn) ? $latestTime : null;

                    $durationMins = 0;
                    $status = 'hadir';

                    if ($clockIn && $clockOut) {
                        [$inH, $inM] = explode(':', $clockIn);
                        [$outH, $outM] = explode(':', $clockOut);
                        $inMinutes = ((int) $inH * 60) + (int) $inM;
                        $outMinutes = ((int) $outH * 60) + (int) $outM;

                        if ($outMinutes < $inMinutes) {
                            // Melintasi tengah malam (misal 22:00 - 06:00)
                            $durationMins = ($outMinutes + 1440) - $inMinutes;
                        } else {
                            $durationMins = $outMinutes - $inMinutes;
                        }
                    }

                    $key = $employee->id.'_'.$dateString;
                    $existing = $existingAttendances->get($key);

                    if ($existing && $existing->source === 'manual') {
                        $existing->update(['raw_log' => $val]);
                        $manualPreserved++;
                    } else {
                        $upsertRows[] = [
                            'employee_id' => $employee->id,
                            'date' => $dateString,
                            'clock_in' => $clockIn,
                            'clock_out' => $clockOut,
                            'status' => $status,
                            'duration_minutes' => $durationMins,
                            'late_minutes' => 0,
                            'early_leave_minutes' => 0,
                            'overtime_minutes' => 0,
                            'source' => 'fingerprint',
                            'raw_log' => $val,
                            'notes' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }

            if (! empty($upsertRows)) {
                foreach (array_chunk($upsertRows, 500) as $chunk) {
                    Attendance::upsert(
                        $chunk,
                        ['employee_id', 'date'],
                        [
                            'clock_in',
                            'clock_out',
                            'status',
                            'duration_minutes',
                            'late_minutes',
                            'early_leave_minutes',
                            'source',
                            'raw_log',
                            'updated_at',
                        ]
                    );
                }
                $recordsSaved += count($upsertRows);
            }
        });

        $refreshedWeeklyPeriods = $this->refreshDraftWeeklyPeriodsForRange(
            $startDate->toDateString(),
            $endDate->toDateString(),
            $actor
        );

        Log::info('Laporan absensi fingerprint berhasil diimpor', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_parsed' => $totalParsed,
            'matched_count' => count($matched),
            'unmatched_count' => 0,
            'records_saved' => $recordsSaved,
            'manual_preserved' => $manualPreserved,
            'actor_id' => $actor?->id,
        ]);

        $this->logger->info(
            action: 'ATTENDANCE_FINGERPRINT_IMPORTED',
            module: 'attendances',
            message: 'Laporan absensi fingerprint berhasil diimpor',
            context: [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_parsed' => $totalParsed,
                'matched_count' => count($matched),
                'records_saved' => $recordsSaved,
                'manual_preserved' => $manualPreserved,
                'actor_id' => $actor?->id,
            ],
            userId: $actor?->id
        );

        return [
            'status' => 'success',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_parsed' => $totalParsed,
            'matched_count' => count($matched),
            'unmatched_count' => 0,
            'unregistered_list' => [],
            'records_saved' => $recordsSaved,
            'manual_preserved' => $manualPreserved,
            'weekly_periods_refreshed' => $refreshedWeeklyPeriods,
        ];
    }

    /**
     * Build attendance listing query with optional filters.
     */
    public function getAttendancesQuery(array $filters = []): Builder
    {
        $query = Attendance::query()->with(['employee.department', 'employee.position', 'employee.detail']);

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('date', [$filters['start_date'], $filters['end_date']]);
        } elseif (! empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        if (! empty($filters['employment_status'])) {
            $query->whereHas('employee', function (Builder $q) use ($filters) {
                $q->where('employment_status', $filters['employment_status']);
            });
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', function (Builder $q) use ($filters) {
                $q->where('department_id', (int) $filters['department_id']);
            });
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('date', 'desc')->orderBy('clock_in', 'asc');
    }

    /** Refresh draft weekly periods affected by new or changed attendance data. */
    public function refreshDraftWeeklyPeriodsForRange(string $startDate, string $endDate, ?User $actor = null): int
    {
        $periods = OvertimePeriod::query()
            ->where('status', OvertimePeriodStatus::DRAFT)
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();

        $refreshedCount = 0;
        foreach ($periods as $period) {
            OvertimePeriod::query()
                ->whereKey($period->id)
                ->update(['attendance_synced_at' => null]);

            try {
                $this->syncToWeeklyOvertimePeriod($period, $actor);
                $refreshedCount++;
            } catch (\Throwable $exception) {
                Log::warning('Slip mingguan belum dapat diperbarui otomatis dari perubahan absensi', [
                    'period_id' => $period->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'error' => $exception->getMessage(),
                    'actor_id' => $actor?->id,
                ]);
            }
        }

        return $refreshedCount;
    }

    /**
     * Synchronize recorded attendances directly into a weekly OvertimePeriodAttendance.
     */
    public function syncToWeeklyOvertimePeriod(OvertimePeriod $period, ?User $actor = null): int
    {
        $period->refresh();

        // Periode yang sudah lunas tidak boleh berubah — sinkronisasi ulang akan
        // membalikkan grand_total dan menimpa koreksi manual admin.
        if ($period->status !== null && $period->status !== OvertimePeriodStatus::DRAFT) {
            throw new InvalidArgumentException('Hanya periode berstatus Draf (Belum Dibayar) yang dapat disinkronkan dari log absensi.');
        }

        $startDate = Carbon::parse($period->start_date)->toDateString();
        $endDate = Carbon::parse($period->end_date)->toDateString();

        app(OvertimeService::class)->assertWeeklyRosterComplete($startDate, $endDate);

        // Karyawan bulanan mengikuti jadwal perusahaan di bawah. Untuk HLP,
        // hari dibayar berdasarkan kehadiran yang disetujui, termasuk akhir
        // pekan dan hari libur karena mereka tidak mengikuti roster.
        $company = CompanyProfile::getProfile();
        $isRoster = $company->isRosterMode();
        $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);
        $validWorkDays = WorkingDays::workDays($startDate, $endDate, $workDaysPerWeek);

        // Get all attendances in this date range
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
            ->where(fn ($query) => $query->whereNull('review_status')->orWhere('review_status', 'approved'))
            ->get()
            ->groupBy('employee_id');

        // Sinkronkan pengajuan lembur yang sudah diapprove tapi belum tertaut (overtime_period_id IS NULL)
        app(OvertimeService::class)->syncUnlinkedApprovedSubmissions($period);

        $linkedOvertimeEmpIds = $period->submissions()->pluck('employee_id')->unique()->all();
        $attendedEmpIds = $attendances->keys()->all();

        // Karyawan yang sudah punya baris presensi mingguan pada periode ini
        // (termasuk yang sudah resign / diarsipkan) tetap diikutkan sebagai
        // kandidat agar barisnya diperbarui dari log, bukan terhapus.
        $existingRowEmpIds = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
            ->pluck('employee_id')
            ->unique()
            ->all();

        // Target karyawan mingguan: Pegawai Harian Lepas aktif + karyawan yang memiliki kehadiran atau lembur.
        // withTrashed(): karyawan soft-delete yang masih punya baris/kehadiran tetap disinkronkan.
        $candidateEmployees = Employee::withTrashed()
            ->where(function ($q) use ($linkedOvertimeEmpIds, $attendedEmpIds, $existingRowEmpIds) {
                $q->where(function ($q2) use ($linkedOvertimeEmpIds, $attendedEmpIds) {
                    $q2->where('status', 'active')
                        ->where(function ($q3) use ($linkedOvertimeEmpIds, $attendedEmpIds) {
                            $q3->where('employment_status', EmploymentStatus::HARIAN)
                                ->orWhereIn('id', $linkedOvertimeEmpIds)
                                ->orWhereIn('id', $attendedEmpIds);
                        });
                })->orWhereIn('id', $existingRowEmpIds);
            })
            ->get();

        $syncedCount = 0;

        DB::transaction(function () use ($period, $candidateEmployees, $linkedOvertimeEmpIds, $attendances, $validWorkDays, $company, $isRoster, $startDate, $endDate, &$syncedCount) {
            $paidEmployeeIds = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                ->where('payment_status', 'paid')
                ->pluck('employee_id')
                ->all();

            $rosterSchedules = $isRoster
                ? EmployeeSchedule::schedulesForEmployees($candidateEmployees->pluck('id')->all(), $startDate, $endDate)
                : collect();

            foreach ($candidateEmployees as $emp) {
                // STRICT LOCK: Karyawan yang sudah dibayar tidak boleh diubah
                if (in_array($emp->id, $paidEmployeeIds, true)) {
                    $syncedCount++;

                    continue;
                }

                $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;
                $empAttendances = $attendances->get($emp->id);

                // Hari kerja valid menurut jadwal: pada mode Roster mengikuti jadwal shift masing-masing,
                // pada mode Fixed mengikuti hari kerja reguler perusahaan.
                $empValidWorkDays = $isRoster
                    ? WorkingDays::employeeWorkDays($emp->id, $startDate, $endDate, $company, $rosterSchedules->get($emp->id))
                    : $validWorkDays;
                if ($isHarian) {
                    $empValidWorkDays = $this->allDayKeysWithin($startDate, $endDate);
                }

                $attendedDaysKeys = $this->attendedWorkDayKeys($empAttendances ?? collect(), $empValidWorkDays);
                $attendedCount = count($attendedDaysKeys);
                $hasOvertime = in_array($emp->id, $linkedOvertimeEmpIds, true);

                // Opsi A & C: Hanya masukkan karyawan yang gajian (ada kehadiran kerja / upah harian / lembur)
                if ($attendedCount <= 0 && ! $hasOvertime) {
                    OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                        ->where('employee_id', $emp->id)
                        ->where('payment_status', '!=', 'paid')
                        ->delete();

                    continue;
                }

                $rate = $emp->getEffectiveMealAllowancePerDay();
                $totalMeal = round($attendedCount * $rate, 2);

                $dailyRate = $isHarian ? (float) ($emp->daily_rate ?? 0) : 0.0;
                $hourlyWorkPay = 0.0;
                if ($isHarian) {
                    $dailyAttendances = ($empAttendances ?? collect())->filter(fn (Attendance $attendance): bool => ($attendance->casual_work_type_confirmed ?? $attendance->casual_work_type ?? 'daily') === 'daily');
                    $hourlyAttendances = ($empAttendances ?? collect())->filter(fn (Attendance $attendance): bool => ($attendance->casual_work_type_confirmed ?? $attendance->casual_work_type) === 'hourly');
                    $totalDailyWage = (float) $dailyAttendances->sum(fn (Attendance $attendance): float => (float) ($attendance->casual_pay_override ?? $dailyRate));
                    $hourlyWorkPay = (float) $hourlyAttendances->sum(function (Attendance $attendance) use ($emp): float {
                        $hours = $attendance->clock_in && $attendance->clock_out
                            ? app(OvertimeService::class)->calculateOvertimeDuration($attendance->clock_in, $attendance->clock_out)
                            : (int) $attendance->duration_minutes / 60;

                        return round($hours * (float) ($emp->overtime_rate_per_hour ?? 0), 2);
                    });
                } else {
                    $totalDailyWage = 0.0;
                }

                OvertimePeriodAttendance::updateOrCreate(
                    [
                        'overtime_period_id' => $period->id,
                        'employee_id' => $emp->id,
                    ],
                    [
                        'attended_days' => $attendedCount,
                        'attendance_days' => $attendedDaysKeys,
                        'meal_allowance_type' => $emp->meal_allowance_type ?? 'standar',
                        'meal_allowance_per_day' => $rate,
                        'total_meal_allowance' => $totalMeal,
                        'daily_rate' => $dailyRate,
                        'total_daily_wage' => $totalDailyWage,
                        'total_hourly_work_pay' => $hourlyWorkPay,
                    ]
                );

                $syncedCount++;
            }

            // Bersihkan karyawan di luar kandidat yang belum dibayar
            OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                ->where('payment_status', '!=', 'paid')
                ->whereNotIn('employee_id', $candidateEmployees->pluck('id'))
                ->delete();

            OvertimePeriod::query()
                ->whereKey($period->id)
                ->update(['attendance_synced_at' => now()]);
            $period->refresh();
            $period->recalculateTotals();
        });

        Log::info('Kehadiran mingguan berhasil disinkronisasi dari log absensi mesin', [
            'period_id' => $period->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'synced_count' => $syncedCount,
            'actor_id' => $actor?->id,
        ]);

        $this->logger->info(
            action: 'ATTENDANCE_SYNCED_TO_WEEKLY',
            module: 'attendances',
            message: 'Kehadiran mingguan berhasil disinkronisasi dari log absensi mesin',
            context: [
                'period_id' => $period->id,
                'period_code' => $period->period_code ?? $period->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'synced_count' => $syncedCount,
                'actor_id' => $actor?->id,
            ],
            userId: $actor?->id
        );

        return $syncedCount;
    }

    /**
     * Key hari hadir yang HANYA merupakan hari kerja valid menurut jadwal
     * perusahaan (WorkingDays::workDays): Minggu, Sabtu bila jadwal 5 hari,
     * dan hari libur nasional tidak dihitung sebagai hari hadir berbayar.
     * Key tetap dedup per hari dan terurut mon..sun agar deterministik.
     *
     * @param  iterable  $attendances  kumpulan model Attendance (properti `date`)
     * @param  array<string, string>  $validWorkDays  tanggal (Y-m-d) => key hari
     * @return string[] daftar key hari
     */
    private function attendedWorkDayKeys(iterable $attendances, array $validWorkDays): array
    {
        $keys = [];
        foreach ($attendances as $attendance) {
            $dateString = $attendance->date instanceof CarbonInterface
                ? $attendance->date->toDateString()
                : substr((string) $attendance->date, 0, 10);

            $key = $validWorkDays[$dateString] ?? null;
            if ($key !== null && ! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        usort($keys, fn (string $a, string $b) => array_search($a, WorkingDays::DAY_KEYS, true) <=> array_search($b, WorkingDays::DAY_KEYS, true));

        return $keys;
    }

    /** @return array<string, string> */
    private function allDayKeysWithin(string $startDate, string $endDate): array
    {
        $days = [];
        $date = Carbon::parse($startDate)->startOfDay();
        $lastDate = Carbon::parse($endDate)->startOfDay();

        while ($date->lte($lastDate)) {
            $days[$date->toDateString()] = strtolower($date->format('D'));
            $date->addDay();
        }

        return $days;
    }

    /**
     * Hitung jarak Haversine antara dua koordinat GPS dalam satuan meter.
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // radius bumi dalam meter

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Catat presensi masuk / pulang melalui web camera & GPS geofencing.
     *
     * @param  Employee  $employee  Karyawan yang melakukan absen
     * @param  string  $type  'clock_in' atau 'clock_out'
     * @param  string|null  $photoBase64  Gambar selfie format base64
     * @param  float  $latitude  Koordinat lintang perangkat
     * @param  float  $longitude  Koordinat bujur perangkat
     * @param  string|null  $notes  Catatan / alasan jika di luar radius
     * @param  string|null  $deviceInfo  Info browser / user agent
     * @param  User|null  $actor  User yang sedang login
     * @param  mixed  $accuracyMeters  Perkiraan akurasi dari perangkat, dalam meter
     */
    public function recordWebClock(
        Employee $employee,
        string $type,
        ?string $photoBase64,
        float $latitude,
        float $longitude,
        ?string $notes = null,
        ?string $deviceInfo = null,
        ?User $actor = null,
        mixed $accuracyMeters = null,
        ?string $casualWorkType = null,
        ?string $earlyDepartureReason = null
    ): Attendance {
        if (! in_array($type, ['clock_in', 'clock_out', 'overtime_in', 'overtime_out'], true)) {
            throw new InvalidArgumentException('Jenis presensi tidak valid.');
        }
        $isCasualWorker = $employee->employment_status === EmploymentStatus::HARIAN;
        if ($isCasualWorker && $type === 'clock_in' && ! in_array($casualWorkType, ['daily', 'hourly'], true)) {
            throw new InvalidArgumentException('Pilih Kerja harian atau Kerja per jam sebelum absen masuk.');
        }
        if ($isCasualWorker && $type === 'clock_in') {
            if ($casualWorkType === 'daily' && (float) ($employee->daily_rate ?? 0) <= 0) {
                throw new InvalidArgumentException('Nominal kerja harian belum diatur, silahkan hubungi admin untuk minta mengatur nominal harian.');
            }
            if ($casualWorkType === 'hourly' && (float) ($employee->overtime_rate_per_hour ?? 0) <= 0) {
                throw new InvalidArgumentException('Nominal kerja per jam belum diatur, silahkan hubungi admin untuk minta mengatur nominal per jam.');
            }
        }
        if (in_array($type, ['overtime_in', 'overtime_out'], true) && (float) ($employee->overtime_rate_per_hour ?? 0) <= 0) {
            throw new InvalidArgumentException('Nominal lembur belum diatur, silahkan hubungi admin untuk minta mengatur nominal lembur.');
        }
        if ($isCasualWorker && in_array($type, ['clock_in', 'clock_out'], true) && blank($photoBase64)) {
            throw new InvalidArgumentException('Foto selfie wajib diambil saat absen masuk dan pulang.');
        }
        if (! $isCasualWorker && (filled($casualWorkType) || filled($earlyDepartureReason))) {
            throw new InvalidArgumentException('Pilihan kerja harian lepas tidak berlaku untuk karyawan ini.');
        }
        if ($earlyDepartureReason !== null && mb_strlen($earlyDepartureReason) > 1000) {
            throw new InvalidArgumentException('Alasan pulang lebih awal maksimal 1.000 karakter.');
        }
        if (! is_finite($latitude) || $latitude < -90 || $latitude > 90 || ! is_finite($longitude) || $longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Koordinat lokasi tidak valid.');
        }
        if ($accuracyMeters !== null) {
            if ((! is_int($accuracyMeters) && ! is_float($accuracyMeters))
                || ! is_finite((float) $accuracyMeters)
                || $accuracyMeters < 0
                || $accuracyMeters > self::MAX_STORED_GPS_ACCURACY_METERS) {
                throw new InvalidArgumentException('Akurasi GPS tidak valid.');
            }

            $accuracyMeters = (int) ceil($accuracyMeters);
        }

        $today = now()->toDateString();
        $timeNow = now()->format('H:i:s');
        $photoPath = null;

        // 1. Simpan foto selfie jika ada
        if (! empty($photoBase64)) {
            if (str_contains($photoBase64, ';base64,')) {
                [, $photoBase64] = explode(';base64,', $photoBase64, 2);
            }
            if (strlen($photoBase64) > 2_800_000) {
                throw new InvalidArgumentException('Ukuran foto presensi terlalu besar.');
            }
            $binaryData = base64_decode($photoBase64, true);
            if ($binaryData !== false) {
                $imageInfo = @getimagesizefromstring($binaryData);
                if (strlen($binaryData) > 2 * 1024 * 1024 || ! in_array($imageInfo['mime'] ?? null, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    throw new InvalidArgumentException('Foto presensi harus berupa gambar JPG, PNG, atau WebP maksimal 2 MB.');
                }
                $dir = 'attendances/'.now()->format('Y-m');
                $filename = 'emp_'.$employee->id.'_'.$type.'_'.now()->format('His').'_'.substr(md5(uniqid('', true)), 0, 6).'.jpg';
                $photoPath = $dir.'/'.$filename;
                if (! Storage::disk('local')->put($photoPath, $binaryData)) {
                    throw new \RuntimeException('Foto presensi gagal disimpan.');
                }
            } else {
                throw new InvalidArgumentException('Foto presensi tidak valid.');
            }
        }

        try {
            $attendance = DB::transaction(function () use ($employee, $type, $latitude, $longitude, $notes, $deviceInfo, $actor, $accuracyMeters, $photoPath, $today, $timeNow, $casualWorkType, $earlyDepartureReason, $isCasualWorker): Attendance {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $attendance = Attendance::query()->where('employee_id', $employee->id)
                    ->where('date', $today)->lockForUpdate()->firstOrNew();

                $existingTime = match ($type) {
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'overtime_in' => $attendance->overtime_in,
                    'overtime_out' => $attendance->overtime_out,
                };
                if ($existingTime) {
                    throw new InvalidArgumentException('Presensi ini sudah tercatat hari ini.');
                }
                if ($type === 'clock_out' && ! $attendance->clock_in) {
                    throw new InvalidArgumentException('Anda harus presensi masuk sebelum presensi pulang.');
                }
                if ($isCasualWorker && $type === 'clock_out' && ! in_array($attendance->casual_work_type, ['daily', 'hourly'], true)) {
                    throw new InvalidArgumentException('Jenis kerja harian lepas belum tercatat. Hubungi Admin untuk memperbaiki absensi.');
                }
                if ($isCasualWorker && $type === 'clock_out' && $attendance->casual_work_type === 'hourly' && filled($earlyDepartureReason)) {
                    throw new InvalidArgumentException('Keterangan pulang lebih awal hanya berlaku untuk Kerja harian.');
                }
                if ($type === 'clock_in' && $attendance->clock_out) {
                    throw new InvalidArgumentException('Presensi pulang sudah tercatat hari ini.');
                }
                if ($type === 'overtime_out' && ! $attendance->overtime_in) {
                    throw new InvalidArgumentException('Anda harus mulai lembur sebelum mengakhiri lembur.');
                }
                if ($type === 'overtime_in' && $attendance->overtime_out) {
                    throw new InvalidArgumentException('Presensi selesai lembur sudah tercatat hari ini.');
                }
                if ($type === 'overtime_in' && ! $attendance->clock_out) {
                    $schedule = EmployeeSchedule::query()->where('employee_id', $employee->id)->where('date', $today)->first();
                    $company = CompanyProfile::getProfile();
                    $isOffDay = $schedule
                        ? $schedule->isOff()
                        : WorkingDays::isWeekend(now(), (int) ($company->work_days_per_week ?: 6)) || Holiday::query()->where('date', $today)->exists();
                    if (! $isOffDay) {
                        throw new InvalidArgumentException('Anda harus presensi pulang sebelum mulai lembur.');
                    }
                }

                // 2. Evaluasi Geofencing terhadap lokasi yang diizinkan untuk karyawan
                $allowedLocations = $employee->getValidWorkLocationsOnDate($today);
                $matchedLocation = null;
                $distanceMeters = null;
                $isInsideRadius = false;

                if ($allowedLocations === null) {
                    // Bebas lokasi: tidak terikat geofence kantor manapun
                    $isInsideRadius = true;
                    $distanceMeters = 0;
                    $allActive = WorkLocation::query()->active()->get();
                    if ($allActive->isNotEmpty()) {
                        $nearest = null;
                        $minD = INF;
                        foreach ($allActive as $loc) {
                            $d = $this->calculateHaversineDistance($latitude, $longitude, (float) $loc->latitude, (float) $loc->longitude);
                            if ($d < $minD) {
                                $minD = $d;
                                $nearest = $loc;
                            }
                        }
                        $matchedLocation = $nearest;
                    }
                } else {
                    // Hitung jarak ke setiap lokasi yang diizinkan
                    if ($allowedLocations->isEmpty()) {
                        // Fallback ke semua lokasi aktif bila belum di-assign spesifik
                        $allowedLocations = WorkLocation::query()->active()->get();
                    }

                    $minDistance = INF;
                    $bestLocation = null;

                    foreach ($allowedLocations as $location) {
                        $dist = $this->calculateHaversineDistance(
                            $latitude,
                            $longitude,
                            (float) $location->latitude,
                            (float) $location->longitude
                        );

                        if ($dist < $minDistance) {
                            $minDistance = $dist;
                            $bestLocation = $location;
                        }
                    }

                    $matchedLocation = $bestLocation;
                    $distanceMeters = is_finite($minDistance) ? (int) ceil($minDistance) : null;

                    if ($matchedLocation && $distanceMeters !== null && $accuracyMeters !== null
                        && $distanceMeters + $accuracyMeters <= (int) $matchedLocation->radius_meters) {
                        $isInsideRadius = true;
                    } else {
                        $isInsideRadius = false;
                    }
                }

                // Tentukan review_status:
                // Jika di dalam radius: langsung 'approved'
                // Jika di luar radius: 'pending_review' (admin/atasan perlu review catatan)
                $reviewStatus = $isInsideRadius ? 'approved' : 'pending_review';
                if ($isCasualWorker) {
                    $reviewStatus = 'pending_review';
                }
                if (! $isInsideRadius && trim((string) $notes) === '') {
                    throw new InvalidArgumentException('Isi alasan lokasi untuk presensi di luar radius.');
                }

                // 3. Ambil atau inisialisasi record presensi hari ini
                $attendance->employee_id = $employee->id;
                $attendance->date = $today;

                if ($type === 'clock_in') {
                    $attendance->clock_in = $timeNow;
                    $attendance->clock_in_photo_path = $photoPath ?? $attendance->clock_in_photo_path;
                    $attendance->clock_in_latitude = $latitude;
                    $attendance->clock_in_longitude = $longitude;
                    $attendance->clock_in_distance_meters = $distanceMeters;
                    $attendance->clock_in_accuracy_meters = $accuracyMeters;
                    $attendance->clock_in_location_id = $matchedLocation?->id;
                    $attendance->source = 'web_gps';
                    if ($isCasualWorker) {
                        $attendance->casual_work_type = $casualWorkType;
                    }

                    // Status kehadiran dasar
                    if (empty($attendance->status) || $attendance->status === 'absen') {
                        $attendance->status = 'hadir';
                    }
                } elseif ($type === 'clock_out') {
                    $attendance->clock_out = $timeNow;
                    $attendance->clock_out_photo_path = $photoPath ?? $attendance->clock_out_photo_path;
                    $attendance->clock_out_latitude = $latitude;
                    $attendance->clock_out_longitude = $longitude;
                    $attendance->clock_out_distance_meters = $distanceMeters;
                    $attendance->clock_out_accuracy_meters = $accuracyMeters;
                    $attendance->clock_out_location_id = $matchedLocation?->id;
                    if ($isCasualWorker && filled($earlyDepartureReason)) {
                        $attendance->early_departure_reason = trim($earlyDepartureReason);
                    }
                } elseif ($type === 'overtime_in') {
                    $attendance->is_overtime = true;
                    $attendance->overtime_status = 'in_progress';
                    $attendance->overtime_in = $timeNow;
                    $attendance->overtime_in_photo_path = $photoPath ?? $attendance->overtime_in_photo_path;
                    $attendance->overtime_in_latitude = $latitude;
                    $attendance->overtime_in_longitude = $longitude;
                    $attendance->overtime_in_distance_meters = $distanceMeters;
                    $attendance->overtime_in_accuracy_meters = $accuracyMeters;
                    $attendance->overtime_in_location_id = $matchedLocation?->id;
                    if (empty($attendance->source)) {
                        $attendance->source = 'web_gps';
                    }
                    if (empty($attendance->status)) {
                        $attendance->status = 'hadir';
                    }
                } elseif ($type === 'overtime_out') {
                    $attendance->overtime_status = 'completed';
                    $attendance->overtime_out = $timeNow;
                    $attendance->overtime_out_photo_path = $photoPath ?? $attendance->overtime_out_photo_path;
                    $attendance->overtime_out_latitude = $latitude;
                    $attendance->overtime_out_longitude = $longitude;
                    $attendance->overtime_out_distance_meters = $distanceMeters;
                    $attendance->overtime_out_accuracy_meters = $accuracyMeters;
                    $attendance->overtime_out_location_id = $matchedLocation?->id;

                    if ($attendance->overtime_in && $attendance->overtime_out) {
                        try {
                            $in = Carbon::parse($today.' '.$attendance->overtime_in);
                            $out = Carbon::parse($today.' '.$attendance->overtime_out);
                            if ($out->lt($in)) {
                                $out->addDay();
                            }
                            $attendance->overtime_minutes = (int) $in->diffInMinutes($out);
                        } catch (\Throwable) {
                        }
                    }
                }

                // Hitung durasi kerja jika jam masuk dan pulang sudah terisi
                if ($attendance->clock_in && $attendance->clock_out) {
                    try {
                        $in = Carbon::parse($today.' '.$attendance->clock_in);
                        $out = Carbon::parse($today.' '.$attendance->clock_out);
                        if ($out->gte($in)) {
                            $attendance->duration_minutes = (int) $in->diffInMinutes($out);
                        }
                    } catch (\Throwable) {
                        // Ignore parse errors on duration
                    }
                }

                // Catat alasan / review status
                if ($reviewStatus === 'pending_review') {
                    $attendance->review_status = 'pending_review';
                    if ($notes) {
                        $prefix = match ($type) {
                            'clock_in' => '[Masuk] ',
                            'clock_out' => '[Pulang] ',
                            'overtime_in' => '[Mulai Lembur] ',
                            'overtime_out' => '[Selesai Lembur] ',
                            default => "[{$type}] ",
                        };
                        $attendance->notes = trim(($attendance->notes ? $attendance->notes.' | ' : '').$prefix.$notes);
                    }
                } elseif (! $attendance->review_status || $attendance->review_status === 'approved') {
                    $attendance->review_status = 'approved';
                }

                if ($deviceInfo) {
                    $attendance->device_info = substr($deviceInfo, 0, 255);
                }

                $attendance->save();

                if ($type === 'overtime_out') {
                    $this->syncOvertimeSubmissionForAttendance($attendance, $employee, $actor);
                }

                $this->logger->log(
                    action: 'record_web_attendance',
                    module: 'attendance',
                    level: 'info',
                    message: "Presensi {$type} via web kamera & GPS: {$employee->full_name}",
                    context: [
                        'type' => $type,
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'time' => $timeNow,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'distance_meters' => $distanceMeters,
                        'accuracy_meters' => $accuracyMeters,
                        'location_name' => $matchedLocation?->name,
                        'is_inside_radius' => $isInsideRadius,
                        'review_status' => $attendance->review_status,
                        'has_photo' => ! empty($photoPath),
                    ],
                    userId: $actor?->id ?? auth()->id()
                );

                return $attendance;
            });

            $this->refreshDraftWeeklyPeriodsForRange($today, $today, $actor);

            return $attendance;
        } catch (\Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }
    }

    /**
     * Review presensi karyawan oleh Admin / Atasan (Approve / Reject).
     */
    public function reviewAttendance(
        Attendance $attendance,
        string $status,
        ?string $reviewNotes = null,
        ?User $actor = null,
        ?float $casualPayOverride = null,
        ?string $casualPayReason = null,
        ?string $confirmedCasualWorkType = null
    ): Attendance {
        $validStatuses = ['approved', 'rejected'];
        if (! in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Status review tidak valid: {$status}");
        }

        $isCasualWorker = $attendance->employee?->employment_status === EmploymentStatus::HARIAN;
        if (! $isCasualWorker && ($casualPayOverride !== null || filled($casualPayReason))) {
            throw new InvalidArgumentException('Penyesuaian upah ini hanya berlaku untuk karyawan harian lepas.');
        }
        if ($isCasualWorker && $confirmedCasualWorkType === 'hourly' && ($casualPayOverride !== null || filled($casualPayReason))) {
            throw new InvalidArgumentException('Pembayaran Kerja per Jam dihitung otomatis dan tidak dapat disesuaikan manual.');
        }
        if ($isCasualWorker && $status === 'approved') {
            if (! $attendance->clock_in || ! $attendance->clock_out || ! $attendance->clock_in_photo_path || ! $attendance->clock_out_photo_path) {
                throw new InvalidArgumentException('Absensi harian lepas baru bisa disetujui setelah absen masuk dan pulang lengkap dengan foto.');
            }
            if (! in_array($confirmedCasualWorkType, ['daily', 'hourly'], true)) {
                throw new InvalidArgumentException('Admin harus mengonfirmasi jenis kerja harian lepas sebelum menyetujui absensi.');
            }
            if ($confirmedCasualWorkType === 'hourly'
                && (float) ($attendance->employee?->overtime_rate_per_hour ?? 0) <= 0) {
                throw new InvalidArgumentException('Tarif Kerja per Jam karyawan belum tersedia. Lengkapi tarifnya di profil karyawan sebelum menyetujui absensi.');
            }
        }
        if ($casualPayOverride !== null) {
            if (! $isCasualWorker || $status !== 'approved' || ! is_finite($casualPayOverride) || $casualPayOverride < 0) {
                throw new InvalidArgumentException('Nominal penyesuaian pembayaran tidak valid.');
            }
            if (blank($casualPayReason) || mb_strlen($casualPayReason) > 1000) {
                throw new InvalidArgumentException('Alasan penyesuaian pembayaran wajib diisi maksimal 1.000 karakter.');
            }
        }

        $attendance->review_status = $status;
        $attendance->review_notes = $reviewNotes;
        $attendance->reviewed_by = $actor?->id ?? auth()->id();
        $attendance->reviewed_at = now();
        if ($isCasualWorker && $status === 'approved') {
            $attendance->casual_work_type_confirmed = $confirmedCasualWorkType;
            $attendance->casual_pay_override = $confirmedCasualWorkType === 'daily' ? $casualPayOverride : null;
            $attendance->casual_pay_reason = $confirmedCasualWorkType === 'daily' && $casualPayOverride !== null ? trim((string) $casualPayReason) : null;
        } elseif ($status === 'rejected') {
            $attendance->casual_work_type_confirmed = null;
            $attendance->casual_pay_override = null;
            $attendance->casual_pay_reason = null;
        }

        if ($status === 'rejected') {
            $attendance->status = 'absen';
        } elseif ($status === 'approved' && $attendance->status === 'absen') {
            $attendance->status = 'hadir';
        }

        $attendance->save();

        $this->refreshDraftWeeklyPeriodsForRange(
            $attendance->date->toDateString(),
            $attendance->date->toDateString(),
            $actor
        );

        $this->logger->log(
            action: 'review_attendance',
            module: 'attendance',
            level: 'info',
            message: "Review presensi {$attendance->employee?->full_name}: {$status}",
            context: [
                'review_status' => $status,
                'review_notes' => $reviewNotes,
                'employee_name' => $attendance->employee?->full_name,
                'date' => $attendance->date?->toDateString(),
            ],
            userId: $actor?->id ?? auth()->id()
        );

        return $attendance;
    }

    /**
     * Sinkronisasi data presensi lembur ke tabel overtime_submissions untuk approval & payroll.
     */
    public function syncOvertimeSubmissionForAttendance(
        Attendance $attendance,
        Employee $employee,
        ?User $actor = null,
        bool $isAutoCutoff = false
    ): ?OvertimeSubmission {
        if (empty($attendance->overtime_in) || empty($attendance->overtime_out)) {
            return null;
        }

        $attDate = $attendance->date instanceof Carbon ? $attendance->date : Carbon::parse($attendance->date);
        $in = Carbon::parse($attDate->toDateString().' '.$attendance->overtime_in);
        $out = Carbon::parse($attDate->toDateString().' '.$attendance->overtime_out);
        if ($out->lt($in)) {
            $out->addDay();
        }

        $durationHours = app(OvertimeService::class)->calculateOvertimeDuration($in, $out);
        if ($durationHours <= 0) {
            $durationHours = round(($attendance->overtime_minutes ?? 0) / 60, 2);
        }

        $hourlyRate = (float) ($employee->overtime_rate_per_hour ?? 0);
        if ($hourlyRate <= 0 && ! $isAutoCutoff) {
            throw new InvalidArgumentException('Nominal lembur belum diatur, silahkan hubungi admin untuk minta mengatur nominal lembur.');
        }

        $company = CompanyProfile::getProfile();
        $mealThreshold = (float) ($company->overtime_meal_threshold_hours ?? 3.0);
        $defaultMealAmount = (float) ($company->overtime_meal_allowance ?? 15000.0);
        $mealAllowance = $durationHours >= $mealThreshold ? $defaultMealAmount : 0.0;
        $totalAmount = ($durationHours * $hourlyRate) + $mealAllowance;

        $submission = OvertimeSubmission::where('attendance_id', $attendance->id)->first();
        if (! $submission) {
            $referenceNumber = app(OvertimeService::class)->generateSubmissionReference($attDate);
            $submission = new OvertimeSubmission;
            $submission->reference_number = $referenceNumber;
            $submission->attendance_id = $attendance->id;
            $submission->employee_id = $employee->id;
            $submission->overtime_type = OvertimeType::HOURLY;
            $submission->date = $attDate->toDateString();
            $submission->submission_source = OvertimeSubmissionSource::KARYAWAN;
            $submission->status = OvertimeStatus::PENDING_ADMIN;
            $submission->submitted_by = $actor?->id ?? auth()->id() ?? $employee->user_id;
        }

        $submission->start_time = $attendance->overtime_in;
        $submission->end_time = $attendance->overtime_out;
        $submission->duration_hours = $durationHours;
        $submission->hourly_rate = $hourlyRate;
        $submission->meal_allowance = $mealAllowance;
        $submission->total_amount = $totalAmount;
        $submission->reason = $isAutoCutoff
            ? 'Presensi Lembur Mandiri (Auto Cut-off 06:00 - Karyawan lupa tap selesai lembur)'
            : 'Presensi Lembur Mandiri Web Clock';

        if ($isAutoCutoff) {
            $submission->admin_notes = '[Auto Cut-off 06:00] Karyawan tidak melakukan tap selesai lembur. Mohon verifikasi jam lembur aktual sebelum approval.';
        }

        $submission->save();

        return $submission;
    }

    /**
     * Auto cut-off lembur karyawan yang lupa tap selesai hingga pukul 06:00 pagi keesokan harinya.
     */
    public function processEmployeeOvertimeAutoCutoff(Employee $employee): void
    {
        $now = now();
        $pendingOvertime = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('is_overtime', true)
            ->where('overtime_status', 'in_progress')
            ->whereNotNull('overtime_in')
            ->whereNull('overtime_out')
            ->get();

        foreach ($pendingOvertime as $att) {
            $attDate = $att->date instanceof Carbon ? $att->date : Carbon::parse($att->date);
            $isBeforeToday = $attDate->lt($now->copy()->startOfDay());
            $isPastCutoffToday = $attDate->isToday() && $now->format('H:i:s') >= '06:00:00' && $att->overtime_in < '06:00:00';

            if ($isBeforeToday || $isPastCutoffToday) {
                $att->overtime_out = '06:00:00';
                $att->overtime_status = 'auto_cutoff';

                $in = Carbon::parse($attDate->toDateString().' '.$att->overtime_in);
                $out = Carbon::parse($attDate->toDateString().' 06:00:00')->addDay();
                $att->overtime_minutes = max(0, (int) $in->diffInMinutes($out));

                $cutoffNote = '[Auto Cut-off 06:00] Karyawan tidak tap selesai lembur. Menunggu verifikasi admin.';
                $att->notes = trim(($att->notes ? $att->notes.' | ' : '').$cutoffNote);
                $att->save();

                $this->syncOvertimeSubmissionForAttendance($att, $employee, null, true);
            }
        }
    }

    /**
     * Proses auto cut-off untuk seluruh karyawan sekaligus.
     */
    public function processAllOvertimeAutoCutoff(): int
    {
        $now = now();
        $cutoffCount = 0;
        $pendingRecords = Attendance::query()
            ->with('employee')
            ->where('is_overtime', true)
            ->where('overtime_status', 'in_progress')
            ->whereNotNull('overtime_in')
            ->whereNull('overtime_out')
            ->get();

        foreach ($pendingRecords as $att) {
            $attDate = $att->date instanceof Carbon ? $att->date : Carbon::parse($att->date);
            $isBeforeToday = $attDate->lt($now->copy()->startOfDay());
            $isPastCutoffToday = $attDate->isToday() && $now->format('H:i:s') >= '06:00:00' && $att->overtime_in < '06:00:00';

            if ($isBeforeToday || $isPastCutoffToday) {
                $att->overtime_out = '06:00:00';
                $att->overtime_status = 'auto_cutoff';

                $in = Carbon::parse($attDate->toDateString().' '.$att->overtime_in);
                $out = Carbon::parse($attDate->toDateString().' 06:00:00')->addDay();
                $att->overtime_minutes = max(0, (int) $in->diffInMinutes($out));

                $cutoffNote = '[Auto Cut-off 06:00] Karyawan tidak tap selesai lembur. Menunggu verifikasi admin.';
                $att->notes = trim(($att->notes ? $att->notes.' | ' : '').$cutoffNote);
                $att->save();

                if ($att->employee) {
                    $this->syncOvertimeSubmissionForAttendance($att, $att->employee, null, true);
                }
                $cutoffCount++;
            }
        }

        return $cutoffCount;
    }
}
