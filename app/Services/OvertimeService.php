<?php

namespace App\Services;

use App\Enums\EmploymentStatus;
use App\Enums\OvertimePeriodStatus;
use App\Enums\OvertimeStatus;
use App\Enums\OvertimeSubmissionSource;
use App\Enums\OvertimeType;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\OvertimePeriod;
use App\Models\OvertimePeriodAttendance;
use App\Models\OvertimeSubmission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class OvertimeService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Reserve sequence number atomically to prevent race condition.
     */
    private function reserveSequenceNumber(string $type, Carbon $date): string
    {
        $prefix = $type === 'submission'
            ? 'LMB-' . $date->format('Ym') . '-'
            : 'PER-LMB-' . $date->format('Y-W') . '-';
        $padLength = $type === 'submission' ? 4 : 3;
        $sequenceKey = rtrim($prefix, '-');
        $sourceTable = $type === 'submission' ? 'overtime_submissions' : 'overtime_periods';
        $sourceColumn = 'reference_number';

        $latest = DB::table($sourceTable)
            ->where($sourceColumn, 'like', $prefix . '%')
            ->orderByDesc($sourceColumn)
            ->value($sourceColumn);

        $initialNumber = 0;
        if ($latest && preg_match('/(\d{' . $padLength . ',})$/', (string) $latest, $matches)) {
            $initialNumber = (int) $matches[1];
        }

        DB::table('overtime_sequences')->insertOrIgnore([
            'sequence_key' => $sequenceKey,
            'last_number' => $initialNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('overtime_sequences')
            ->where('sequence_key', $sequenceKey)
            ->lockForUpdate()
            ->first();

        $lastNumber = max((int) ($sequence->last_number ?? 0), $initialNumber);
        $nextNumber = $lastNumber + 1;

        DB::table('overtime_sequences')
            ->where('sequence_key', $sequenceKey)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return $prefix . str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique reference number for overtime submission.
     */
    public function generateSubmissionReference(?Carbon $date = null): string
    {
        $targetDate = $date ?? now();
        $callback = fn (): string => $this->reserveSequenceNumber('submission', $targetDate);

        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }

    /**
     * Generate unique reference number for overtime period.
     */
    public function generatePeriodReference(?Carbon $date = null): string
    {
        $targetDate = $date ?? now();
        $callback = fn (): string => $this->reserveSequenceNumber('period', $targetDate);

        return DB::transactionLevel() > 0 ? $callback() : DB::transaction($callback);
    }

    /**
     * Calculate overtime duration in hours based on company profile rounding policy.
     */
    public function calculateOvertimeDuration(
        Carbon|string $startTime,
        Carbon|string $endTime,
        ?string $mode = null,
        ?int $thresholdMinutes = null
    ): float {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        if ($totalMinutes <= 0) {
            return 0.0;
        }

        $profile = null;
        if ($mode === null || $thresholdMinutes === null) {
            $profile = CompanyProfile::getProfile();
        }

        if ($mode === null) {
            $mode = $profile?->overtime_rounding_mode ?? 'round_60_min';
        }

        if ($thresholdMinutes === null) {
            $thresholdMinutes = (int) ($profile?->overtime_rounding_threshold_minutes ?? 10);
        }

        return match ($mode) {
            'round_30_min' => (float) (round($totalMinutes / 30) * 0.5),
            'round_60_min' => (float) $this->roundToHoursWithThreshold($totalMinutes, $thresholdMinutes),
            'floor_hour' => (float) floor($totalMinutes / 60),
            'exact' => (float) round($totalMinutes / 60, 2),
            default => (float) $this->roundToHoursWithThreshold($totalMinutes, $thresholdMinutes),
        };
    }

    /**
     * Round total overtime minutes to hours using a dynamic threshold (in minutes).
     * Excess minutes meeting or exceeding the threshold are rounded up to 1 full hour.
     */
    public function roundToHoursWithThreshold(int $totalMinutes, int $thresholdMinutes = 10): float
    {
        if ($totalMinutes <= 0) {
            return 0.0;
        }

        $hours = intdiv($totalMinutes, 60);
        $remainderMinutes = $totalMinutes % 60;

        if ($thresholdMinutes === 0) {
            if ($remainderMinutes > 0) {
                $hours += 1;
            }
        } elseif ($thresholdMinutes > 0 && $remainderMinutes >= $thresholdMinutes) {
            $hours += 1;
        }

        return (float) $hours;
    }

    /**
     * Retrieve paginated overtime submissions with filters.
     */
    public function getPaginatedSubmissions(
        ?string $search = null,
        ?string $status = null,
        ?int $employeeId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = OvertimeSubmission::query()
            ->with(['employee.department', 'employee.position', 'submitter', 'verifiedByAdmin', 'approvedByDirektur', 'rejectedBy'])
            ->latest('date')
            ->latest('id');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($empQuery) use ($search) {
                        $empQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Check if the employee already has an active overtime submission overlapping the specified time.
     */
    public function checkSubmissionOverlap(
        int $employeeId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeSubmissionId = null
    ): ?OvertimeSubmission {
        $newStart = Carbon::parse("{$date} {$startTime}");
        $newEnd = Carbon::parse("{$date} {$endTime}");
        if ($newEnd->lte($newStart)) {
            $newEnd->addDay();
        }

        $prevDate = Carbon::parse($date)->subDay()->toDateString();

        $candidates = OvertimeSubmission::query()
            ->where('employee_id', $employeeId)
            ->whereIn('date', [$prevDate, $date])
            ->where('overtime_type', OvertimeType::HOURLY)
            ->whereIn('status', [
                OvertimeStatus::PENDING_ADMIN,
                OvertimeStatus::PENDING_DIREKTUR,
                OvertimeStatus::APPROVED,
            ])
            ->when($excludeSubmissionId, fn ($q) => $q->where('id', '!=', $excludeSubmissionId))
            ->get();

        foreach ($candidates as $candidate) {
            if (! $candidate->start_time || ! $candidate->end_time) {
                continue;
            }

            $candDateStr = $candidate->date instanceof Carbon ? $candidate->date->format('Y-m-d') : substr((string) $candidate->date, 0, 10);
            $candStart = Carbon::parse("{$candDateStr} {$candidate->start_time}");
            $candEnd = Carbon::parse("{$candDateStr} {$candidate->end_time}");
            if ($candEnd->lte($candStart)) {
                $candEnd->addDay();
            }

            $latestStart = $candStart->gt($newStart) ? $candStart : $newStart;
            $earliestEnd = $candEnd->lt($newEnd) ? $candEnd : $newEnd;

            if ($latestStart->lt($earliestEnd)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Create a new overtime submission.
     */
    public function createSubmission(array $data, User $actor): OvertimeSubmission
    {
        return DB::transaction(function () use ($data, $actor) {
            $employeeId = $data['employee_id'];
            $employee = Employee::findOrFail($employeeId);
            $submissionDate = Carbon::parse($data['date'])->toDateString();

            $overtimeType = $data['overtime_type'] ?? 'hourly';
            $isDaily = $overtimeType === 'daily' || $overtimeType === OvertimeType::DAILY;

            // 2. Cek apakah karyawan sudah memiliki pengajuan lembur harian di tanggal yang sama
            $hasDailySubmission = OvertimeSubmission::query()
                ->where('employee_id', $employeeId)
                ->where('date', $submissionDate)
                ->where('overtime_type', OvertimeType::DAILY)
                ->whereIn('status', [
                    OvertimeStatus::PENDING_ADMIN,
                    OvertimeStatus::PENDING_DIREKTUR,
                    OvertimeStatus::APPROVED,
                ])
                ->exists();

            if ($isDaily) {
                if ($hasDailySubmission) {
                    throw new InvalidArgumentException("Karyawan {$employee->full_name} sudah memiliki pengajuan lembur harian pada tanggal {$submissionDate}.");
                }

                // Tidak boleh mengajukan lembur harian jika sudah ada lembur per jam di tanggal yang sama
                $hasHourlySubmission = OvertimeSubmission::query()
                    ->where('employee_id', $employeeId)
                    ->where('date', $submissionDate)
                    ->where('overtime_type', OvertimeType::HOURLY)
                    ->whereIn('status', [
                        OvertimeStatus::PENDING_ADMIN,
                        OvertimeStatus::PENDING_DIREKTUR,
                        OvertimeStatus::APPROVED,
                    ])
                    ->exists();

                if ($hasHourlySubmission) {
                    throw new InvalidArgumentException("Karyawan {$employee->full_name} sudah memiliki pengajuan lembur per jam pada tanggal {$submissionDate}. Lembur harian tidak dapat digabungkan.");
                }

                $rate = (float) ($employee->overtime_rate_per_day ?? 0);
                if ($rate <= 0) {
                    throw new InvalidArgumentException("Karyawan {$employee->full_name} belum memiliki nominal lembur harian di profilnya.");
                }

                $startTimeVal = null;
                $endTimeVal = null;
                $durationHours = 1.0;
            } else {
                if ($hasDailySubmission) {
                    throw new InvalidArgumentException("Karyawan {$employee->full_name} sudah memiliki pengajuan lembur harian pada tanggal {$submissionDate}. Tidak dapat mengajukan lembur per jam.");
                }

                $rate = (float) ($employee->overtime_rate_per_hour ?? 0);
                if ($rate <= 0) {
                    throw new InvalidArgumentException("Karyawan {$employee->full_name} belum memiliki tarif lembur per jam di profilnya.");
                }

                $startTime = Carbon::parse($data['start_time']);
                $endTime = Carbon::parse($data['end_time']);
                if ($startTime->equalTo($endTime)) {
                    throw new InvalidArgumentException('Jam selesai lembur tidak boleh sama dengan jam mulai.');
                }

                // Cek tumpang tindih jam lembur (overlapping hours)
                $overlapping = $this->checkSubmissionOverlap(
                    $employeeId,
                    $submissionDate,
                    $data['start_time'],
                    $data['end_time']
                );

                if ($overlapping) {
                    $overlapStart = $overlapping->start_time;
                    $overlapEnd = $overlapping->end_time;
                    throw new InvalidArgumentException("Jam lembur ({$data['start_time']} - {$data['end_time']}) bertabrakan dengan pengajuan lembur yang sudah ada ({$overlapping->reference_number}: {$overlapStart} - {$overlapEnd}).");
                }

                $durationHours = $this->calculateOvertimeDuration($startTime, $endTime);
                if (isset($data['duration_hours']) && (float) $data['duration_hours'] > 0) {
                    $durationHours = (float) $data['duration_hours'];
                }

                $startTimeVal = $data['start_time'];
                $endTimeVal = $data['end_time'];
            }

            $mealAllowance = isset($data['meal_allowance']) ? (float) $data['meal_allowance'] : 0;
            $baseOvertimePay = $isDaily ? $rate : ($durationHours * $rate);
            $totalAmount = round($baseOvertimePay + $mealAllowance, 2);

            $isAdmin = $actor->hasRole('admin');
            $source = $isAdmin ? OvertimeSubmissionSource::ADMIN : OvertimeSubmissionSource::KARYAWAN;
            $status = $isAdmin ? OvertimeStatus::PENDING_DIREKTUR : OvertimeStatus::PENDING_ADMIN;

            $submission = OvertimeSubmission::create([
                'reference_number' => $this->generateSubmissionReference(),
                'employee_id' => $employee->id,
                'overtime_type' => $isDaily ? OvertimeType::DAILY : OvertimeType::HOURLY,
                'date' => $data['date'],
                'start_time' => $startTimeVal,
                'end_time' => $endTimeVal,
                'duration_hours' => $durationHours,
                'hourly_rate' => $rate,
                'meal_allowance' => $mealAllowance,
                'total_amount' => $totalAmount,
                'reason' => $data['reason'],
                'submission_source' => $source,
                'status' => $status,
                'admin_notes' => $isAdmin ? ($data['admin_notes'] ?? 'Diinput langsung oleh Admin') : null,
                'admin_adjusted_at' => $isAdmin ? now() : null,
                'submitted_by' => $actor->id,
                'verified_by_admin_id' => $isAdmin ? $actor->id : null,
            ]);

            $this->logger->info(
                action: 'OVERTIME_SUBMITTED',
                module: 'overtimes',
                message: "Pengajuan lembur {$submission->reference_number} dibuat oleh {$actor->name}",
                context: [
                    'submission_id' => $submission->id,
                    'reference_number' => $submission->reference_number,
                    'employee_id' => $employee->id,
                    'status' => $status->value,
                    'source' => $source->value,
                ]
            );

            return $submission;
        });
    }

    /**
     * Admin adjusts hours and meal allowance after discussion, then forwards to Direktur.
     */
    public function adjustAndForwardToDirector(OvertimeSubmission $submission, array $data, User $admin): OvertimeSubmission
    {
        return DB::transaction(function () use ($submission, $data, $admin) {
            $lockedSubmission = OvertimeSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            if ($lockedSubmission->status !== OvertimeStatus::PENDING_ADMIN) {
                throw new InvalidArgumentException('Hanya pengajuan berstatus Menunggu Verifikasi Admin yang dapat disesuaikan.');
            }

            $mealAllowance = isset($data['meal_allowance']) ? (float) $data['meal_allowance'] : (float) $lockedSubmission->meal_allowance;
            $rate = (float) $lockedSubmission->hourly_rate;

            if ($lockedSubmission->isDaily()) {
                $durationHours = 1.0;
                $startTimeStr = null;
                $endTimeStr = null;
                $totalAmount = round($rate + $mealAllowance, 2);
            } else {
                $startTime = Carbon::parse($data['start_time'] ?? $lockedSubmission->start_time);
                $endTime = Carbon::parse($data['end_time'] ?? $lockedSubmission->end_time);
                if ($startTime->equalTo($endTime)) {
                    throw new InvalidArgumentException('Jam selesai lembur tidak boleh sama dengan jam mulai.');
                }

                $subDate = $lockedSubmission->date instanceof Carbon ? $lockedSubmission->date->format('Y-m-d') : substr((string) $lockedSubmission->date, 0, 10);
                $overlapping = $this->checkSubmissionOverlap(
                    $lockedSubmission->employee_id,
                    $subDate,
                    $startTime->format('H:i'),
                    $endTime->format('H:i'),
                    $lockedSubmission->id
                );

                if ($overlapping) {
                    $overlapStart = $overlapping->start_time;
                    $overlapEnd = $overlapping->end_time;
                    throw new InvalidArgumentException("Penyesuaian jam lembur ({$startTime->format('H:i')} - {$endTime->format('H:i')}) bertabrakan dengan pengajuan lembur yang sudah ada ({$overlapping->reference_number}: {$overlapStart} - {$overlapEnd}).");
                }

                $durationHours = isset($data['duration_hours']) && (float) $data['duration_hours'] > 0
                    ? (float) $data['duration_hours']
                    : $this->calculateOvertimeDuration($startTime, $endTime);

                $startTimeStr = $startTime->format('H:i:s');
                $endTimeStr = $endTime->format('H:i:s');
                $totalAmount = round(($durationHours * $rate) + $mealAllowance, 2);
            }

            $lockedSubmission->update([
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
                'duration_hours' => $durationHours,
                'meal_allowance' => $mealAllowance,
                'total_amount' => $totalAmount,
                'admin_notes' => $data['admin_notes'] ?? $lockedSubmission->admin_notes,
                'admin_adjusted_at' => now(),
                'verified_by_admin_id' => $admin->id,
                'status' => OvertimeStatus::PENDING_DIREKTUR,
            ]);

            $this->logger->info(
                action: 'OVERTIME_VERIFIED_BY_ADMIN',
                module: 'overtimes',
                message: "Pengajuan lembur {$lockedSubmission->reference_number} diverifikasi admin {$admin->name} dan diajukan ke Direktur",
                context: [
                    'submission_id' => $lockedSubmission->id,
                    'reference_number' => $lockedSubmission->reference_number,
                    'duration_hours' => $durationHours,
                    'meal_allowance' => $mealAllowance,
                    'total_amount' => $totalAmount,
                ]
            );

            return $lockedSubmission;
        });
    }

    /**
     * Direktur approves final overtime submission.
     */
    public function approveByDirector(OvertimeSubmission $submission, User $director): OvertimeSubmission
    {
        return DB::transaction(function () use ($submission, $director) {
            $lockedSubmission = OvertimeSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            if ($lockedSubmission->status !== OvertimeStatus::PENDING_DIREKTUR) {
                throw new InvalidArgumentException('Hanya pengajuan berstatus Menunggu Approval Direktur yang dapat disetujui.');
            }

            $lockedSubmission->update([
                'status' => OvertimeStatus::APPROVED,
                'approved_by_direktur_id' => $director->id,
                'approved_at' => now(),
            ]);

            $this->logger->info(
                action: 'OVERTIME_APPROVED_BY_DIRECTOR',
                module: 'overtimes',
                message: "Pengajuan lembur {$lockedSubmission->reference_number} disetujui Direktur {$director->name}",
                context: [
                    'submission_id' => $lockedSubmission->id,
                    'reference_number' => $lockedSubmission->reference_number,
                    'approved_at' => now()->toIso8601String(),
                ]
            );

            return $lockedSubmission;
        });
    }

    /**
     * Reject overtime submission with mandatory reason.
     */
    public function rejectSubmission(OvertimeSubmission $submission, string $reason, User $actor): OvertimeSubmission
    {
        return DB::transaction(function () use ($submission, $reason, $actor) {
            if (empty(trim($reason))) {
                throw new InvalidArgumentException('Alasan penolakan lembur wajib diisi.');
            }

            $lockedSubmission = OvertimeSubmission::query()
                ->lockForUpdate()
                ->findOrFail($submission->id);

            $newStatus = $actor->hasRole('admin') ? OvertimeStatus::REJECTED_ADMIN : OvertimeStatus::REJECTED_DIREKTUR;

            $lockedSubmission->update([
                'status' => $newStatus,
                'rejection_reason' => trim($reason),
                'rejected_by_id' => $actor->id,
            ]);

            $this->logger->info(
                action: 'OVERTIME_REJECTED',
                module: 'overtimes',
                message: "Pengajuan lembur {$lockedSubmission->reference_number} ditolak oleh {$actor->name}. Alasan: {$reason}",
                context: [
                    'submission_id' => $lockedSubmission->id,
                    'reference_number' => $lockedSubmission->reference_number,
                    'rejection_reason' => $reason,
                    'rejected_by' => $actor->id,
                ]
            );

            return $lockedSubmission;
        });
    }

    /**
     * Delete an overtime submission (e.g. inputted by Admin and not locked in a period).
     */
    public function deleteSubmission(OvertimeSubmission|int $submission, User $actor): bool
    {
        return DB::transaction(function () use ($submission, $actor) {
            $subId = $submission instanceof OvertimeSubmission ? $submission->id : $submission;
            $sub = OvertimeSubmission::query()
                ->lockForUpdate()
                ->findOrFail($subId);

            if ($sub->overtime_period_id !== null) {
                throw new InvalidArgumentException('Pengajuan lembur ini sudah masuk ke dalam periode lembur mingguan dan tidak dapat dihapus.');
            }

            $ref = $sub->reference_number;
            $empName = $sub->employee?->full_name ?? 'Karyawan';
            $id = $sub->id;

            $sub->delete();

            $this->logger->info(
                action: 'OVERTIME_DELETED',
                module: 'overtimes',
                message: "Pengajuan lembur {$ref} ({$empName}) dihapus oleh {$actor->name}",
                context: [
                    'submission_id' => $id,
                    'reference_number' => $ref,
                    'deleted_by' => $actor->id,
                ]
            );

            return true;
        });
    }

    /**
     * Create weekly overtime period and group approved unassigned submissions.
     */
    public function createPeriod(array $data, User $actor): OvertimePeriod
    {
        return DB::transaction(function () use ($data, $actor) {
            $startDate = Carbon::parse($data['start_date'])->startOfDay();
            $endDate = Carbon::parse($data['end_date'])->endOfDay();

            if ($endDate->lessThan($startDate)) {
                throw new InvalidArgumentException('Tanggal akhir periode tidak boleh lebih awal dari tanggal mulai.');
            }

            $approvedSubmissions = OvertimeSubmission::query()
                ->where('status', OvertimeStatus::APPROVED)
                ->whereNull('overtime_period_id')
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->lockForUpdate()
                ->get();

            $linkedEmpIds = $approvedSubmissions->pluck('employee_id')->unique()->all();

            // Cek apakah data absensi log mesin sudah tersedia di rentang tanggal ini
            $attendances = \App\Models\Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->whereIn('status', ['hadir', 'terlambat', 'pulang_cepat'])
                ->get()
                ->groupBy('employee_id');

            $hasAttendanceLog = $attendances->isNotEmpty();
            $attendedEmpIds = $attendances->keys()->all();

            // Target karyawan mingguan:
            // 1. Pegawai Harian Lepas aktif (upah harian + uang makan)
            // 2. Karyawan yang memiliki lembur di periode ini
            // 3. Karyawan aktif (tetap/kontrak) yang memiliki catatan kehadiran di periode ini (berhak uang makan)
            $candidateEmployees = Employee::query()
                ->where('status', 'active')
                ->where(function ($q) use ($linkedEmpIds, $attendedEmpIds, $hasAttendanceLog) {
                    $q->where('employment_status', EmploymentStatus::HARIAN)
                      ->orWhereIn('id', $linkedEmpIds);
                    if ($hasAttendanceLog) {
                        $q->orWhereIn('id', $attendedEmpIds);
                    }
                })
                ->orderBy('full_name')
                ->get();

            if ($candidateEmployees->isEmpty()) {
                throw new InvalidArgumentException('Tidak ada data karyawan harian lepas, karyawan hadir, atau karyawan lembur aktif untuk periode ini.');
            }

            $period = OvertimePeriod::create([
                'reference_number' => $this->generatePeriodReference(),
                'name' => $data['name'] ?? sprintf('Gaji Mingguan W%s', $startDate->format('W')),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_hours' => $approvedSubmissions->sum('duration_hours'),
                'total_overtime_pay' => $approvedSubmissions->sum(fn ($sub) => (float) $sub->subtotal_overtime),
                'total_meal_allowance' => 0,
                'grand_total' => 0,
                'employee_count' => 0,
                'status' => OvertimePeriodStatus::DRAFT,
                'created_by' => $actor->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Link submissions to this period
            if ($approvedSubmissions->isNotEmpty()) {
                OvertimeSubmission::whereIn('id', $approvedSubmissions->pluck('id'))
                    ->update(['overtime_period_id' => $period->id]);
            }

            $dayKeyMap = [
                1 => 'mon', 2 => 'tue', 3 => 'wed', 4 => 'thu', 5 => 'fri', 6 => 'sat', 0 => 'sun',
            ];
            $defaultDaysCount = 6;
            $defaultDays = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

            foreach ($candidateEmployees as $emp) {
                $rate = $emp->getEffectiveMealAllowancePerDay();
                $isHarian = $emp->employment_status === EmploymentStatus::HARIAN;
                $dailyRate = $isHarian ? (float) ($emp->daily_rate ?? 0) : 0.0;
                $hasOvertime = in_array($emp->id, $linkedEmpIds, true);

                if ($hasAttendanceLog) {
                    $empAtts = $attendances->get($emp->id);
                    $attendedDaysKeys = [];
                    if ($empAtts && $empAtts->isNotEmpty()) {
                        foreach ($empAtts as $att) {
                            $dow = Carbon::parse($att->date)->dayOfWeek;
                            $k = $dayKeyMap[$dow] ?? null;
                            if ($k && ! in_array($k, $attendedDaysKeys, true)) {
                                $attendedDaysKeys[] = $k;
                            }
                        }
                    }
                    $attendedCount = count($attendedDaysKeys);

                    // Hanya buat record presensi bagi yang memiliki kehadiran (> 0 hari) atau lembur
                    if ($attendedCount <= 0 && ! $hasOvertime) {
                        continue;
                    }

                    $totalMeal = round($attendedCount * $rate, 2);
                    $totalDailyWage = round($attendedCount * $dailyRate, 2);

                    \App\Models\OvertimePeriodAttendance::create([
                        'overtime_period_id' => $period->id,
                        'employee_id' => $emp->id,
                        'meal_allowance_type' => $emp->meal_allowance_type ?: 'standar',
                        'meal_allowance_per_day' => $rate,
                        'daily_rate' => $dailyRate,
                        'attended_days' => $attendedCount,
                        'attendance_days' => $attendedDaysKeys,
                        'total_meal_allowance' => $totalMeal,
                        'total_daily_wage' => $totalDailyWage,
                    ]);
                } else {
                    // Jika log absensi belum diimpor, default hanya untuk karyawan harian aktif & lembur
                    $totalDailyWage = $defaultDaysCount * $dailyRate;
                    \App\Models\OvertimePeriodAttendance::create([
                        'overtime_period_id' => $period->id,
                        'employee_id' => $emp->id,
                        'meal_allowance_type' => $emp->meal_allowance_type ?: 'standar',
                        'meal_allowance_per_day' => $rate,
                        'daily_rate' => $dailyRate,
                        'attended_days' => $defaultDaysCount,
                        'attendance_days' => $defaultDays,
                        'total_meal_allowance' => $defaultDaysCount * $rate,
                        'total_daily_wage' => $totalDailyWage,
                    ]);
                }
            }

            $period->recalculateTotals();

            $this->logger->info(
                action: 'OVERTIME_PERIOD_CREATED',
                module: 'overtimes',
                message: "Periode lembur mingguan {$period->reference_number} dibuat dengan {$approvedSubmissions->count()} lembur",
                context: [
                    'period_id' => $period->id,
                    'reference_number' => $period->reference_number,
                    'total_submissions' => $approvedSubmissions->count(),
                    'grand_total' => $period->grand_total,
                ]
            );

            return $period;
        });
    }

    /**
     * Delete an overtime period (only if DRAFT).
     */
    public function deletePeriod(OvertimePeriod $period, User $actor): void
    {
        if ($period->status !== OvertimePeriodStatus::DRAFT) {
            throw new InvalidArgumentException('Hanya periode mingguan berstatus Draf (Belum Dibayar) yang dapat dihapus.');
        }

        DB::transaction(function () use ($period, $actor) {
            // Unassign linked overtime submissions so they are preserved
            $period->submissions()->update(['overtime_period_id' => null]);

            // Hapus file fisik bukti transfer dari storage agar tidak menjadi file sampah
            foreach ($period->attendances()->whereNotNull('payment_proof_path')->get() as $att) {
                if ($att->payment_proof_path && Storage::disk('public')->exists($att->payment_proof_path)) {
                    Storage::disk('public')->delete($att->payment_proof_path);
                }
            }

            // Delete attendances
            $period->attendances()->delete();

            $ref = $period->reference_number;
            $name = $period->name;
            $periodId = $period->id;

            $period->delete();

            $this->logger->warning(
                action: 'OVERTIME_PERIOD_DELETED',
                module: 'overtimes',
                message: "Periode gaji mingguan {$ref} ({$name}) telah dihapus",
                context: [
                    'period_id' => $periodId,
                    'reference_number' => $ref,
                    'actor_id' => $actor->id,
                ]
            );
        });
    }

    /**
     * Update attendance and meal allowance for an employee in a weekly period.
     */
    public function updatePeriodAttendance(OvertimePeriod $period, int $employeeId, array $data, User $actor, bool $recalculateTotals = true): \App\Models\OvertimePeriodAttendance
    {
        return DB::transaction(function () use ($period, $employeeId, $data, $actor, $recalculateTotals) {
            $attendance = \App\Models\OvertimePeriodAttendance::where('overtime_period_id', $period->id)
                ->where('employee_id', $employeeId)
                ->firstOrFail();

            $attendedDays = isset($data['attended_days']) ? (int) $data['attended_days'] : $attendance->attended_days;
            $attendanceDays = $data['attendance_days'] ?? $attendance->attendance_days;
            $rate = isset($data['meal_allowance_per_day']) ? (float) $data['meal_allowance_per_day'] : (float) $attendance->meal_allowance_per_day;
            $type = $data['meal_allowance_type'] ?? $attendance->meal_allowance_type;
            $totalMeal = round($attendedDays * $rate, 2);

            $employee = $attendance->employee ?? Employee::find($employeeId);
            $isHarian = $employee && $employee->employment_status === EmploymentStatus::HARIAN;
            $dailyRate = isset($data['daily_rate']) ? (float) $data['daily_rate'] : ($isHarian ? (float) ($employee->daily_rate ?? 0) : 0.0);
            $totalDailyWage = round($attendedDays * $dailyRate, 2);

            $attendance->update([
                'attended_days' => $attendedDays,
                'attendance_days' => $attendanceDays,
                'meal_allowance_type' => $type,
                'meal_allowance_per_day' => $rate,
                'total_meal_allowance' => $totalMeal,
                'daily_rate' => $dailyRate,
                'total_daily_wage' => $totalDailyWage,
                'notes' => $data['notes'] ?? $attendance->notes,
            ]);

            if ($recalculateTotals) {
                $period->recalculateTotals();
            }

            $this->logger->info(
                action: 'OVERTIME_ATTENDANCE_UPDATED',
                module: 'overtimes',
                message: "Kehadiran uang makan karyawan ID {$employeeId} pada periode {$period->reference_number} diperbarui: {$attendedDays} hari",
                context: [
                    'period_id' => $period->id,
                    'employee_id' => $employeeId,
                    'attended_days' => $attendedDays,
                    'total_meal_allowance' => $totalMeal,
                ]
            );

            return $attendance;
        });
    }

    /**
     * Mark an overtime period as paid.
     */
    public function markPeriodAsPaid(OvertimePeriod $period, User $actor): OvertimePeriod
    {
        return DB::transaction(function () use ($period, $actor) {
            $lockedPeriod = OvertimePeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if ($lockedPeriod->status === OvertimePeriodStatus::PAID) {
                throw new InvalidArgumentException('Periode lembur ini sudah ditandai selesai dibayar.');
            }

            $lockedPeriod->update([
                'status' => OvertimePeriodStatus::PAID,
                'paid_by' => $actor->id,
                'paid_at' => now(),
            ]);

            // Sync all unpaid attendances in this period to paid
            $lockedPeriod->attendances()->where(function ($q) {
                $q->whereNull('payment_status')
                  ->orWhere('payment_status', '!=', 'paid');
            })->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'paid_by' => $actor->id,
                'payment_method' => 'cash',
            ]);

            $this->logger->info(
                action: 'OVERTIME_PERIOD_PAID',
                module: 'overtimes',
                message: "Periode lembur {$lockedPeriod->reference_number} ditandai selesai dibayar oleh {$actor->name}",
                context: [
                    'period_id' => $lockedPeriod->id,
                    'reference_number' => $lockedPeriod->reference_number,
                    'paid_at' => now()->toIso8601String(),
                ]
            );

            return $lockedPeriod;
        });
    }

    /**
     * Mark an individual employee's weekly attendance & wage as paid.
     */
    public function payEmployeeWeekly(OvertimePeriodAttendance $attendance, array $data, User $actor): OvertimePeriodAttendance
    {
        return DB::transaction(function () use ($attendance, $data, $actor) {
            $attendance->update([
                'payment_status' => 'paid',
                'paid_at' => $data['payment_date'] ?? now(),
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'payment_proof_path' => $data['payment_proof_path'] ?? $attendance->payment_proof_path,
                'payment_notes' => $data['payment_notes'] ?? null,
                'paid_by' => $actor->id,
            ]);

            $this->logger->info(
                action: 'OVERTIME_ATTENDANCE_PAID',
                module: 'overtimes',
                message: "Gaji mingguan karyawan {$attendance->employee?->full_name} berhasil ditandai dibayar oleh {$actor->name}",
                context: [
                    'period_id' => $attendance->overtime_period_id,
                    'employee_id' => $attendance->employee_id,
                    'payment_method' => $attendance->payment_method,
                    'paid_at' => $attendance->paid_at?->toIso8601String(),
                ]
            );

            return $attendance;
        });
    }

    /**
     * Update an individual employee's weekly payment details.
     */
    public function updateEmployeeWeeklyPayment(OvertimePeriodAttendance $attendance, array $data, User $actor): OvertimePeriodAttendance
    {
        return DB::transaction(function () use ($attendance, $data, $actor) {
            $updates = [
                'paid_at' => $data['payment_date'] ?? $attendance->paid_at,
                'payment_method' => $data['payment_method'] ?? $attendance->payment_method,
                'bank_name' => $data['bank_name'] ?? $attendance->bank_name,
                'bank_account_number' => $data['bank_account_number'] ?? $attendance->bank_account_number,
                'bank_account_name' => $data['bank_account_name'] ?? $attendance->bank_account_name,
                'payment_notes' => $data['payment_notes'] ?? $attendance->payment_notes,
            ];

            if (! empty($data['payment_proof_path'])) {
                $updates['payment_proof_path'] = $data['payment_proof_path'];
            }

            $attendance->update($updates);

            $this->logger->info(
                action: 'OVERTIME_ATTENDANCE_PAYMENT_UPDATED',
                module: 'overtimes',
                message: "Detail pembayaran gaji mingguan karyawan {$attendance->employee?->full_name} diperbarui oleh {$actor->name}",
                context: [
                    'period_id' => $attendance->overtime_period_id,
                    'employee_id' => $attendance->employee_id,
                ]
            );

            return $attendance;
        });
    }

    /**
     * Get monthly overtime totals already paid or approved for an employee.
     */
    public function getEmployeeMonthlyOvertimeSummary(int $employeeId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Submissions that are approved and fall within this month
        $submissions = OvertimeSubmission::query()
            ->where('employee_id', $employeeId)
            ->where('status', OvertimeStatus::APPROVED)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $totalHours = (float) $submissions->sum('duration_hours');
        $totalOvertimePay = (float) $submissions->sum(fn ($sub) => (float) $sub->duration_hours * (float) $sub->hourly_rate);

        // Jika tidak ada pengajuan manual tapi ada log jam lembur mesin di absensi
        if ($totalOvertimePay <= 0) {
            $emp = \App\Models\Employee::find($employeeId);
            $otRate = (float) ($emp?->overtime_rate_per_hour ?? 0);
            if ($otRate > 0) {
                $attMins = (int) \App\Models\Attendance::where('employee_id', $employeeId)
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->sum('overtime_minutes');
                if ($attMins > 0) {
                    $totalHours = round($attMins / 60, 2);
                    $totalOvertimePay = round(($attMins / 60) * $otRate, 2);
                }
            }
        }

        // Meal allowances from period attendances in this month
        $attendances = \App\Models\OvertimePeriodAttendance::query()
            ->where('employee_id', $employeeId)
            ->whereHas('overtimePeriod', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->get();

        $totalMealAllowance = (float) $attendances->sum('total_meal_allowance');
        if ($totalMealAllowance <= 0) {
            $totalMealAllowance = (float) $submissions->sum('meal_allowance');
        }

        $grandTotal = $totalOvertimePay + $totalMealAllowance;

        return [
            'count' => $submissions->count(),
            'total_hours' => $totalHours,
            'total_overtime_pay' => $totalOvertimePay,
            'total_meal_allowance' => $totalMealAllowance,
            'grand_total' => $grandTotal,
            'formatted_total_overtime_pay' => 'Rp ' . number_format($totalOvertimePay, 0, ',', '.'),
            'formatted_total_meal_allowance' => 'Rp ' . number_format($totalMealAllowance, 0, ',', '.'),
            'formatted_grand_total' => 'Rp ' . number_format($grandTotal, 0, ',', '.'),
        ];
    }

    /**
     * Get chart data for executive analytics card (Tren 6 bulan & Distribusi departemen).
     */
    public function getOvertimeChartData(?int $employeeId = null): array
    {
        $now = Carbon::now();
        $startDate = (clone $now)->subMonths(5)->startOfMonth()->toDateString();
        $endDate = (clone $now)->endOfMonth()->toDateString();

        $baseQuery = OvertimeSubmission::query()
            ->where('status', OvertimeStatus::APPROVED)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($employeeId) {
            $baseQuery->where('employee_id', $employeeId);
        }

        $allSubmissions = (clone $baseQuery)->with('employee.department')->get();

        // 1. Monthly trend (6 months)
        $monthCursor = (clone $now)->subMonths(5)->startOfMonth();
        $monthlyLabels = [];
        $monthlyHours = [];
        $monthlyPay = [];

        for ($i = 0; $i < 6; $i++) {
            $curStart = (clone $monthCursor)->startOfMonth();
            $curEnd = (clone $monthCursor)->endOfMonth();

            $monthSubs = $allSubmissions->filter(fn ($s) => $s->date >= $curStart && $s->date <= $curEnd);

            $monthlyLabels[] = $curStart->locale('id')->isoFormat('MMM');
            $monthlyHours[] = round((float) $monthSubs->sum('duration_hours'), 1);
            $monthlyPay[] = (float) $monthSubs->sum(fn ($s) => (float) $s->duration_hours * (float) $s->hourly_rate);

            $monthCursor->addMonth();
        }

        // 2. Department distribution
        $deptHours = [];
        $deptPay = [];
        foreach ($allSubmissions as $sub) {
            $deptName = $sub->employee?->department?->name ?: 'Tanpa Dept';
            $pay = (float) $sub->duration_hours * (float) $sub->hourly_rate;
            $deptHours[$deptName] = ($deptHours[$deptName] ?? 0) + (float) $sub->duration_hours;
            $deptPay[$deptName] = ($deptPay[$deptName] ?? 0) + $pay;
        }

        arsort($deptPay);
        $topDepts = array_slice($deptPay, 0, 5, true);
        $totalDeptPay = array_sum($deptPay);
        $totalDeptHours = array_sum($deptHours);

        $deptLabels = array_keys($topDepts);
        $deptData = array_values($topDepts);
        $deptColors = ['#0b52aa', '#0284c7', '#10b981', '#f59e0b', '#8b5cf6'];

        $departmentBreakdown = [];
        $maxDeptPay = max(array_values($topDepts) ?: [1]);
        foreach ($topDepts as $dName => $pAmount) {
            $departmentBreakdown[] = [
                'name' => $dName,
                'hours' => round($deptHours[$dName] ?? 0, 1),
                'cost' => $pAmount,
                'compact_cost' => 'Rp ' . (
                    $pAmount >= 1000000 
                        ? number_format($pAmount / 1000000, 1, ',', '.') . ' jt' 
                        : number_format($pAmount / 1000, 0, ',', '.') . ' rb'
                ),
                'bar_pct' => $maxDeptPay > 0 ? round(($pAmount / $maxDeptPay) * 100) : 0,
            ];
        }

        $totalEmployeesParticipated = $allSubmissions->pluck('employee_id')->unique()->count();
        $avgHoursPerPerson = $totalEmployeesParticipated > 0 ? round($totalDeptHours / $totalEmployeesParticipated, 1) : 0;
        $avgCostPerPerson = $totalEmployeesParticipated > 0 ? round($totalDeptPay / $totalEmployeesParticipated) : 0;

        return [
            'monthly' => [
                'labels' => $monthlyLabels,
                'hours' => $monthlyHours,
                'pay' => $monthlyPay,
            ],
            'department_chart' => [
                'has_data' => count($deptData) > 0 && array_sum($deptData) > 0,
                'labels' => !empty($deptLabels) ? $deptLabels : ['Belum ada lembur'],
                'data' => !empty($deptData) && array_sum($deptData) > 0 ? $deptData : [1],
                'colors' => !empty($deptData) && array_sum($deptData) > 0 ? array_slice($deptColors, 0, count($deptData)) : ['#e2e8f0'],
                'total_pay' => $totalDeptPay,
                'total_hours' => $totalDeptHours,
                'compact_total' => $totalDeptPay >= 1000000 
                    ? 'Rp ' . number_format($totalDeptPay / 1000000, 1, ',', '.') . ' jt' 
                    : 'Rp ' . number_format($totalDeptPay / 1000, 0, ',', '.') . ' rb',
            ],
            'metrics' => [
                'total_hours' => round($totalDeptHours, 1),
                'total_pay' => $totalDeptPay,
                'avg_hours_per_person' => $avgHoursPerPerson,
                'formatted_avg_cost' => 'Rp ' . number_format($avgCostPerPerson, 0, ',', '.'),
                'total_employees' => $totalEmployeesParticipated,
            ],
            'department_breakdown' => $departmentBreakdown,
        ];
    }
}
