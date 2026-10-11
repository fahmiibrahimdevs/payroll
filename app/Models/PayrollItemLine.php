<?php

namespace App\Models;

use App\Enums\PayrollLineCategory;
use App\Enums\PayrollLineType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'salary_component_id',
        'cash_advance_installment_id',
        'name',
        'type',
        'category',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => PayrollLineType::class,
            'category' => PayrollLineCategory::class,
            'amount' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class, 'payroll_item_id');
    }

    public function payrollItem(): BelongsTo
    {
        return $this->item();
    }

    public function salaryComponent(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class);
    }

    public function cashAdvanceInstallment(): BelongsTo
    {
        return $this->belongsTo(CashAdvanceInstallment::class);
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'Rp '.number_format((float) $this->amount, 0, ',', '.')
        );
    }
}
