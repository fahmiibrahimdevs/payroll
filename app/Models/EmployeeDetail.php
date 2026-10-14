<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\PtkpStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use Database\Factories\EmployeeDetailFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDetail extends Model
{
    /** @use HasFactory<EmployeeDetailFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
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
        'ptkp_status',
        'tax_method',
        'tax_recipient_type',
        'has_npwp',
        'npwp_number',
        'nik',
        'bpjs_kesehatan_number',
        'bpjs_ketenagakerjaan_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'birth_date' => 'date',
            'marital_status' => MaritalStatus::class,
            'dependents_count' => 'integer',
            'ptkp_status' => PtkpStatus::class,
            'tax_method' => TaxMethod::class,
            'tax_recipient_type' => TaxRecipientType::class,
            'has_npwp' => 'boolean',
        ];
    }

    /**
     * Bootstrap the model and its events.
     */
    protected static function booted(): void
    {
        static::saving(function (EmployeeDetail $detail) {
            $status = $detail->marital_status ?? MaritalStatus::TK;
            $dependents = (int) ($detail->dependents_count ?? 0);
            $detail->ptkp_status = PtkpStatus::fromMaritalAndDependents($status, $dependents);

            $detail->has_npwp = ! empty(trim((string) ($detail->npwp_number ?? '')));
        });
    }

    /**
     * Get the employee that owns this detail record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get CV download/view URL.
     */
    protected function cvUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->curriculum_vitae
                ? route('employees.documents.cv', ['employee' => $this->employee_id])
                : null,
        );
    }

    /**
     * Get gender display label.
     */
    protected function genderLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->gender instanceof Gender
                ? $this->gender->label()
                : ($this->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
        );
    }

    /**
     * Get TER category (A, B, or C).
     */
    protected function terCategory(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->ptkp_status instanceof PtkpStatus
                ? $this->ptkp_status->terCategory()
                : 'A',
        );
    }
}
