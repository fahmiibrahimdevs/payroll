<?php

namespace App\Models;

use App\Enums\PayrollPeriodStatus;
use App\Enums\PtkpStatus;
use App\Enums\TaxMethod;
use App\Enums\TaxRecipientType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'payslip_number',
        'basic_salary',
        'total_allowances',
        'total_deductions',
        'net_salary',
        'tax_method',
        'tax_recipient_type',
        'is_final_tax_period',
        'ptkp_status',
        'ter_category',
        'ter_rate',
        'taxable_gross',
        'tax_allowance',
        'pph21_amount',
        'total_company_contributions',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'payment_status',
        'paid_at',
        'payment_method',
        'payment_proof_path',
        'payment_notes',
        'paid_by',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'notes',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'tax_method' => TaxMethod::class,
            'tax_recipient_type' => TaxRecipientType::class,
            'is_final_tax_period' => 'boolean',
            'ptkp_status' => PtkpStatus::class,
            'ter_rate' => 'decimal:4',
            'taxable_gross' => 'decimal:2',
            'tax_allowance' => 'decimal:2',
            'pph21_amount' => 'decimal:2',
            'total_company_contributions' => 'decimal:2',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return ! is_null($this->verified_at);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isOfficiallyApproved(): bool
    {
        if ($this->isApproved() || $this->approved_at !== null) {
            return true;
        }

        return in_array($this->payrollPeriod?->status, [PayrollPeriodStatus::APPROVED, PayrollPeriodStatus::PAID], true);
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending' || empty($this->approval_status);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollItemLine::class);
    }

    public function allowanceLines(): HasMany
    {
        return $this->hasMany(PayrollItemLine::class)->where('type', 'allowance');
    }

    public function deductionLines(): HasMany
    {
        return $this->hasMany(PayrollItemLine::class)->where('type', 'deduction');
    }

    public function companyContributionLines(): HasMany
    {
        return $this->hasMany(PayrollItemLine::class)->where('type', 'company_contribution');
    }

    protected function formattedTotalCompanyContributions(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_company_contributions, 0, ',', '.')
        );
    }

    protected function costToCompany(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->basic_salary + (float) $this->total_allowances + (float) $this->total_company_contributions
        );
    }

    protected function formattedCostToCompany(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->cost_to_company, 0, ',', '.')
        );
    }

    protected function grossSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->basic_salary + (float) $this->total_allowances
        );
    }

    protected function formattedGrossSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->gross_salary, 0, ',', '.')
        );
    }

    protected function formattedNetSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->net_salary, 0, ',', '.')
        );
    }

    protected function formattedBasicSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->basic_salary, 0, ',', '.')
        );
    }

    protected function formattedTotalAllowances(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_allowances, 0, ',', '.')
        );
    }

    protected function formattedTotalDeductions(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_deductions, 0, ',', '.')
        );
    }

    protected function formattedPph21Amount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->pph21_amount, 0, ',', '.')
        );
    }

    protected function formattedTaxAllowance(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->tax_allowance, 0, ',', '.')
        );
    }

    protected function paymentStatusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_status === 'paid' ? 'badge badge-info' : 'badge badge-warning'
        );
    }

    protected function paymentStatusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_status === 'paid' ? 'Dibayar' : 'Belum Dibayar'
        );
    }

    protected function approvalStatusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->approval_status) {
                    'approved' => 'badge badge-success',
                    'rejected' => 'badge badge-danger',
                    default => 'badge badge-warning',
                };
            }
        );
    }

    protected function approvalStatusLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->approval_status) {
                    'approved' => 'Disetujui (ACC)',
                    'rejected' => 'Ditolak / Revisi',
                    default => 'Menunggu ACC',
                };
            }
        );
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    protected function paymentProofUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_proof_path ? asset('storage/'.$this->payment_proof_path) : null
        );
    }

    protected function paymentMethodLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->payment_method)) {
                    return '-';
                }

                return match ($this->payment_method) {
                    'bank_transfer' => 'Transfer Bank / E-Wallet',
                    'cash' => 'Tunai / Cash',
                    'ewallet' => 'Transfer Bank / E-Wallet',
                    default => ucfirst(str_replace('_', ' ', (string) $this->payment_method)),
                };
            }
        );
    }

    protected function payrollStatusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isPaid()) {
                    return 'badge badge-info';
                }

                if ($this->isRejected()) {
                    return 'badge badge-danger';
                }

                if ($this->isApproved()) {
                    return 'badge badge-success';
                }

                $periodStatus = $this->relationLoaded('payrollPeriod') ? $this->payrollPeriod?->status?->value : null;
                if ($periodStatus === 'submitted') {
                    return 'badge badge-warning text-dark';
                }

                if ($this->isVerified()) {
                    return 'badge badge-info';
                }

                return 'badge badge-secondary';
            }
        );
    }

    protected function payrollStatusLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isPaid()) {
                    return 'Dibayar';
                }

                if ($this->isRejected()) {
                    return 'Ditolak';
                }

                if ($this->isApproved()) {
                    return 'Disetujui (ACC)';
                }

                $periodStatus = $this->relationLoaded('payrollPeriod') ? $this->payrollPeriod?->status?->value : null;
                if ($periodStatus === 'submitted') {
                    return 'Menunggu ACC';
                }

                if ($this->isVerified()) {
                    $name = $this->relationLoaded('verifier') ? ($this->verifier?->name ?? 'Admin') : ($this->verifier?->name ?? 'Admin');

                    return 'Verif HRD: '.$name;
                }

                return 'Draf';
            }
        );
    }

    protected function payrollStatusIcon(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isPaid()) {
                    return 'fas fa-check-circle';
                }

                if ($this->isRejected()) {
                    return 'fas fa-times-circle';
                }

                if ($this->isApproved()) {
                    return 'fas fa-check-double';
                }

                $periodStatus = $this->relationLoaded('payrollPeriod') ? $this->payrollPeriod?->status?->value : null;
                if ($periodStatus === 'submitted') {
                    return 'fas fa-clock';
                }

                if ($this->isVerified()) {
                    return 'fas fa-user-check';
                }

                return 'fas fa-pencil-alt';
            }
        );
    }

    protected function payrollStatusTooltip(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isPaid()) {
                    return 'Dibayar pada '.($this->paid_at ? $this->paid_at->format('d/m/Y H:i') : '-');
                }

                if ($this->isRejected()) {
                    return 'Alasan revisi: '.($this->rejection_reason ?: 'Perlu perbaikan');
                }

                if ($this->isApproved()) {
                    $approverName = $this->relationLoaded('approver') ? ($this->approver?->name ?? 'Direktur') : 'Direktur';

                    return 'Disetujui (ACC) oleh '.$approverName;
                }

                $periodStatus = $this->relationLoaded('payrollPeriod') ? $this->payrollPeriod?->status?->value : null;
                if ($periodStatus === 'submitted') {
                    $verifierName = $this->relationLoaded('verifier') ? ($this->verifier?->name ?? 'HRD') : 'HRD';

                    return 'Menunggu ACC Direktur (Diverifikasi oleh '.$verifierName.')';
                }

                if ($this->isVerified()) {
                    $verifierName = $this->relationLoaded('verifier') ? ($this->verifier?->name ?? 'HRD') : 'HRD';

                    return 'Diverifikasi oleh '.$verifierName.($this->verified_at ? ' pada '.$this->verified_at->format('d/m/Y H:i') : '');
                }

                return 'Draf - Belum diperiksa & diverifikasi oleh HRD';
            }
        );
    }

    protected function humanNotes(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (empty($this->notes)) {
                    return null;
                }

                $trimmed = trim((string) $this->notes);
                if (str_starts_with($trimmed, '{')) {
                    $meta = json_decode($trimmed, true);

                    return ! empty($meta['text_note']) ? (string) $meta['text_note'] : null;
                }

                return $this->notes;
            }
        );
    }
}
