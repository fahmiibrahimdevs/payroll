<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendanceService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}
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

        $timeFormatter = function (?string $val): ?string {
            if (empty($val) || strtolower($val) === 'absen') {
                return null;
            }
            preg_match('/\d{1,2}:\d{2}/', $val, $m);
            if (empty($m[0])) {
                return null;
            }
            $t = $m[0];
            if (strlen($t) === 4) {
                $t = '0' . $t;
            }

            return $t . ':00';
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
                        $chkVal = (string) $firstSheet->getCell(Coordinate::stringFromColumnIndex($c) . $r)->getValue();
                        if (preg_match('/(\d{4}[-\/]\d{2}[-\/]\d{2})\s*~\s*(\d{4}[-\/]\d{2}[-\/]\d{2})/', $chkVal, $rangeMatch)) {
                            break 2;
                        }
                    }
                }
            }

            if (empty($rangeMatch)) {
                throw new \InvalidArgumentException('Format rentang tanggal absensi pada berkas Kartu Laporan tidak terbaca.');
            }

            $startDate = Carbon::parse(trim($rangeMatch[1]));
            $endDate = Carbon::parse(trim($rangeMatch[2]));

            // 2. Deteksi dinamis blok karyawan di setiap sheet Kartu Laporan
            $detectedBlocks = [];
            foreach ($cardSheets as $sheet) {
                $highestColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
                for ($c = 1; $c <= $highestColIndex; $c++) {
                    $colStr = Coordinate::stringFromColumnIndex($c);
                    $val4 = trim((string) $sheet->getCell($colStr . '4')->getValue());
                    if ($val4 === 'ID') {
                        $nextColStr = Coordinate::stringFromColumnIndex($c + 1);
                        $fId = trim((string) $sheet->getCell($nextColStr . '4')->getValue());
                        $rawName = trim((string) $sheet->getCell($nextColStr . '3')->getValue());

                        if ($fId !== '' && $rawName !== '') {
                            $baseColIdx = max(1, $c - 8);
                            $deptColStr = Coordinate::stringFromColumnIndex($baseColIdx + 1);
                            $dept = trim((string) $sheet->getCell($deptColStr . '3')->getValue());

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
                    ->keyBy(fn ($a) => $a->employee_id . '_' . $a->date->toDateString());

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
                        $tglStr = trim((string) $sheet->getCell($dateColStr . $r)->getValue());
                        if (empty($tglStr) || ! preg_match('/^(\d{1,2})/', $tglStr, $m)) {
                            continue;
                        }

                        $dayNum = (int) $m[1];
                        $dateObj = $startDate->copy();
                        if ($dayNum < $startDate->day && $endDate->gt($startDate)) {
                            $dateObj = $endDate->copy()->day($dayNum);
                        } else {
                            $dateObj->day($dayNum);
                        }
                        $dateString = $dateObj->toDateString();

                        // Proteksi strictly: abaikan tanggal di luar rentang $startDate s.d. $endDate
                        if ($dateObj->lt($startDate) || $dateObj->gt($endDate)) {
                            continue;
                        }

                        $regInRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 1) . $r)->getValue());
                        $regOutRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 3) . $r)->getValue());
                        $lemburInRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 10) . $r)->getValue());
                        $lemburOutRaw = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($baseCol + 12) . $r)->getValue());

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

                        $rawLog = trim("Reg: {$regInRaw}" . ($regOutRaw ? " {$regOutRaw}" : '') . ($lemburInRaw ? " | OT: {$lemburInRaw}" : '') . ($lemburOutRaw ? " {$lemburOutRaw}" : ''));

                        // Cek perlindungan koreksi manual HR/Admin via in-memory keyBy (O(1))
                        $key = $employee->id . '_' . $dateString;
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
            throw new \InvalidArgumentException('Format berkas tidak valid: Lembar kerja laporan absensi tidak ditemukan.');
        }

        // 1. Detect date range
        $rangeText = $sheetLog->getCell('C3')->getValue() ?: $sheetLog->getCell('A3')->getValue();
        if (! $rangeText || ! str_contains((string) $rangeText, '~')) {
            for ($c = 1; $c <= 10; $c++) {
                $checkCol = Coordinate::stringFromColumnIndex($c);
                $val = (string) $sheetLog->getCell($checkCol . '3')->getValue();
                if (str_contains($val, '~')) {
                    $rangeText = $val;
                    break;
                }
            }
        }

        if (! $rangeText || ! str_contains((string) $rangeText, '~')) {
            throw new \InvalidArgumentException('Format rentang tanggal absensi pada berkas tidak terbaca.');
        }

        [$startStr, $endStr] = explode('~', (string) $rangeText);
        $startDate = Carbon::parse(trim($startStr));
        $endDate = Carbon::parse(trim($endStr));

        // 2. Map header columns to dates (supports full month spanning up to column AE / 31 days)
        $dateCols = [];
        $cur = $startDate->copy();
        $highestColIndex = Coordinate::columnIndexFromString($sheetLog->getHighestColumn());
        for ($colIdx = 1; $colIdx <= $highestColIndex; $colIdx++) {
            if ($cur->gt($endDate)) {
                break;
            }
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $dayNum = (int) $sheetLog->getCell($col . '4')->getValue();
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
            $idLabel = trim((string) $sheetLog->getCell('A' . $r)->getValue());
            if ($idLabel !== 'ID:') {
                continue;
            }

            $totalParsed++;
            $fId = trim((string) $sheetLog->getCell('C' . $r)->getValue());
            $rawName = trim((string) $sheetLog->getCell('K' . $r)->getValue());
            $dept = trim((string) $sheetLog->getCell('U' . $r)->getValue());

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
                ->keyBy(fn ($a) => $a->employee_id . '_' . $a->date->toDateString());

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

                    $val = trim((string) $sheetLog->getCell($col . $dataRow)->getValue());

                    if (empty($val) || strtolower($val) === 'absen') {
                        continue;
                    }

                    preg_match_all('/\d{1,2}:\d{2}/', $val, $timeMatches);
                    $times = $timeMatches[0] ?? [];

                    if (empty($times)) {
                        continue;
                    }

                    $clockIn = strlen($times[0]) === 4 ? '0' . $times[0] : $times[0];
                    $latestTime = end($times);
                    if (strlen($latestTime) === 4) {
                        $latestTime = '0' . $latestTime;
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

                    $key = $employee->id . '_' . $dateString;
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
        $startDate = Carbon::parse($period->start_date)->toDateString();
        $endDate = Carbon::parse($period->end_date)->toDateString();

        // Get all attendances in this date range
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
            ->get()
            ->groupBy('employee_id');

        $linkedOvertimeEmpIds = $period->submissions()->pluck('employee_id')->unique()->all();
        $attendedEmpIds = $attendances->keys()->all();

        // Target karyawan mingguan: Pegawai Harian Lepas aktif + karyawan yang memiliki kehadiran atau lembur
        $candidateEmployees = Employee::where('status', 'active')
            ->where(function ($q) use ($linkedOvertimeEmpIds, $attendedEmpIds) {
                $q->where('employment_status', EmploymentStatus::HARIAN)
                  ->orWhereIn('id', $linkedOvertimeEmpIds)
                  ->orWhereIn('id', $attendedEmpIds);
            })
            ->get();

        $syncedCount = 0;

        DB::transaction(function () use ($period, $candidateEmployees, $linkedOvertimeEmpIds, $attendances, &$syncedCount) {
            $dayKeyMap = [
                1 => 'mon',
                2 => 'tue',
                3 => 'wed',
                4 => 'thu',
                5 => 'fri',
                6 => 'sat',
                0 => 'sun',
            ];

            foreach ($candidateEmployees as $emp) {
                $empAttendances = $attendances->get($emp->id);
                $attendedDaysKeys = [];

                if ($empAttendances && $empAttendances->isNotEmpty()) {
                    foreach ($empAttendances as $att) {
                        $dow = Carbon::parse($att->date)->dayOfWeek;
                        $key = $dayKeyMap[$dow] ?? null;
                        if ($key && ! in_array($key, $attendedDaysKeys, true)) {
                            $attendedDaysKeys[] = $key;
                        }
                    }
                }

                $attendedCount = count($attendedDaysKeys);
                $hasOvertime = in_array($emp->id, $linkedOvertimeEmpIds, true);
                $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;

                // Opsi A & C: Hanya masukkan karyawan yang gajian (ada kehadiran kerja / upah harian / lembur)
                if ($attendedCount <= 0 && ! $hasOvertime) {
                    OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                        ->where('employee_id', $emp->id)
                        ->delete();
                    continue;
                }

                $rate = (float) ($emp->meal_allowance_per_day ?? 25000);
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

            // Bersihkan karyawan di luar kandidat yang mungkin sempat tersimpan
            OvertimePeriodAttendance::where('overtime_period_id', $period->id)
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
}
