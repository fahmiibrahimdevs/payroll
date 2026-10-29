<?php

namespace App\Livewire\Forms;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class EmployeeForm extends Form
{
    public ?Employee $employee = null;

    // Fingerprint
    public ?string $fingerprint_id = null;

    // Kepegawaian
    public ?int $department_id = null;

    public ?int $position_id = null;

    public string $full_name = '';

    public string $join_date = '';

    public ?string $contract_end_date = null;

    public string $employment_status = 'tetap';

    public string $status = 'active';

    public mixed $basic_salary = 0;

    public mixed $daily_rate = 0;

    public mixed $overtime_rate_per_hour = 0;

    public mixed $overtime_rate_per_day = 0;

    public string $meal_allowance_type = 'standar';

    public mixed $meal_allowance_per_day = 25000;

    public string $formatted_total_cash_receipt = 'Rp 0';

    public mixed $photo = null;

    // Detail Pribadi & Kontak
    public string $employee_code = '';

    public string $gender = 'male';

    public string $birth_place = '';

    public ?string $birth_date = null;

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $email_username = '';

    public string $role = 'karyawan';

    public string $password = '';

    public string $bank_name = '';

    public string $bank_account_number = '';

    public string $bank_account_name = '';

    public mixed $curriculum_vitae = null;

    // Informasi Pajak & BPJS
    public string $marital_status = 'tk';

    public int $dependents_count = 0;

    public string $tax_method = 'gross';

    public string $tax_recipient_type = '';

    public bool $has_npwp = false;

    public ?string $npwp_number = '';

    public ?string $nik = '';

    public ?string $bpjs_kesehatan_number = '';

    public ?string $bpjs_ketenagakerjaan_number = '';

