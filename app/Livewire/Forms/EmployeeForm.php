<?php

namespace App\Livewire\Forms;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\TaxMethod;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class EmployeeForm extends Form
{
    public ?Employee $employee = null;

    // Kepegawaian
    public ?int $department_id = null;

    public ?int $position_id = null;

    public string $full_name = '';

    public string $join_date = '';

    public ?string $contract_end_date = null;

    public string $employment_status = 'tetap';

    public string $status = 'active';

    public mixed $basic_salary = 0;

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

    public string $bank_name = '';

    public string $bank_account_number = '';

    public string $bank_account_name = '';

    public mixed $curriculum_vitae = null;

    // Informasi Pajak & BPJS
    public string $marital_status = 'tk';

    public int $dependents_count = 0;

    public string $tax_method = 'gross';

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
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'join_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('employee_details', 'employee_code')->ignore($detailId)],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employee_details', 'email')->ignore($detailId)],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'curriculum_vitae' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'marital_status' => ['nullable', Rule::enum(MaritalStatus::class)],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:3'],
            'tax_method' => ['nullable', Rule::enum(TaxMethod::class)],
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
            'department_id' => 'Department',
            'position_id' => 'Jabatan',
            'full_name' => 'Nama Lengkap',
            'join_date' => 'Tanggal Bergabung',
            'contract_end_date' => 'Tanggal Akhir Kontrak',
            'employment_status' => 'Status Kepegawaian',
            'status' => 'Status Akun',
            'basic_salary' => 'Gaji Pokok',
            'photo' => 'Foto Karyawan',
            'employee_code' => 'Kode Karyawan',
            'gender' => 'Jenis Kelamin',
            'birth_place' => 'Tempat Lahir',
            'birth_date' => 'Tanggal Lahir',
            'address' => 'Alamat Domisili',
            'phone' => 'No. Telepon / WhatsApp',
            'email' => 'Alamat Email',
            'bank_name' => 'Nama Bank',
            'bank_account_number' => 'Nomor Rekening',
            'bank_account_name' => 'Nama Pemilik Rekening',
            'curriculum_vitae' => 'File CV / Resume',
            'marital_status' => 'Status Pernikahan',
            'dependents_count' => 'Jumlah Tanggungan',
            'tax_method' => 'Metode Pajak',
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
        $this->employee = $employee;
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
        $this->bank_name = $detail?->bank_name ?? '';
        $this->bank_account_number = $detail?->bank_account_number ?? '';
        $this->bank_account_name = $detail?->bank_account_name ?? '';
        $this->curriculum_vitae = null;
        $this->marital_status = $detail?->marital_status instanceof MaritalStatus ? $detail->marital_status->value : (string) ($detail?->marital_status ?? 'tk');
        $this->dependents_count = (int) ($detail?->dependents_count ?? 0);
        $this->tax_method = $detail?->tax_method instanceof TaxMethod ? $detail->tax_method->value : (string) ($detail?->tax_method ?? 'gross');
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
            'department_id',
            'position_id',
            'full_name',
            'join_date',
            'contract_end_date',
            'employment_status',
            'status',
            'basic_salary',
            'formatted_total_cash_receipt',
            'photo',
            'employee_code',
            'gender',
            'birth_place',
            'birth_date',
            'address',
            'phone',
            'email',
            'bank_name',
            'bank_account_number',
            'bank_account_name',
            'curriculum_vitae',
            'marital_status',
            'dependents_count',
            'tax_method',
            'has_npwp',
            'npwp_number',
            'nik',
            'bpjs_kesehatan_number',
            'bpjs_ketenagakerjaan_number',
        ]);
        $this->employment_status = 'tetap';
        $this->status = 'active';
        $this->gender = 'male';
        $this->join_date = date('Y-m-d');
        $this->basic_salary = 0;
        $this->formatted_total_cash_receipt = 'Rp 0';
        $this->marital_status = 'tk';
        $this->dependents_count = 0;
        $this->tax_method = 'gross';
        $this->has_npwp = false;
        $this->npwp_number = '';
        $this->nik = '';
        $this->bpjs_kesehatan_number = '';
        $this->bpjs_ketenagakerjaan_number = '';
        $this->resetValidation();
    }

    /**
     * Validate and create a new employee using EmployeeService.
     */
    public function store(EmployeeService $employeeService): Employee
    {
        $validated = $this->validate();

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
        $validated = $this->validate();

        $photoFile = $this->photo instanceof TemporaryUploadedFile ? $this->photo : null;
        $cvFile = $this->curriculum_vitae instanceof TemporaryUploadedFile ? $this->curriculum_vitae : null;

        $employee = $employeeService->updateEmployee($this->employee, $validated, $photoFile, $cvFile);
        $this->resetForm();

        return $employee;
    }
}
