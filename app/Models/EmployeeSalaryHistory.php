<?php

namespace App\Models;

use App\Enums\SalaryAdjustmentType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'previous_salary',
        'new_salary',
        'change_amount',
        'percentage_change',
        'effective_date',
        'type',
        'reason',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_salary' => 'decimal:2',
            'new_salary' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'percentage_change' => 'decimal:2',
            'effective_date' => 'date',
            'type' => SalaryAdjustmentType::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function formattedPreviousSalary(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->previous_salary ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedNewSalary(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->new_salary ?? 0), 0, ',', '.'),
        );
    }

    protected function formattedChangeAmount(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $change = (float) ($this->change_amount ?? 0);
                $prefix = $change > 0 ? '+Rp ' : ($change < 0 ? '-Rp ' : 'Rp ');

                return $prefix.number_format(abs($change), 0, ',', '.');
            },
        );
    }

    protected function formattedPercentageChange(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $pct = (float) ($this->percentage_change ?? 0);
                $prefix = $pct > 0 ? '+' : '';

                return $prefix.number_format($pct, 1, ',', '.').'%';
            },
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->type instanceof SalaryAdjustmentType
                ? $this->type->label()
                : (SalaryAdjustmentType::tryFrom((string) $this->type)?->label() ?? 'Penyesuaian'),
        );
    }

    protected function typeBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->type instanceof SalaryAdjustmentType
                ? $this->type->badgeClass()
                : (SalaryAdjustmentType::tryFrom((string) $this->type)?->badgeClass() ?? 'badge badge-light border text-dark'),
        );
    }

    public function isIncrease(): bool
    {
        return (float) $this->change_amount > 0;
    }

    public function isDecrease(): bool
    {
        return (float) $this->change_amount < 0;
    }
}
