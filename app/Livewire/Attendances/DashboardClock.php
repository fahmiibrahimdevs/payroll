<?php

namespace App\Livewire\Attendances;

use App\Models\Attendance;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\User;
use App\Services\AttendanceService;
use App\Support\WorkingDays;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardClock extends Component
{
    public ?Employee $employee = null;

    public ?Attendance $todayAttendance = null;

    public bool $hasClockedIn = false;

    public bool $hasClockedOut = false;

    public string $clockInTime = '--:--';

    public string $clockOutTime = '--:--';

    public bool $isOvertime = false;

    public bool $hasOvertimeIn = false;

    public bool $hasOvertimeOut = false;

    public string $overtimeInTime = '--:--';

    public string $overtimeOutTime = '--:--';

    public bool $isHolidayOrWeekend = false;

    public string $todayScheduleStatus = 'work';

    public string $clockType = 'clock_in';

    public string $notes = '';

    public array $allowedLocations = [];

    public bool $isFreeLocation = false;

    public function mount(AttendanceService $attendanceService): void
    {
        $this->loadEmployee();
        $this->loadAttendanceState($attendanceService);
    }

    public function loadEmployee(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->employee = $user->getEmployeeRecord();

        // Fallback untuk kemudahan testing jika login sebagai Admin/Direktur di mode lokal
        if (! $this->employee && app()->environment(['local', 'testing'])) {
            $this->employee = Employee::first();
        }

        if ($this->employee) {
            $locations = $this->employee->getValidWorkLocationsOnDate(now()->toDateString());
            if ($locations === null) {
                $this->isFreeLocation = true;
                $this->allowedLocations = [];
            } else {
                $this->isFreeLocation = false;
                $this->allowedLocations = $locations->map(fn ($loc) => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'radius_meters' => (int) $loc->radius_meters,
                ])->values()->toArray();
            }
        }
    }

    public function loadAttendanceState(?AttendanceService $attendanceService = null): void
    {
        if (! $this->employee) {
            return;
        }

        $attendanceService = $attendanceService ?? app(AttendanceService::class);
        $attendanceService->processEmployeeOvertimeAutoCutoff($this->employee);

        $todayStr = now()->toDateString();
        $schedule = EmployeeSchedule::where('employee_id', $this->employee->id)
            ->where('date', $todayStr)
            ->first();

        if ($schedule) {
            $this->todayScheduleStatus = $schedule->status;
            $this->isHolidayOrWeekend = ($schedule->status === EmployeeSchedule::STATUS_OFF);
        } else {
            $company = CompanyProfile::getProfile();
            $workDaysPerWeek = (int) ($company->work_days_per_week ?: 6);
            $isWeekend = WorkingDays::isWeekend(now(), $workDaysPerWeek);
            $isHoliday = Holiday::where('date', $todayStr)->exists();
            $this->isHolidayOrWeekend = $isWeekend || $isHoliday;
            $this->todayScheduleStatus = $this->isHolidayOrWeekend ? EmployeeSchedule::STATUS_OFF : EmployeeSchedule::STATUS_WORK;
        }

        $this->todayAttendance = Attendance::query()
            ->with(['clockInLocation', 'clockOutLocation', 'overtimeInLocation', 'overtimeOutLocation', 'overtimeSubmission'])
            ->where('employee_id', $this->employee->id)
            ->where('date', now()->toDateString())
            ->first();

        $this->hasClockedIn = ! empty($this->todayAttendance?->clock_in);
        $this->hasClockedOut = ! empty($this->todayAttendance?->clock_out);

        $this->clockInTime = $this->hasClockedIn
            ? Carbon::parse($this->todayAttendance->clock_in)->format('H:i').' WIB'
            : '--:--';

        $this->clockOutTime = $this->hasClockedOut
            ? Carbon::parse($this->todayAttendance->clock_out)->format('H:i').' WIB'
            : '--:--';

        // Sinkronisasi status lembur
        if ($this->todayAttendance?->is_overtime) {
            $this->isOvertime = true;
        }

        $this->hasOvertimeIn = ! empty($this->todayAttendance?->overtime_in);
        $this->hasOvertimeOut = ! empty($this->todayAttendance?->overtime_out);

        $this->overtimeInTime = $this->hasOvertimeIn
            ? Carbon::parse($this->todayAttendance->overtime_in)->format('H:i').' WIB'
            : '--:--';

        $this->overtimeOutTime = $this->hasOvertimeOut
            ? Carbon::parse($this->todayAttendance->overtime_out)->format('H:i').' WIB'
            : '--:--';
    }

    /**
     * Buka modal kamera & GPS untuk absen masuk, pulang, atau lembur.
     */
    public function openClockModal(string $type): void
    {
        $validTypes = ['clock_in', 'clock_out', 'overtime_in', 'overtime_out'];
        $type = in_array($type, $validTypes, true) ? $type : 'clock_in';
        $this->clockType = $type;
        $this->notes = '';

        // Validasi alur jam kerja normal
        if ($type === 'clock_out' && ! $this->hasClockedIn) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Belum Absen Masuk',
                'message' => 'Anda harus melakukan presensi masuk terlebih dahulu sebelum dapat melakukan presensi pulang.',
            ]);

            return;
        }

        // Validasi alur lembur:
        // Pada hari kerja biasa: Karyawan WAJIB sudah Absen Pulang kerja normal terlebih dahulu!
        if ($type === 'overtime_in' && ! $this->isHolidayOrWeekend && ! $this->hasClockedOut) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Wajib Absen Pulang Terlebih Dahulu',
                'message' => 'Anda harus menyelesaikan presensi pulang kerja reguler terlebih dahulu sebelum memulai presensi lembur.',
            ]);

            return;
        }

        if ($type === 'overtime_out' && ! $this->hasOvertimeIn) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Belum Mulai Lembur',
                'message' => 'Anda belum melakukan presensi mulai lembur.',
            ]);

            return;
        }

        $isReadOnly = ($type === 'clock_in' && $this->hasClockedIn)
            || ($type === 'clock_out' && $this->hasClockedOut)
            || ($type === 'overtime_in' && $this->hasOvertimeIn)
            || ($type === 'overtime_out' && $this->hasOvertimeOut);

        $readOnlyData = null;
        if ($isReadOnly && $this->todayAttendance) {
            $photoPath = match ($type) {
                'clock_in' => $this->todayAttendance->clock_in_photo_path,
                'clock_out' => $this->todayAttendance->clock_out_photo_path,
                'overtime_in' => $this->todayAttendance->overtime_in_photo_path,
                'overtime_out' => $this->todayAttendance->overtime_out_photo_path,
            };

            $photoUrl = $photoPath ? '/storage/'.ltrim($photoPath, '/') : null;

            $time = match ($type) {
                'clock_in' => $this->todayAttendance->clock_in,
                'clock_out' => $this->todayAttendance->clock_out,
                'overtime_in' => $this->todayAttendance->overtime_in,
                'overtime_out' => $this->todayAttendance->overtime_out,
            };
            $timeFormatted = $time ? Carbon::parse($time)->format('H:i:s').' WIB' : '-';

            $latitude = (float) match ($type) {
                'clock_in' => $this->todayAttendance->clock_in_latitude ?? 0,
                'clock_out' => $this->todayAttendance->clock_out_latitude ?? 0,
                'overtime_in' => $this->todayAttendance->overtime_in_latitude ?? 0,
                'overtime_out' => $this->todayAttendance->overtime_out_latitude ?? 0,
            };

            $longitude = (float) match ($type) {
                'clock_in' => $this->todayAttendance->clock_in_longitude ?? 0,
                'clock_out' => $this->todayAttendance->clock_out_longitude ?? 0,
                'overtime_in' => $this->todayAttendance->overtime_in_longitude ?? 0,
                'overtime_out' => $this->todayAttendance->overtime_out_longitude ?? 0,
            };

            $distance = match ($type) {
                'clock_in' => $this->todayAttendance->clock_in_distance_meters,
                'clock_out' => $this->todayAttendance->clock_out_distance_meters,
                'overtime_in' => $this->todayAttendance->overtime_in_distance_meters,
                'overtime_out' => $this->todayAttendance->overtime_out_distance_meters,
            };

            $location = match ($type) {
                'clock_in' => $this->todayAttendance->clockInLocation,
                'clock_out' => $this->todayAttendance->clockOutLocation,
                'overtime_in' => $this->todayAttendance->overtimeInLocation,
                'overtime_out' => $this->todayAttendance->overtimeOutLocation,
            };

            $locationName = $location?->name ?? ($this->isFreeLocation ? 'Bebas Lokasi' : 'Kantor Resmi');
            $allowedRadius = $location?->radius_meters ?? 50;
            $isInside = $this->isFreeLocation || ($distance !== null && $distance <= $allowedRadius);

            $readOnlyData = [
                'photoUrl' => $photoUrl,
                'time' => $timeFormatted,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance' => $distance,
                'locationName' => $locationName,
                'allowedRadius' => $allowedRadius,
                'isInside' => $isInside,
                'reviewStatus' => $this->todayAttendance->review_status,
                'notes' => $this->todayAttendance->notes,
                'employeeName' => $this->employee?->full_name,
            ];
        }

        $this->dispatch('open-web-clock-modal', [
            'type' => $this->clockType,
            'isReadOnly' => $isReadOnly,
            'readOnlyData' => $readOnlyData,
            'isFreeLocation' => $this->isFreeLocation,
            'allowedLocations' => $this->allowedLocations,
        ]);
    }

    /**
     * Simpan presensi masuk / pulang / lembur dari modal kamera & GPS.
     */
    public function recordClock(
        string $type,
        ?string $photoBase64,
        float $latitude,
        float $longitude,
        ?string $notes,
        ?string $deviceInfo,
        AttendanceService $attendanceService
    ): void {
        if (! $this->employee) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Presensi',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ]);

            return;
        }

        $validTypes = ['clock_in', 'clock_out', 'overtime_in', 'overtime_out'];
        $type = in_array($type, $validTypes, true) ? $type : 'clock_in';

        // Validasi: tidak boleh absen masuk 2x
        if ($type === 'clock_in' && $this->hasClockedIn) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Presensi Sudah Tercatat',
                'message' => 'Anda sudah melakukan presensi masuk untuk hari ini.',
            ]);

            return;
        }

        // Validasi: tidak boleh absen pulang 2x
        if ($type === 'clock_out' && $this->hasClockedOut) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Presensi Sudah Tercatat',
                'message' => 'Anda sudah melakukan presensi pulang untuk hari ini.',
            ]);

            return;
        }

        // Validasi: tidak boleh lembur mulai 2x
        if ($type === 'overtime_in' && $this->hasOvertimeIn) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Presensi Sudah Tercatat',
                'message' => 'Anda sudah melakukan presensi mulai lembur untuk hari ini.',
            ]);

            return;
        }

        // Validasi: tidak boleh lembur selesai 2x
        if ($type === 'overtime_out' && $this->hasOvertimeOut) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Presensi Sudah Tercatat',
                'message' => 'Anda sudah melakukan presensi selesai lembur untuk hari ini.',
            ]);

            return;
        }

        try {
            $attendance = $attendanceService->recordWebClock(
                employee: $this->employee,
                type: $type,
                photoBase64: $photoBase64,
                latitude: $latitude,
                longitude: $longitude,
                notes: $notes,
                deviceInfo: $deviceInfo,
                actor: Auth::user()
            );

            $this->loadAttendanceState($attendanceService);

            $typeLabel = match ($type) {
                'clock_in' => 'Masuk',
                'clock_out' => 'Pulang',
                'overtime_in' => 'Mulai Lembur',
                'overtime_out' => 'Selesai Lembur',
            };

            if ($attendance->isPendingReview()) {
                $this->dispatch('swal:alert', [
                    'type' => 'warning',
                    'title' => "Presensi {$typeLabel} Berhasil Dicatat (Perlu Review)",
                    'message' => 'Anda berada di luar radius kantor resmi. Catatan alasan Anda telah dikirimkan ke Admin untuk ditinjau.',
                ]);
            } else {
                $successMsg = match ($type) {
                    'overtime_in' => 'Presensi Mulai Lembur pada '.now()->format('H:i').' WIB berhasil dicatat. Selamat bertugas!',
                    'overtime_out' => 'Presensi Selesai Lembur pada '.now()->format('H:i').' WIB berhasil dicatat. Pengajuan lembur otomatis dikirimkan ke Admin.',
                    default => "Presensi {$typeLabel} Anda pada ".now()->format('H:i').' WIB berhasil tercatat valid.',
                };

                $this->dispatch('swal:alert', [
                    'type' => 'success',
                    'title' => "Presensi {$typeLabel} Berhasil!",
                    'message' => $successMsg,
                ]);
            }

            $this->dispatch('close-web-clock-modal');
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Menyimpan Presensi',
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.attendances.dashboard-clock', [
            'todayDateFormatted' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ]);
    }
}
