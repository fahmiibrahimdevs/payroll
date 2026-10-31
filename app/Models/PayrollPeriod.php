<?php

namespace App\Models;

use App\Enums\PayrollPeriodStatus;
use App\Services\DashboardService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'name',
        'month',
        'year',
        'start_date',
        'end_date',
        'payment_date',
        'status',
        'total_basic_salary',
        'total_allowances',
        'total_deductions',
        'total_net_salary',
        'total_company_contributions',
        'employee_count',
        'created_by',
        'updated_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollPeriodStatus::class,
            'month' => 'integer',
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'payment_date' => 'date',
            'total_basic_salary' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net_salary' => 'decimal:2',
            'total_company_contributions' => 'decimal:2',
            'employee_count' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof PayrollPeriodStatus
                ? $this->status->badgeClass()
                : 'badge badge-secondary'
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status instanceof PayrollPeriodStatus
                ? $this->status->label()
                : (string) $this->status
        );
    }

    protected function monthName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $months = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];

                return $months[$this->month] ?? (string) $this->month;
            }
        );
    }

    protected function periodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->month_name.' '.$this->year
        );
    }

    /** Tanggal dokumen slip: tanggal pembayaran, atau akhir periode, atau hari ini. */
    protected function documentDateLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_date
                ? $this->payment_date->locale('id')->translatedFormat('d F Y')
                : ($this->end_date
                    ? $this->end_date->locale('id')->translatedFormat('d F Y')
                    : now()->locale('id')->translatedFormat('d F Y'))
        );
    }

    /** Admin pengesah slip: submitter/creator/approver dengan role admin, atau fallback admin mana pun. */
    protected function signer(): Attribute
    {
        return Attribute::make(
            get: function () {
                $adminUser = null;
                if ($this->submitter && $this->submitter->hasRole('admin')) {
                    $adminUser = $this->submitter;
                } elseif ($this->creator && $this->creator->hasRole('admin')) {
                    $adminUser = $this->creator;
                } elseif ($this->approver && $this->approver->hasRole('admin')) {
                    $adminUser = $this->approver;
                } else {
                    $adminUser = User::whereNotIn('email', UserService::HIDDEN_DUMMY_EMAILS)
                        ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
                        ->first()
                        ?? $this->approver;
                }

                $adminEmployee = $adminUser?->employee;
                if (! $adminEmployee && $adminUser) {
                    $adminEmployee = Employee::where('user_id', $adminUser->id)->first()
                        ?? Employee::whereHas('detail', fn ($q) => $q->where('email', $adminUser->email))->first();
                }

                return [
                    'name' => $adminUser?->name ?? 'Admin Demo',
                    'job_title' => $adminEmployee?->position?->name
                        ?: ($adminUser?->hasRole('admin') ? 'HR Manager' : 'Direktur'),
                ];
            }
        );
    }

    protected function formattedTotalNetSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_net_salary, 0, ',', '.')
        );
    }

    protected function formattedTotalBasicSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_basic_salary, 0, ',', '.')
        );
    }

    protected function formattedTotalAllowances(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_allowances, 0, ',', '.')
        );
    }

    protected function totalGrossSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->total_basic_salary + (float) $this->total_allowances
        );
    }

    protected function formattedTotalGrossSalary(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_gross_salary, 0, ',', '.')
        );
    }

    protected function formattedTotalDeductions(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_deductions, 0, ',', '.')
        );
    }

    protected function formattedTotalCompanyContributions(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_company_contributions, 0, ',', '.')
        );
    }

    protected function totalCostToCompany(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->total_basic_salary + (float) $this->total_allowances + (float) $this->total_company_contributions
        );
    }

    protected function formattedTotalCostToCompany(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->total_cost_to_company, 0, ',', '.')
        );
    }

    protected static function booted(): void
    {
        static::saved(function (PayrollPeriod $period) {
            DashboardService::clearPayrollTrendCache($period->year);
        });
        static::deleted(function (PayrollPeriod $period) {
            DashboardService::clearPayrollTrendCache($period->year);
        });
    }
}