    /**
     * Define the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $detailId = $this->employee?->detail?->id;

        return [
            'fingerprint_id' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'fingerprint_id')->ignore($this->employee?->id)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'join_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'basic_salary' => $this->employment_status === EmploymentStatus::HARIAN->value
                ? ['nullable', 'numeric', 'min:0']
                : ['required', 'numeric', 'min:0'],
            'daily_rate' => $this->employment_status === EmploymentStatus::HARIAN->value
                ? ['required', 'numeric', 'min:0']
                : ['nullable', 'numeric', 'min:0'],
            'overtime_rate_per_hour' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate_per_day' => ['nullable', 'numeric', 'min:0'],
            'meal_allowance_type' => ['nullable', Rule::in(['standar', 'khusus'])],
            'meal_allowance_per_day' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('employee_details', 'employee_code')->ignore($detailId)],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employee_details', 'email')->ignore($detailId),
                Rule::unique(User::class, 'email')->ignore($this->employee?->user_id),
            ],
            'email_username' => ['nullable', 'string', 'max:100'],
            'role' => ['required', 'string', 'in:karyawan,admin,direktur'],
            'password' => ['nullable', 'string', 'min:1', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'curriculum_vitae' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:3'],
            'tax_method' => ['nullable', Rule::enum(TaxMethod::class)],
            'tax_recipient_type' => [
                'required',
                Rule::enum(TaxRecipientType::class),
                ...($this->employment_status === EmploymentStatus::MAGANG->value
                    ? [Rule::in([TaxRecipientType::OutsideScope->value])]
                    : []),
                ...($this->employment_status === EmploymentStatus::TETAP->value
                    ? [Rule::in([TaxRecipientType::MonthlyEmployee->value])]
                    : []),
            ],
            'has_npwp' => ['nullable', 'boolean'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'nik' => ['nullable', 'string', 'max:50'],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:50'],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'fingerprint_id' => 'ID Mesin Fingerprint / PIN',
            'department_id' => 'Department',
            'position_id' => 'Jabatan',
            'full_name' => 'Nama Lengkap',
            'join_date' => 'Tanggal Bergabung',
            'contract_end_date' => 'Tanggal Akhir Kontrak',
            'employment_status' => 'Status Kepegawaian',
            'status' => 'Status Akun',
            'basic_salary' => 'Gaji Pokok',
            'daily_rate' => 'Upah Harian',
            'overtime_rate_per_hour' => 'Nominal Lembur per Jam',
            'overtime_rate_per_day' => 'Nominal Lembur Harian',
            'meal_allowance_type' => 'Tipe Uang Makan',
            'meal_allowance_per_day' => 'Nominal Uang Makan',
            'photo' => 'Foto Karyawan',
            'employee_code' => 'Kode Karyawan',
            'gender' => 'Jenis Kelamin',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir',
            'address' => 'Alamat Domisili',
            'phone' => 'No. Telepon / WhatsApp',
            'email' => 'Alamat Email',
            'email_username' => 'Username Email',
            'role' => 'Role Akun Login',
            'password' => 'Password Akun',
            'bank_name' => 'Nama Bank',
            'bank_account_number' => 'Nomor Rekening',
            'bank_account_name' => 'Nama Pemilik Rekening',
            'curriculum_vitae' => 'File CV / Resume',
            'marital_status' => 'Status Pernikahan',
            'dependents_count' => 'Jumlah Tanggungan',
            'tax_method' => 'Metode Pajak',
            'tax_recipient_type' => 'Jenis Penerima Penghasilan untuk Pajak',
            'npwp_number' => 'Nomor NPWP',
            'nik' => 'NIK',
            'bpjs_kesehatan_number' => 'Nomor BPJS Kesehatan',
            'bpjs_ketenagakerjaan_number' => 'Nomor BPJS Ketenagakerjaan',
        ];
    }

    /**
     * Populate form fields from an existing Employee model for editing.
     */
    public function setEmployee(Employee $employee): void
    {
        $this->resetForm();
        $employee->loadMissing(['detail', 'user.roles']);
        $this->employee = $employee;
        $this->fingerprint_id = $employee->fingerprint_id;
        $this->department_id = $employee->department_id;
        $this->position_id = $employee->position_id;
        $this->full_name = $employee->full_name;
        $this->join_date = $employee->join_date?->format('Y-m-d') ?? '';
        $this->contract_end_date = $employee->contract_end_date?->format('Y-m-d');
        $this->employment_status = $employee->employment_status instanceof EmploymentStatus
            ? $employee->employment_status->value
            : (string) $employee->employment_status;
        $this->status = $employee->status instanceof EmployeeStatus
            ? $employee->status->value
            : (string) $employee->status;
        $this->basic_salary = (float) $employee->basic_salary;
        $this->daily_rate = (float) ($employee->daily_rate ?? 0);
        $this->overtime_rate_per_hour = (float) ($employee->overtime_rate_per_hour ?? 0);
        $this->overtime_rate_per_day = (float) ($employee->overtime_rate_per_day ?? 0);
        $this->meal_allowance_type = $employee->meal_allowance_type ?: 'standar';
        $this->meal_allowance_per_day = (float) ($employee->meal_allowance_per_day ?? 25000);
        $this->formatted_total_cash_receipt = $employee->formatted_total_cash_receipt;
        $this->photo = null;

        $detail = $employee->detail;
        $this->employee_code = $detail?->employee_code ?? '';
        $this->gender = $detail?->gender instanceof Gender
            ? $detail->gender->value
            : (string) ($detail?->gender ?? 'male');
        $this->birth_place = $detail?->birth_place ?? '';
        $this->birth_date = $detail?->birth_date?->format('Y-m-d');
        $this->address = $detail?->address ?? '';
        $this->phone = $detail?->phone ?? '';
        $this->email = $detail?->email ?? '';
        if (! empty($this->email) && str_contains($this->email, '@')) {
            $this->email_username = Str::before($this->email, '@');
        } else {
            $this->email_username = $this->email;
        }
        $this->role = $employee->user?->roles->first()?->name ?? 'karyawan';
        $this->password = '';
        $this->bank_name = $detail?->bank_name ?? '';
        $this->bank_account_number = $detail?->bank_account_number ?? '';
        $this->bank_account_name = $detail?->bank_account_name ?? '';
        $this->curriculum_vitae = null;
        $this->marital_status = $detail?->marital_status instanceof MaritalStatus ? $detail->marital_status->value : (string) ($detail?->marital_status ?? 'tk');
        $this->dependents_count = (int) ($detail?->dependents_count ?? 0);
        $this->tax_method = $detail?->tax_method instanceof TaxMethod ? $detail->tax_method->value : (string) ($detail?->tax_method ?? 'gross');
        if ($employee->employment_status === EmploymentStatus::MAGANG) {
            $this->tax_recipient_type = TaxRecipientType::OutsideScope->value;
        } elseif ($employee->employment_status === EmploymentStatus::TETAP) {
            $this->tax_recipient_type = TaxRecipientType::MonthlyEmployee->value;
        } else {
            $this->tax_recipient_type = $detail?->tax_recipient_type?->value ?? TaxRecipientType::MonthlyEmployee->value;
        }
        $this->has_npwp = (bool) ($detail?->has_npwp ?? false);
        $this->npwp_number = $detail?->npwp_number ?? '';
        $this->nik = $detail?->nik ?? '';
        $this->bpjs_kesehatan_number = $detail?->bpjs_kesehatan_number ?? '';
        $this->bpjs_ketenagakerjaan_number = $detail?->bpjs_ketenagakerjaan_number ?? '';

        $this->resetValidation();
    }

