<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\User;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WorkScheduleService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Nama hari singkatan Bahasa Indonesia untuk header matrix.
     */
    public const DAY_NAMES = [
        0 => 'Mgg',
        1 => 'Sen',
        2 => 'Sel',
        3 => 'Rab',
        4 => 'Kam',
        5 => 'Jum',
        6 => 'Sab',
    ];

    /**
     * Mengambil matriks jadwal bulanan (metadata tanggal + daftar karyawan & jadwal per harinya).
     *
     * @return array{
     *     year: int,
     *     month: int,
     *     days_in_month: int,
     *     dates: array<int, array{day: int, date: string, day_name: string, is_sunday: bool, is_holiday: bool, holiday_name: ?string}>,
     *     employees: Collection
     * }
     */
    public function getMonthlyRoster(int $year, int $month, ?int $departmentId = null, ?string $search = null): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $daysInMonth = $startDate->daysInMonth;
        $endDate = Carbon::createFromDate($year, $month, $daysInMonth)->endOfDay();

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        $holidays = Holiday::datesBetween($startDateStr, $endDateStr);
        $company = CompanyProfile::getProfile();
        $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);

        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $curr = Carbon::createFromDate($year, $month, $d);
            $currDateStr = $curr->toDateString();
            $isSunday = $curr->isSunday();
            $isWeekend = WorkingDays::isWeekend($curr, $workDaysPerWeek);
            $isHoliday = $holidays->has($currDateStr);

            $dates[$d] = [
                'day' => $d,
                'date' => $currDateStr,
                'day_name' => self::DAY_NAMES[$curr->dayOfWeek],
                'is_sunday' => $isSunday,
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'holiday_name' => $isHoliday ? $holidays->get($currDateStr) : null,
            ];
        }

        $employeesQuery = Employee::query()
            ->with(['department:id,name', 'position:id,name', 'detail'])
            ->where('status', 'active');

        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }

        if (! empty($search)) {
            $employeesQuery->where(function (Builder $q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('fingerprint_id', 'like', "%{$search}%")
                    ->orWhereHas('position', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
            });
        }

        $employees = $employeesQuery->orderBy('full_name')->get();
        $employeeIds = $employees->pluck('id')->all();

        // Ambil data jadwal yang sudah tersimpan di database
        $schedules = EmployeeSchedule::schedulesForEmployees($employeeIds, $startDateStr, $endDateStr);

        // Petakan jadwal per karyawan untuk setiap tanggal
        $mappedEmployees = $employees->map(function (Employee $emp) use ($dates, $schedules) {
            $empSchedules = $schedules->get($emp->id, collect());
            $scheduleDays = [];
            $offCount = 0;
            $workCount = 0;

            foreach ($dates as $dayNum => $meta) {
                $dateStr = $meta['date'];
                /** @var EmployeeSchedule|null $rec */
                $rec = $empSchedules->get($dateStr);

                if ($rec) {
                    $status = $rec->status;
                    $notes = $rec->notes;
                    $isCustom = true;
                } else {
                    // Default fallback jika belum diset secara spesifik:
                    // Libur jika akhir pekan (sesuai work_days_per_week) atau hari libur nasional
                    $isDefaultOff = $meta['is_weekend'] || $meta['is_holiday'];
                    $status = $isDefaultOff ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;
                    $notes = $meta['holiday_name'] ?? null;
                    $isCustom = false;
                }

                if ($status === EmployeeSchedule::STATUS_OFF) {
                    $offCount++;
                } else {
                    $workCount++;
                }

                $scheduleDays[$dateStr] = [
                    'day' => $dayNum,
                    'status' => $status,
                    'notes' => $notes,
                    'is_custom' => $isCustom,
                ];
            }

            return [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'code' => $emp->detail?->employee_code ?? '-',
                'jobdesk' => $emp->position?->name ?? '-',
                'department' => $emp->department?->name ?? '-',
                'fingerprint_id' => $emp->fingerprint_id,
                'schedules' => $scheduleDays,
                'total_work_days' => $workCount,
                'total_off_days' => $offCount,
            ];
        });

        return [
            'year' => $year,
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'dates' => $dates,
            'employees' => $mappedEmployees,
        ];
    }

    /**
     * Toggle status jadwal (work <-> off) untuk seorang karyawan pada tanggal tertentu.
     */
    public function toggleSchedule(int $employeeId, string $date, ?User $actor = null): EmployeeSchedule
    {
        return DB::transaction(function () use ($employeeId, $date, $actor) {
            $carbonDate = Carbon::parse($date);
            $dateStr = $carbonDate->toDateString();

            // Kunci baris dengan lockForUpdate untuk mencegah race condition
            $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->where('date', $dateStr)
                ->lockForUpdate()
                ->first();

            if ($schedule) {
                $newStatus = $schedule->status === EmployeeSchedule::STATUS_OFF
                    ? EmployeeSchedule::STATUS_WORK
                    : EmployeeSchedule::STATUS_OFF;

                $schedule->update([
                    'status' => $newStatus,
                    'updated_by' => $actor?->id,
                ]);
            } else {
                $company = CompanyProfile::getProfile();
                $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);
                $isWeekend = WorkingDays::isWeekend($carbonDate, $workDaysPerWeek);
                $isHoliday = Holiday::where('date', $dateStr)->exists();

                $defaultStatus = ($isWeekend || $isHoliday) ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;
                $newStatus = $defaultStatus === EmployeeSchedule::STATUS_OFF
                    ? EmployeeSchedule::STATUS_WORK
                    : EmployeeSchedule::STATUS_OFF;

                try {
                    $schedule = EmployeeSchedule::create([
                        'employee_id' => $employeeId,
                        'date' => $dateStr,
                        'status' => $newStatus,
                        'created_by' => $actor?->id,
                        'updated_by' => $actor?->id,
                    ]);
                } catch (QueryException $e) {
                    // Jika ada toggle konkuren yang baru saja dibuat, kunci dan update
                    $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                        ->where('date', $dateStr)
                        ->lockForUpdate()
                        ->first();

                    if ($schedule) {
                        $newStatus = $schedule->status === EmployeeSchedule::STATUS_OFF
                            ? EmployeeSchedule::STATUS_WORK
                            : EmployeeSchedule::STATUS_OFF;

                        $schedule->update([
                            'status' => $newStatus,
                            'updated_by' => $actor?->id,
                        ]);
                    } else {
                        throw $e;
                    }
                }
            }

            $this->logger->info(
                action: 'SCHEDULE_UPDATED',
                module: 'schedules',
                message: "Jadwal karyawan ID {$employeeId} tgl {$dateStr} diubah menjadi {$schedule->status}",
                context: [
                    'employee_id' => $employeeId,
                    'date' => $dateStr,
                    'status' => $schedule->status,
                ],
                userId: $actor?->id
            );

            return $schedule;
        }, 3);
    }

    /**
     * Menetapkan status jadwal secara eksplisit.
     */
    public function setSchedule(int $employeeId, string $date, string $status, ?string $notes = null, ?User $actor = null): EmployeeSchedule
    {
        if (! in_array($status, [EmployeeSchedule::STATUS_WORK, EmployeeSchedule::STATUS_OFF], true)) {
            throw new InvalidArgumentException('Status jadwal harus work atau off.');
        }

        return DB::transaction(function () use ($employeeId, $date, $status, $notes, $actor) {
            $carbonDate = Carbon::parse($date)->toDateString();

            $schedule = EmployeeSchedule::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $carbonDate],
                [
                    'status' => $status,
                    'notes' => $notes,
                    'updated_by' => $actor?->id,
                    'created_by' => DB::raw('COALESCE(created_by, '.($actor?->id ?? 'NULL').')'),
                ]
            );

            return $schedule;
        });
    }

    /**
     * Salin pola hari kerja/libur dari bulan sebelumnya ke bulan target.
     */
    public function copyScheduleFromPreviousMonth(int $targetYear, int $targetMonth, ?User $actor = null): int
    {
        $targetCarbon = Carbon::createFromDate($targetYear, $targetMonth, 1);
        $prevCarbon = $targetCarbon->copy()->subMonth();

        $sourceYear = $prevCarbon->year;
        $sourceMonth = $prevCarbon->month;

        // Hanya salin jadwal untuk karyawan aktif (status = 'active')
        $sourceSchedules = EmployeeSchedule::query()
            ->whereIn('employee_id', Employee::where('status', 'active')->select('id'))
            ->month($sourceYear, $sourceMonth)
            ->get();

        if ($sourceSchedules->isEmpty()) {
            return 0;
        }

        $targetDaysInMonth = $targetCarbon->daysInMonth;
        $sourceDaysInMonth = $prevCarbon->daysInMonth;
        $maxDay = min($targetDaysInMonth, $sourceDaysInMonth);

        $insertedCount = 0;

        DB::transaction(function () use ($sourceSchedules, $targetYear, $targetMonth, $sourceYear, $sourceMonth, $maxDay, $actor, &$insertedCount) {
            $rows = [];
            $now = now();

            foreach ($sourceSchedules as $source) {
                $day = (int) $source->date->day;
                if ($day > $maxDay) {
                    continue;
                }

                $targetDate = sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $day);

                $rows[] = [
                    'employee_id' => $source->employee_id,
                    'date' => $targetDate,
                    'status' => $source->status,
                    'notes' => $source->notes ? "[Salin] {$source->notes}" : null,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                EmployeeSchedule::upsert(
                    $chunk,
                    ['employee_id', 'date'],
                    ['status', 'notes', 'updated_by', 'updated_at']
                );
                $insertedCount += count($chunk);
            }

            $this->logger->info(
                action: 'SCHEDULES_COPIED',
                module: 'schedules',
                message: "Jadwal bulan {$targetMonth}/{$targetYear} disalin dari bulan {$sourceMonth}/{$sourceYear} ({$insertedCount} data)",
                context: [
                    'target_year' => $targetYear,
                    'target_month' => $targetMonth,
                    'count' => $insertedCount,
                ],
                userId: $actor?->id
            );
        });

        return $insertedCount;
    }

    /**
     * Impor jadwal dari file Excel (.xlsx / .xls).
     * Mendukung pembacaan warna sel (Merah = Libur) atau teks ('OFF', 'LIBUR', 'X').
     */
    public function importRosterFromExcel(mixed $file, int $year, int $month, ?User $actor = null): array
    {
        $spreadsheet = null;

        try {
            $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

            // Cari baris header yang memuat angka 1..daysInMonth
            $headerRow = null;
            $dateColumnMap = []; // [colIndex => dayNumber]
            $nameCol = null;
            $codeCol = null;
            $fingerprintCol = null;
            $idCol = null;

            $highestRow = (int) $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColIndex = Coordinate::columnIndexFromString($highestColumn);

            for ($row = 1; $row <= min(15, $highestRow); $row++) {
                $numericCount = 0;
                $tempDateMap = [];

                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $cellVal = trim((string) $sheet->getCell([$col, $row])->getValue());
                    $lowerVal = strtolower($cellVal);

                    // Abaikan Kolom A ($col === 1). Hanya deteksi header numerik tanggal (1..daysInMonth) mulai Kolom B ($col >= 2).
                    if ($col >= 2 && is_numeric($cellVal)) {
                        $dayInt = (int) $cellVal;
                        if ($dayInt >= 1 && $dayInt <= $daysInMonth) {
                            $numericCount++;
                            $tempDateMap[$col] = $dayInt;
                        }
                    }

                    if (in_array($lowerVal, ['nama', 'name', 'nama karyawan'], true)) {
                        $nameCol = $col;
                    } elseif (in_array($lowerVal, ['fingerprint', 'fingerprint_id', 'pin', 'no absen', 'id fingerprint'], true)) {
                        $fingerprintCol = $col;
                    } elseif (in_array($lowerVal, ['nik', 'kode', 'kode karyawan', 'employee_code', 'nip'], true)) {
                        $codeCol = $col;
                    } elseif (in_array($lowerVal, ['id', 'emp_id', 'employee_id'], true)) {
                        $idCol = $col;
                    }
                }

                if ($numericCount >= min(15, $daysInMonth - 5)) {
                    $headerRow = $row;
                    $dateColumnMap = $tempDateMap;
                    break;
                }
            }

            if (! $headerRow || empty($dateColumnMap)) {
                throw new InvalidArgumentException("Format Excel tidak sesuai. Baris tanggal (1 s/d {$daysInMonth}) tidak ditemukan.");
            }

            // Jika kolom Nama belum terdeteksi dari header baris yang sama, cari di baris sebelumnya atau default kolom 2 / 1
            if (! $nameCol) {
                for ($r = 1; $r <= max(1, $headerRow - 1); $r++) {
                    for ($c = 1; $c <= min(5, $highestColIndex); $c++) {
                        $v = strtolower(trim((string) $sheet->getCell([$c, $r])->getValue()));
                        if (str_contains($v, 'nama')) {
                            $nameCol = $c;
                            break 2;
                        }
                    }
                }
                if (! $nameCol) {
                    $valCol1 = strtolower(trim((string) $sheet->getCell([1, max(1, $headerRow - 1)])->getValue()));
                    $nameCol = (in_array($valCol1, ['no', 'no.', 'nomor'], true)) ? 2 : 1;
                }
            }

            // Ambil info hari kerja perusahaan dan hari libur nasional untuk penanganan cell kosong
            $company = CompanyProfile::getProfile();
            $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);
            $startDateStr = sprintf('%04d-%02d-01', $year, $month);
            $endDateStr = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
            $holidays = Holiday::datesBetween($startDateStr, $endDateStr);

            // Pre-calculate default off days per date in month
            $defaultOffDates = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $curr = Carbon::createFromDate($year, $month, $d);
                $currStr = $curr->toDateString();
                $defaultOffDates[$d] = WorkingDays::isWeekend($curr, $workDaysPerWeek) || $holidays->has($currStr);
            }

            // Cache karyawan untuk pencocokan yang aman dan robust
            $employees = Employee::with('detail')->where('status', 'active')->get();
            $byId = [];
            $byFingerprint = [];
            $byCode = [];
            $byExactName = [];
            $byNormalizedName = [];
            $employeeTokenList = [];

            foreach ($employees as $emp) {
                $byId[(int) $emp->id] = $emp;
                $byId[(string) $emp->id] = $emp;

                if (! empty($emp->fingerprint_id)) {
                    $byFingerprint[trim((string) $emp->fingerprint_id)] = $emp;
                }

                if (! empty($emp->detail?->employee_code)) {
                    $byCode[strtolower(trim($emp->detail->employee_code))] = $emp;
                }

                $cleanName = strtolower(trim((string) $emp->full_name));
                $byExactName[$cleanName] = $emp;

                $normalized = preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleanName));
                if ($normalized !== '') {
                    $byNormalizedName[$normalized] = $emp;
                }

                $tokens = array_values(array_filter(explode(' ', $normalized), fn ($t) => mb_strlen($t) >= 2));
                $employeeTokenList[] = [
                    'employee' => $emp,
                    'normalized' => $normalized,
                    'tokens' => $tokens,
                ];
            }

            $recordsSaved = 0;
            $matchedEmployees = 0;
            $unmatchedNames = [];

            DB::transaction(function () use (
                $sheet,
                $headerRow,
                $highestRow,
                $nameCol,
                $codeCol,
                $fingerprintCol,
                $idCol,
                $dateColumnMap,
                $byId,
                $byFingerprint,
                $byCode,
                $byExactName,
                $byNormalizedName,
                $employeeTokenList,
                $defaultOffDates,
                $year,
                $month,
                $actor,
                &$recordsSaved,
                &$matchedEmployees,
                &$unmatchedNames
            ) {
                $upsertRows = [];
                $now = now();

                for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                    $rawName = trim((string) $sheet->getCell([$nameCol, $row])->getValue());
                    if (empty($rawName) || str_starts_with($rawName, '*') || str_contains(strtolower($rawName), 'periode') || str_contains(strtolower($rawName), 'total')) {
                        continue;
                    }

                    /** @var Employee|null $employee */
                    $employee = null;

                    // 1. Cek dari kolom ID eksplisit jika ada
                    if ($idCol) {
                        $rawId = trim((string) $sheet->getCell([$idCol, $row])->getValue());
                        if ($rawId !== '' && isset($byId[$rawId])) {
                            $employee = $byId[$rawId];
                        }
                    }

                    // 2. Cek dari kolom Fingerprint eksplisit jika ada
                    if (! $employee && $fingerprintCol) {
                        $rawFp = trim((string) $sheet->getCell([$fingerprintCol, $row])->getValue());
                        if ($rawFp !== '' && isset($byFingerprint[$rawFp])) {
                            $employee = $byFingerprint[$rawFp];
                        }
                    }

                    // 3. Cek dari kolom Kode Karyawan jika ada
                    if (! $employee && $codeCol) {
                        $rawCode = strtolower(trim((string) $sheet->getCell([$codeCol, $row])->getValue()));
                        if ($rawCode !== '' && isset($byCode[$rawCode])) {
                            $employee = $byCode[$rawCode];
                        }
                    }

                    // 4. Pencocokan kolom nama
                    if (! $employee) {
                        $cleanInput = strtolower($rawName);

                        if (isset($byExactName[$cleanInput])) {
                            $employee = $byExactName[$cleanInput];
                        } elseif (isset($byCode[$cleanInput])) {
                            $employee = $byCode[$cleanInput];
                        } elseif (isset($byFingerprint[$cleanInput])) {
                            $employee = $byFingerprint[$cleanInput];
                        } elseif (is_numeric($cleanInput) && isset($byId[(int) $cleanInput])) {
                            $employee = $byId[(int) $cleanInput];
                        } else {
                            $normalizedInput = preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleanInput));
                            if (isset($byNormalizedName[$normalizedInput])) {
                                $employee = $byNormalizedName[$normalizedInput];
                            } else {
                                // 5. Unambiguous word token match (Mencegah collision nama depan dan false positive 'Ade' vs 'Dede')
                                $inputTokens = array_values(array_filter(explode(' ', $normalizedInput), fn ($t) => mb_strlen($t) >= 2));
                                if (! empty($inputTokens)) {
                                    $candidates = [];
                                    foreach ($employeeTokenList as $item) {
                                        $empTokens = $item['tokens'];
                                        $allInputTokensInEmp = true;
                                        foreach ($inputTokens as $it) {
                                            if (! in_array($it, $empTokens, true)) {
                                                $allInputTokensInEmp = false;
                                                break;
                                            }
                                        }

                                        $allEmpTokensInInput = true;
                                        foreach ($empTokens as $et) {
                                            if (! in_array($et, $inputTokens, true)) {
                                                $allEmpTokensInInput = false;
                                                break;
                                            }
                                        }

                                        if ($allInputTokensInEmp || $allEmpTokensInInput) {
                                            $candidates[] = $item['employee'];
                                        }
                                    }

                                    // HANYA terima jika strictly unambiguous (tepat 1 karyawan yang cocok)
                                    if (count($candidates) === 1) {
                                        $employee = $candidates[0];
                                    }
                                }
                            }
                        }
                    }

                    if (! $employee) {
                        $unmatchedNames[] = $rawName;

                        continue;
                    }

                    $matchedEmployees++;

                    foreach ($dateColumnMap as $colIndex => $day) {
                        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isDefaultOff = $defaultOffDates[$day] ?? false;

                        $cell = $sheet->getCell([$colIndex, $row]);
                        $val = strtoupper(trim((string) $cell->getValue()));

                        // Deteksi warna background sel
                        $fillColor = '';
                        try {
                            $fill = $cell->getStyle()->getFill();
                            if ($fill->getFillType() !== Fill::FILL_NONE) {
                                $fillColor = strtoupper(trim((string) $fill->getStartColor()->getRGB()));
                            }
                        } catch (\Throwable) {
                        }

                        // Cek apakah merah (Off / Libur)
                        $isRedColor = in_array($fillColor, ['FF0000', 'C00000', 'F44336', 'EF4444', 'DC2626', 'E11D48', 'B91C1C', '991B1B'], true)
                            || (strlen($fillColor) === 6 && hexdec(substr($fillColor, 0, 2)) > 180 && hexdec(substr($fillColor, 2, 2)) < 80);

                        $isExplicitOff = in_array($val, ['OFF', 'LIBUR', 'X', 'L', 'O'], true) || $isRedColor;
                        $isExplicitWork = in_array($val, ['WORK', 'KERJA', 'MASUK', 'W', 'M', 'V', '1'], true);

                        if ($isExplicitOff) {
                            $status = EmployeeSchedule::STATUS_OFF;
                            $notes = 'Import Jadwal Excel (OFF)';
                        } elseif ($isExplicitWork) {
                            $status = EmployeeSchedule::STATUS_WORK;
                            $notes = 'Import Jadwal Excel (WORK)';
                        } else {
                            // Blank/empty cell: jangan paksakan WORK, pertahankan default libur jika akhir pekan/libur nasional
                            $status = $isDefaultOff ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;
                            $notes = null;
                        }

                        $upsertRows[] = [
                            'employee_id' => $employee->id,
                            'date' => $dateStr,
                            'status' => $status,
                            'notes' => $notes,
                            'created_by' => $actor?->id,
                            'updated_by' => $actor?->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (! empty($upsertRows)) {
                    foreach (array_chunk($upsertRows, 250) as $chunk) {
                        EmployeeSchedule::upsert(
                            $chunk,
                            ['employee_id', 'date'],
                            ['status', 'notes', 'updated_by', 'updated_at']
                        );
                        $recordsSaved += count($chunk);
                    }
                }

                $this->logger->info(
                    action: 'SCHEDULES_IMPORTED',
                    module: 'schedules',
                    message: "Impor jadwal bulan {$month}/{$year} berhasil: {$recordsSaved} jadwal untuk {$matchedEmployees} karyawan",
                    context: [
                        'year' => $year,
                        'month' => $month,
                        'matched_employees' => $matchedEmployees,
                        'records_saved' => $recordsSaved,
                        'unmatched_count' => count($unmatchedNames),
                    ],
                    userId: $actor?->id
                );
            });

            return [
                'records_saved' => $recordsSaved,
                'matched_employees' => $matchedEmployees,
                'unmatched_names' => array_unique($unmatchedNames),
            ];
        } finally {
            if ($spreadsheet instanceof Spreadsheet) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }
    }

    /**
     * Export template Excel jadwal bulanan (format siap isi / edit seperti contoh klien).
     */
    public function exportRosterToExcel(int $year, int $month, ?int $departmentId = null, ?string $search = null): Spreadsheet
    {
        $roster = $this->getMonthlyRoster($year, $month, $departmentId, $search);
        $daysInMonth = $roster['days_in_month'];
        $dates = $roster['dates'];
        $employees = $roster['employees'];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $monthName = Carbon::createFromDate($year, $month, 1)->locale('id')->translatedFormat('F Y');
        $sheet->setTitle(substr("Jadwal {$monthName}", 0, 31));

        // Judul Periode
        $sheet->setCellValue('A1', "PERIODE {$monthName}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        // Header Tabel (Baris 3 & 4)
        $sheet->setCellValue('A3', 'NO');
        $sheet->setCellValue('B3', 'NAMA');
        $sheet->setCellValue('C3', 'JOBDESK');
        $sheet->mergeCells('A3:A4');
        $sheet->mergeCells('B3:B4');
        $sheet->mergeCells('C3:C4');

        $colIdx = 4;
        foreach ($dates as $day => $meta) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}3", $meta['day_name']);
            $sheet->setCellValue("{$colLetter}4", $day);

            // Warna merah di header jika hari Minggu atau libur nasional
            if ($meta['is_sunday'] || $meta['is_holiday']) {
                $sheet->getStyle("{$colLetter}3:{$colLetter}4")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EF4444');
                $sheet->getStyle("{$colLetter}3:{$colLetter}4")->getFont()->getColor()->setRGB('FFFFFF');
            }

            $colIdx++;
        }

        // Styling header
        $lastColLetter = Coordinate::stringFromColumnIndex($colIdx - 1);
        $sheet->getStyle("A3:{$lastColLetter}4")->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastColLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $rowNum = 5;
        $totalEmployees = count($employees);
        $lastRow = max(5, $rowNum + $totalEmployees - 1);

        if ($totalEmployees > 0) {
            // Optimasi styling massal: set default hijau untuk seluruh grid tanggal
            $sheet->getStyle("D5:{$lastColLetter}{$lastRow}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '22C55E'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $offStyle = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EF4444'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                    'size' => 9,
                ],
            ];

            foreach ($employees as $idx => $emp) {
                $sheet->setCellValue("A{$rowNum}", $idx + 1);
                $sheet->setCellValue("B{$rowNum}", $emp['name']);
                $sheet->setCellValue("C{$rowNum}", $emp['jobdesk']);

                $colIdx = 4;
                foreach ($dates as $day => $meta) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIdx);
                    $sch = $emp['schedules'][$meta['date']] ?? null;
                    $isOff = $sch && $sch['status'] === EmployeeSchedule::STATUS_OFF;

                    if ($isOff) {
                        $cellCoord = "{$colLetter}{$rowNum}";
                        $sheet->setCellValue($cellCoord, 'OFF');
                        $sheet->getStyle($cellCoord)->applyFromArray($offStyle);
                    } else {
                        $sheet->setCellValue("{$colLetter}{$rowNum}", '');
                    }

                    $colIdx++;
                }

                $rowNum++;
            }
        }

        // Border tabel
        $actualLastRow = $rowNum - 1;
        if ($actualLastRow >= 3) {
            $sheet->getStyle("A3:{$lastColLetter}{$actualLastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        // Auto width untuk kolom A, B, C
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(18);

        for ($c = 4; $c < $colIdx; $c++) {
            $colLetter = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setWidth(5);
        }

        return $spreadsheet;
    }
}
