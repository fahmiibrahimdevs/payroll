<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLocationAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'work_location_id',
        'start_date',
        'end_date',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * The employee who is assigned.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The work location assigned.
     */
    public function workLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class);
    }

    /**
     * Scope a query to only include assignments active on a given date.
     */
    public function scopeActiveOnDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $dateStr = $date instanceof CarbonInterface ? $date->format('Y-m-d') : $date;

        return $query->whereDate('start_date', '<=', $dateStr)
            ->whereDate('end_date', '>=', $dateStr);
    }
}