    /**
     * Reset form fields to clean defaults.
     */
    public function resetForm(): void
    {
        $this->reset([
            'employee',
            'fingerprint_id',
            'department_id',
            'position_id',
            'full_name',
            'join_date',
            'contract_end_date',
            'employment_status',
            'status',
            'basic_salary',
            'daily_rate',
            'formatted_total_cash_receipt',
            'photo',
            'employee_code',
            'gender',
            'birth_place',
            'birth_date',
            'address',
            'phone',
            'email',
            'email_username',
            'role',
            'password',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'curriculum_vitae',
            'marital_status',
            'dependents_count',
            'tax_method',
            'tax_recipient_type',
            'has_npwp',
            'npwp_number',
            'nik',
            'bpjs_kesehatan_number',
            'bpjs_ketenagakerjaan_number',
        ]);
        $this->employment_status = 'tetap';
        $this->status = 'active';
        $this->role = 'karyawan';
        $this->password = '';
        $this->email_username = '';
        $this->gender = 'male';
        $this->join_date = date('Y-m-d');
        $this->basic_salary = 0;
        $this->daily_rate = 0;
        $this->overtime_rate_per_hour = 0;
        $this->overtime_rate_per_day = 0;
        $this->meal_allowance_type = 'standar';
        $this->meal_allowance_per_day = 25000;
        $this->formatted_total_cash_receipt = 'Rp 0';
        $this->marital_status = 'tk';
        $this->dependents_count = 0;
        $this->tax_method = 'gross';
        $this->tax_recipient_type = TaxRecipientType::MonthlyEmployee->value;
        $this->has_npwp = false;
        $this->npwp_number = '';
        $this->nik = '';
        $this->bpjs_kesehatan_number = '';
        $this->bpjs_ketenagakerjaan_number = '';
        $this->resetValidation();
    }

    /**
     * Auto-sync tax recipient type when employment status changes.
     */
    public function updatedEmploymentStatus(string $status): void
    {
        if ($status === EmploymentStatus::MAGANG->value) {
            $this->tax_recipient_type = TaxRecipientType::OutsideScope->value;
        } elseif ($status === EmploymentStatus::TETAP->value) {
            $this->tax_recipient_type = TaxRecipientType::MonthlyEmployee->value;
        }
    }

