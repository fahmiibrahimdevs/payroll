<?php

namespace App\Livewire\Forms;

use App\Models\Employee;
use App\Models\OvertimeSubmission;
use App\Models\User;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Livewire\Form;

class OvertimeSubmissionForm extends Form
{
    public ?OvertimeSubmission $submission = null;

    public int|string|null $employee_id = '';

    public string $overtime_type = 'hourly';

    public string $date = '';

    public string $start_time = '';

    public string $end_time = '';

    public int|float|string|null $duration_hours = null;

    public int|float|string|null $meal_allowance = 0;

    public string $reason = '';

    public string $admin_notes = '';

    public function rules(): array
    {
        $rules = [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'overtime_type' => ['required', 'string', 'in:hourly,daily'],
            'date' => ['required', 'date'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->overtime_type === 'hourly') {
            $rules['start_time'] = ['required', 'date_format:H:i'];
            $rules['end_time'] = ['required', 'date_format:H:i', 'different:start_time'];
            $rules['duration_hours'] = ['nullable', 'numeric', 'min:0.25', 'max:24'];
        } else {
            $rules['start_time'] = ['nullable'];
            $rules['end_time'] = ['nullable'];
            $rules['duration_hours'] = ['nullable', 'numeric'];
        }

        return $rules;
    }

    public function validationAttributes(): array
    {
        return [
            'employee_id' => 'Karyawan',
            'overtime_type' => 'Jenis Lembur',
            'date' => 'Tanggal Lembur',
            'start_time' => 'Jam Mulai',
            'end_time' => 'Jam Selesai',
            'duration_hours' => 'Durasi Jam',
            'meal_allowance' => 'Uang Makan',
            'reason' => 'Keperluan Lembur',
            'admin_notes' => 'Catatan Admin',
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.different' => 'Jam selesai tidak boleh sama dengan jam mulai.',
        ];
    }

    public function isDaily(): bool
    {
        return $this->overtime_type === 'daily';
    }

    public function calculateEstimatedHours(): float
    {
        if ($this->isDaily()) {
            return 0;
        }

        if (empty($this->start_time) || empty($this->end_time)) {
            return 0;
        }

        try {
            return app(OvertimeService::class)->calculateOvertimeDuration($this->start_time, $this->end_time);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function resetForm(): void
    {
        $this->submission = null;
        $this->employee_id = '';
        $this->overtime_type = 'hourly';
        $this->date = date('Y-m-d');
        $this->start_time = '';
        $this->end_time = '';
        $this->duration_hours = null;
        $this->meal_allowance = 0;
        $this->reason = '';
        $this->admin_notes = '';
        $this->resetValidation();
    }

    public function setSubmission(OvertimeSubmission $submission): void
    {
        $this->submission = $submission;
        $this->employee_id = $submission->employee_id;
        $this->overtime_type = $submission->overtime_type?->value ?? ($submission->overtime_type ?: 'hourly');
        $this->date = $submission->date->format('Y-m-d');
        $this->start_time = ! empty($submission->start_time) ? Carbon::parse($submission->start_time)->format('H:i') : '';
        $this->end_time = ! empty($submission->end_time) ? Carbon::parse($submission->end_time)->format('H:i') : '';
        $this->duration_hours = (float) $submission->duration_hours;
        $this->meal_allowance = (float) $submission->meal_allowance;
        $this->reason = $submission->reason;
        $this->admin_notes = $submission->admin_notes ?? '';
        $this->resetValidation();
    }
}
