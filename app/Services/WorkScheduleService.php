<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
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

        $dates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $curr = Carbon::createFromDate($year, $month, $d);
            $currDateStr = $curr->toDateString();
            $isSunday = $curr->isSunday();
            $isHoliday = $holidays->has($currDateStr);

            $dates[$d] = [
                'day' => $d,
                'date' => $currDateStr,
                'day_name' => self::DAY_NAMES[$curr->dayOfWeek],
                'is_sunday' => $isSunday,
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
                    // Default fallback jika belum pernah diset:
                    // Hari Minggu libur (off), hari kerja biasa masuk (work)
                    $status = $meta['is_sunday'] ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;
                    $notes = null;
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
            $schedule = EmployeeSchedule::where('employee_id', $employeeId)
                ->whereDate('date', $carbonDate->toDateString())
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
                // Jika belum ada record: default Minggu adalah off (maka toggle jadi work), default weekday adalah work (maka toggle jadi off)
                $defaultStatus = $carbonDate->isSunday() ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;
                $newStatus = $defaultStatus === EmployeeSchedule::STATUS_OFF
                    ? EmployeeSchedule::STATUS_WORK
                    : EmployeeSchedule::STATUS_OFF;

                $schedule = EmployeeSchedule::create([
                    'employee_id' => $employeeId,
                    'date' => $carbonDate->toDateString(),
                    'status' => $newStatus,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ]);
            }

            $this->logger->info(
                action: 'SCHEDULE_UPDATED',
                module: 'schedules',
                message: "Jadwal karyawan ID {$employeeId} tgl {$date} diubah menjadi {$schedule->status}",
                context: [
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'status' => $schedule->status,
                ],
                userId: $actor?->id
            );

            return $schedule;
        });
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

        $sourceSchedules = EmployeeSchedule::query()
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
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        // Cari baris header yang memuat angka 1..daysInMonth
        $headerRow = null;
        $dateColumnMap = []; // [colLetter => dayNumber]
        $nameCol = null;

        $highestRow = min(200, $sheet->getHighestRow());
        $highestColumn = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($row = 1; $row <= min(15, $highestRow); $row++) {
            $numericCount = 0;
            $tempDateMap = [];

            for ($col = 1; $col <= $highestColIndex; $col++) {
                $cellVal = trim((string) $sheet->getCell([$col, $row])->getValue());
                if (is_numeric($cellVal) && (int) $cellVal >= 1 && (int) $cellVal <= $daysInMonth) {
                    $dayInt = (int) $cellVal;
                    $numericCount++;
                    $tempDateMap[$col] = $dayInt;
                }

                if (in_array(strtolower($cellVal), ['nama', 'name', 'nama karyawan'], true)) {
                    $nameCol = $col;
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

        // Jika kolom Nama belum terdeteksi dari header baris yang sama, cari di baris sebelumnya atau default kolom 1 / 2
        if (! $nameCol) {
            for ($c = 1; $c <= min(4, $highestColIndex); $c++) {
                $v = strtolower(trim((string) $sheet->getCell([$c, $headerRow - 1] ?? [$c, 1])->getValue()));
                if (str_contains($v, 'nama')) {
                    $nameCol = $c;
                    break;
                }
            }
            if (! $nameCol) {
                $nameCol = 1;
            }
        }

        // Cache karyawan untuk pencocokan nama
        $employees = Employee::where('status', 'active')->get();
        $employeeLookup = [];
        foreach ($employees as $emp) {
            $cleanName = strtolower(trim($emp->full_name));
            $employeeLookup[$cleanName] = $emp;
            // First name mapping jika nama panggilan
            $parts = explode(' ', $cleanName);
            if (! empty($parts[0]) && ! isset($employeeLookup[$parts[0]])) {
                $employeeLookup[$parts[0]] = $emp;
            }
        }

        $recordsSaved = 0;
        $matchedEmployees = 0;
        $unmatchedNames = [];

        DB::transaction(function () use (
            $sheet,
            $headerRow,
            $highestRow,
            $nameCol,
            $dateColumnMap,
            $employeeLookup,
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
                if (empty($rawName) || str_starts_with($rawName, '*') || str_contains(strtolower($rawName), 'periode')) {
                    continue;
                }

                $cleanName = strtolower($rawName);
                /** @var Employee|null $employee */
                $employee = $employeeLookup[$cleanName] ?? null;

                if (! $employee) {
                    // Coba fuzzy match
                    foreach ($employeeLookup as $key => $cand) {
                        if (str_contains($key, $cleanName) || str_contains($cleanName, $key)) {
                            $employee = $cand;
                            break;
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
                    $cell = $sheet->getCell([$colIndex, $row]);
                    $val = strtoupper(trim((string) $cell->getValue()));

                    // Deteksi warna background sel
                    $fillColor = $cell->getStyle()->getFill()->getStartColor()->getRGB();
                    $fillColor = strtoupper(trim((string) $fillColor));

                    // Cek apakah merah (Off / Libur)
                    // Warna merah umum: FF0000, F44336, EF4444, E11D48, C00000, atau varian red
                    $isRedColor = in_array($fillColor, ['FF0000', 'C00000', 'F44336', 'EF4444', 'DC2626', 'E11D48', 'B91C1C', '991B1B'], true)
                        || (strlen($fillColor) === 6 && hexdec(substr($fillColor, 0, 2)) > 180 && hexdec(substr($fillColor, 2, 2)) < 80);

                    $isExplicitOff = in_array($val, ['OFF', 'LIBUR', 'X', 'L', 'O'], true) || $isRedColor;
                    $status = $isExplicitOff ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;

                    $upsertRows[] = [
                        'employee_id' => $employee->id,
                        'date' => $dateStr,
                        'status' => $status,
                        'notes' => $isExplicitOff ? 'Import Jadwal Excel' : null,
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
    }

    /**
     * Export template Excel jadwal bulanan (format siap isi / edit seperti contoh klien).
     */
    public function exportRosterToExcel(int $year, int $month): Spreadsheet
    {
        $roster = $this->getMonthlyRoster($year, $month);
        $daysInMonth = $roster['days_in_month'];
        $dates = $roster['dates'];
        $employees = $roster['employees'];

        $spreadsheet = new Spreadsheet();
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
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}3", $meta['day_name']);
            $sheet->setCellValue("{$colLetter}4", $day);

            // Warna merah di header jika hari Minggu
            if ($meta['is_sunday']) {
                $sheet->getStyle("{$colLetter}3:{$colLetter}4")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('EF4444');
                $sheet->getStyle("{$colLetter}3:{$colLetter}4")->getFont()->getColor()->setRGB('FFFFFF');
            }

            $colIdx++;
        }

        // Styling header
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx - 1);
        $sheet->getStyle("A3:{$lastColLetter}4")->getFont()->setBold(true);
        $sheet->getStyle("A3:{$lastColLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Baris Karyawan
        $rowNum = 5;
        foreach ($employees as $idx => $emp) {
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValue("B{$rowNum}", $emp['name']);
            $sheet->setCellValue("C{$rowNum}", $emp['jobdesk']);

            $colIdx = 4;
            foreach ($dates as $day => $meta) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sch = $emp['schedules'][$meta['date']] ?? null;
                $isOff = $sch && $sch['status'] === EmployeeSchedule::STATUS_OFF;

                // Sel Hijau (Masuk) atau Merah (Libur)
                $fillColor = $isOff ? 'EF4444' : '22C55E';
                $sheet->setCellValue("{$colLetter}{$rowNum}", $isOff ? 'OFF' : '');

                $sheet->getStyle("{$colLetter}{$rowNum}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($fillColor);

                if ($isOff) {
                    $sheet->getStyle("{$colLetter}{$rowNum}")->getFont()->getColor()->setRGB('FFFFFF');
                    $sheet->getStyle("{$colLetter}{$rowNum}")->getFont()->setBold(true)->setSize(9);
                }

                $sheet->getStyle("{$colLetter}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $colIdx++;
            }

            $rowNum++;
        }

        // Border tabel
        $lastRow = $rowNum - 1;
        $sheet->getStyle("A3:{$lastColLetter}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto width untuk kolom A, B, C
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(18);

        for ($c = 4; $c < $colIdx; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setWidth(5);
        }

        return $spreadsheet;
    }
}