    /**
     * Validate and create a new employee using EmployeeService.
     */
    public function store(EmployeeService $employeeService): Employee
    {
        $companyDomain = CompanyProfile::getProfile()->getEmailDomain();
        if (! empty(trim($this->email))) {
            if (! str_contains($this->email, '@')) {
                $cleanUsername = ltrim(trim($this->email));
                $this->email = strtolower($cleanUsername.'@'.$companyDomain);
                $this->email_username = $cleanUsername;
            } else {
                $this->email_username = Str::before($this->email, '@');
            }
        } elseif (! empty(trim($this->email_username))) {
            $cleanUsername = ltrim(trim(Str::before($this->email_username, '@')));
            $this->email_username = $cleanUsername;
            $this->email = strtolower($cleanUsername.'@'.$companyDomain);
        } elseif (! empty($this->full_name)) {
            $this->email = CompanyProfile::generateEmployeeEmail($this->full_name);
            $this->email_username = Str::before($this->email, '@');
        }

        $validated = $this->validate();
        $validated['role'] = $this->role ?: 'karyawan';
        $validated['password'] = $this->password ?: '1';

        $companyProfile = CompanyProfile::getProfile();
        $isUniform = (bool) ($companyProfile->is_uniform_meal_allowance ?? true);
        $defaultMealRate = (float) ($companyProfile->default_meal_allowance_per_day ?? 25000);

        if ($isUniform) {
            $validated['meal_allowance_per_day'] = $defaultMealRate;
            $validated['meal_allowance_type'] = 'standar';
        } else {
            $empRate = isset($validated['meal_allowance_per_day']) ? (float) $validated['meal_allowance_per_day'] : $defaultMealRate;
            $validated['meal_allowance_per_day'] = $empRate;
            $validated['meal_allowance_type'] = (abs($empRate - $defaultMealRate) < 0.01) ? 'standar' : 'khusus';
        }

        $photoFile = $this->photo instanceof TemporaryUploadedFile ? $this->photo : null;
        $cvFile = $this->curriculum_vitae instanceof TemporaryUploadedFile ? $this->curriculum_vitae : null;

        $employee = $employeeService->createEmployee($validated, $photoFile, $cvFile);
        $this->resetForm();

        return $employee;
    }

    /**
     * Validate and update an existing employee using EmployeeService.
     */
    public function update(EmployeeService $employeeService): Employee
    {
        $companyDomain = CompanyProfile::getProfile()->getEmailDomain();
        $currentEmailUsername = str_contains($this->email, '@') ? Str::before($this->email, '@') : $this->email;

        if (! empty(trim($this->email_username)) && $this->email_username !== $currentEmailUsername) {
            $cleanUsername = ltrim(trim(Str::before($this->email_username, '@')));
            $this->email_username = $cleanUsername;
            $this->email = strtolower($cleanUsername.'@'.$companyDomain);
        } elseif (! empty(trim($this->email))) {
            if (! str_contains($this->email, '@')) {
                $this->email = strtolower(trim($this->email).'@'.$companyDomain);
            }
            $this->email_username = Str::before($this->email, '@');
        }

        $validated = $this->validate();
        $validated['role'] = $this->role ?: 'karyawan';
        if (! empty($this->password)) {
            $validated['password'] = $this->password;
        }

        $companyProfile = CompanyProfile::getProfile();
        $isUniform = (bool) ($companyProfile->is_uniform_meal_allowance ?? true);
        $defaultMealRate = (float) ($companyProfile->default_meal_allowance_per_day ?? 25000);

        if ($isUniform) {
            $validated['meal_allowance_per_day'] = $defaultMealRate;
            $validated['meal_allowance_type'] = 'standar';
        } else {
            $empRate = isset($validated['meal_allowance_per_day']) ? (float) $validated['meal_allowance_per_day'] : $defaultMealRate;
            $validated['meal_allowance_per_day'] = $empRate;
            $validated['meal_allowance_type'] = (abs($empRate - $defaultMealRate) < 0.01) ? 'standar' : 'khusus';
        }

        $photoFile = $this->photo instanceof TemporaryUploadedFile ? $this->photo : null;
        $cvFile = $this->curriculum_vitae instanceof TemporaryUploadedFile ? $this->curriculum_vitae : null;

        $employee = $employeeService->updateEmployee($this->employee, $validated, $photoFile, $cvFile);
        $this->resetForm();

        return $employee;
    }
}
