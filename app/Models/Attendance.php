<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'overtime_in',
        'overtime_out',
        'status',
        'duration_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'source',
        'raw_log',
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
            'date' => 'date',
            'duration_minutes' => 'integer',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'overtime_minutes' => 'integer',
        ];
    }

    /**
     * The employee who owns the attendance record.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope query for a date range.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope query by search term across employee name or code.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereHas('employee', function (Builder $q) use ($term) {
            $q->where('full_name', 'like', "%{$term}%")
              ->orWhere('fingerprint_id', 'like', "%{$term}%")
              ->orWhereHas('detail', fn ($sub) => $sub->where('employee_code', 'like', "%{$term}%"));
        });
    }

    /**
     * Status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return match (strtolower($this->status)) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'pulang_cepat' => 'Pulang Cepat',
            'absen' => 'Mangkir',
            'libur' => 'Libur',
            'cuti' => 'Cuti',
            default => ucfirst($this->status),
        };
    }

    /**
     * Status badge class attribute.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match (strtolower($this->status)) {
            'hadir' => 'badge badge-success',
            'terlambat' => 'badge badge-warning',
            'pulang_cepat' => 'badge badge-warning',
            'absen' => 'badge badge-danger',
            'libur' => 'badge badge-secondary',
            'cuti' => 'badge badge-info',
            default => 'badge badge-light',
        };
    }

    /**
     * Formatted duration in hours & minutes.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_minutes <= 0) {
            return '-';
        }

        $hours = intdiv($this->duration_minutes, 60);
        $mins = $this->duration_minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return "{$hours}j {$mins}m";
        }

        if ($hours > 0) {
            return "{$hours} jam";
        }

        return "{$mins} mnt";
    }
}
