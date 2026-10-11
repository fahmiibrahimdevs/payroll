<?php

namespace App\Models;

use App\Enums\SalaryCalculationType;
use App\Enums\SalaryComponentCategory;
use App\Enums\SalaryComponentType;
use Database\Factories\SalaryComponentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryComponent extends Model
{
    /** @use HasFactory<SalaryComponentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'category',
        'type',
        'calculation_type',
        'default_value',
        'is_taxable',
        'is_system',
        'is_active',
        'sort_order',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => SalaryComponentCategory::class,
            'type' => SalaryComponentType::class,
            'calculation_type' => SalaryCalculationType::class,
            'default_value' => 'decimal:2',
            'is_taxable' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::saving(function (SalaryComponent $model) {
            if ($model->category instanceof SalaryComponentCategory) {
                $model->type = $model->category->type();
            } elseif (is_string($model->category)) {
                $categoryEnum = SalaryComponentCategory::tryFrom($model->category);
                if ($categoryEnum) {
                    $model->type = $categoryEnum->type();
                }
            }
        });
    }

    /**
     * Get category badge CSS class.
     */
    protected function categoryBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category instanceof SalaryComponentCategory
                ? $this->category->badgeClass()
                : (SalaryComponentCategory::tryFrom((string) $this->category)?->badgeClass() ?? 'badge badge-secondary')
        );
    }

    /**
     * Get category human-readable label.
     */
    protected function categoryLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->category instanceof SalaryComponentCategory) {
                    return $this->category->label();
                }
                $raw = is_scalar($this->category) ? (string) $this->category : '';

                return SalaryComponentCategory::tryFrom($raw)?->label() ?? $raw;
            }
        );
    }

    /**
     * Get calculation type label.
     */
    protected function calculationTypeLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->calculation_type instanceof SalaryCalculationType) {
                    return $this->calculation_type->label();
                }
                $raw = is_scalar($this->calculation_type) ? (string) $this->calculation_type : '';

                return SalaryCalculationType::tryFrom($raw)?->label() ?? $raw;
            }
        );
    }

    /**
     * Get calculation type badge CSS class.
     */
    protected function calculationTypeBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->calculation_type instanceof SalaryCalculationType
                ? $this->calculation_type->badgeClass()
                : 'badge badge-light border'
        );
    }

    /**
     * Get formatted default value display.
     */
    protected function defaultValueFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->default_value === null || $this->default_value === '') {
                    return '-';
                }

                $val = (float) $this->default_value;
                $isPercentage = $this->calculation_type === SalaryCalculationType::PERCENTAGE_OF_BASIC_SALARY
                    || ($this->calculation_type instanceof \BackedEnum && $this->calculation_type->value === 'persen_gaji_pokok')
                    || (is_string($this->calculation_type) && $this->calculation_type === 'persen_gaji_pokok');

                if ($isPercentage) {
                    return rtrim(rtrim(number_format($val, 2, ',', '.'), '0'), ',').'%';
                }

                return 'Rp '.number_format($val, 0, ',', '.');
            }
        );
    }

    /**
     * Get taxable label.
     */
    protected function taxableLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_taxable ? 'Ya' : 'Tidak'
        );
    }

    /**
     * Get taxable badge class.
     */
    protected function taxableBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_taxable ? 'badge badge-success' : 'badge badge-secondary'
        );
    }

    /**
     * Get system badge label.
     */
    protected function systemLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_system ? 'Sistem' : 'Kustom'
        );
    }

    /**
     * Get the badge CSS class for component type.
     */
    protected function typeBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type instanceof SalaryComponentType
                ? $this->type->badgeClass()
                : (SalaryComponentType::tryFrom((string) $this->type)?->badgeClass() ?? 'badge badge-secondary')
        );
    }

    /**
     * Get the human-readable label for component type.
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type instanceof SalaryComponentType
                ? $this->type->label()
                : (SalaryComponentType::tryFrom((string) $this->type)?->label() ?? (string) $this->type)
        );
    }

    /**
     * Get the group header background CSS class.
     */
    protected function typeHeaderBgClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type instanceof SalaryComponentType
                ? $this->type->headerBgClass()
                : (SalaryComponentType::tryFrom((string) $this->type)?->headerBgClass() ?? 'bg-gray-50')
        );
    }

    /**
     * Get the group header text CSS class.
     */
    protected function typeHeaderTextClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type instanceof SalaryComponentType
                ? $this->type->headerTextClass()
                : (SalaryComponentType::tryFrom((string) $this->type)?->headerTextClass() ?? 'text-dark')
        );
    }

    /**
     * Get the status badge CSS class.
     */
    protected function statusBadgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'badge badge-success' : 'badge badge-danger'
        );
    }

    /**
     * Get the status label.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active ? 'Aktif' : 'Nonaktif'
        );
    }

    /**
     * Scope a query to search salary components by name, code, or description.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
