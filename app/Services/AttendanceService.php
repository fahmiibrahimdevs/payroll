<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\OvertimePeriodStatus;
use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Models\User;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceService
{
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

                        // Proteksi Hari Minggu untuk Karyawan Tetap & Kontrak
                        if ($dateObj->isSunday() && ! $isHarian) {
                            $clockIn = null;
                            $clockOut = null;
                        }

                        // Jika tidak ada tap reguler dan tidak ada tap lembur:
                        if ($clockIn === null && $overtimeIn === null) {
                            if (strtolower($regInRaw) === 'absen' || $dateObj->isSunday()) {
                                $status = $dateObj->isSunday() ? 'libur' : 'absen';
                            } else {
                                continue;
                            }
                        } elseif ($clockIn !== null) {
                            $status = 'hadir';
                        } else {
                            $status = $dateObj->isSunday() ? 'libur' : 'absen';
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

            $upsertRows = [];
            $now = now()->toDateTimeString();

            foreach ($matchedRows as $item) {
                $employee = $item['employee'];
                $r = $item['row'];
                $dataRow = $r + 1;

                $matched[$employee->id] = $employee->full_name;

                foreach ($dateCols as $col => $dateString) {
                    $isSunday = Carbon::parse($dateString)->isSunday();
                    $isHarian = $employee->employment_status === EmploymentStatus::HARIAN;

                    if ($isSunday && ! $isHarian) {
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

    /**
     * Synchronize recorded attendances directly into a weekly OvertimePeriodAttendance.
     */
    public function syncToWeeklyOvertimePeriod(OvertimePeriod $period, ?User $actor = null): int
    {
        // Periode yang sudah lunas tidak boleh berubah — sinkronisasi ulang akan
        // membalikkan grand_total dan menimpa koreksi manual admin.
        if ($period->status !== null && $period->status !== OvertimePeriodStatus::DRAFT) {
            throw new InvalidArgumentException('Hanya periode berstatus Draf (Belum Dibayar) yang dapat disinkronkan dari log absensi.');
        }

        $startDate = Carbon::parse($period->start_date)->toDateString();
        $endDate = Carbon::parse($period->end_date)->toDateString();

        // Hari kerja valid sesuai jadwal perusahaan: kecualikan Minggu (dan
        // Sabtu bila jadwal 5 hari kerja) serta hari libur nasional. Tap di
        // hari libur/akhir pekan tetap tersimpan di absensi sebagai data jam,
        // tetapi TIDAK dihitung sebagai hari hadir berbayar — pembayarannya
        // hanya lewat pengajuan lembur yang di-approve Direktur.
        $workDaysPerWeek = (int) (CompanyProfile::getProfile()->work_days_per_week ?: 6);
        $validWorkDays = WorkingDays::workDays($startDate, $endDate, $workDaysPerWeek);

        // Get all attendances in this date range
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
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

        DB::transaction(function () use ($period, $candidateEmployees, $linkedOvertimeEmpIds, $attendances, $validWorkDays, &$syncedCount) {
            $paidEmployeeIds = OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                ->where('payment_status', 'paid')
                ->pluck('employee_id')
                ->all();

            foreach ($candidateEmployees as $emp) {
                // STRICT LOCK: Karyawan yang sudah dibayar tidak boleh diubah
                if (in_array($emp->id, $paidEmployeeIds, true)) {
                    $syncedCount++;
                    continue;
                }

                $empAttendances = $attendances->get($emp->id);

                // Hanya hari kerja valid (bukan Minggu/Sabtu-jadwal-5-hari/
                // libur nasional) yang dihitung sebagai hari hadir berbayar.
                // Bila rentang tidak punya hari kerja valid sama sekali
                // (kasus ekstrem), hasilnya memang 0 — bukan minimal 1.
                $attendedDaysKeys = $this->attendedWorkDayKeys($empAttendances ?? collect(), $validWorkDays);
                $attendedCount = count($attendedDaysKeys);
                $hasOvertime = in_array($emp->id, $linkedOvertimeEmpIds, true);
                $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;

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
                $totalDailyWage = round($attendedCount * $dailyRate, 2);

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
                    ]
                );

                $syncedCount++;
            }

            // Bersihkan karyawan di luar kandidat yang belum dibayar
            OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                ->where('payment_status', '!=', 'paid')
                ->whereNotIn('employee_id', $candidateEmployees->pluck('id'))
                ->delete();

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
}
