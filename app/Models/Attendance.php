<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

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
        'clock_in_photo_path',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_distance_meters',
        'clock_in_location_id',
        'clock_out_photo_path',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_distance_meters',
        'clock_out_location_id',
        'is_overtime',
        'overtime_status',
        'overtime_in',
        'overtime_out',
        'overtime_in_photo_path',
        'overtime_in_latitude',
        'overtime_in_longitude',
        'overtime_in_distance_meters',
        'overtime_in_location_id',
        'overtime_out_photo_path',
        'overtime_out_latitude',
        'overtime_out_longitude',
        'overtime_out_distance_meters',
        'overtime_out_location_id',
        'status',
        'duration_minutes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'source',
        'raw_log',
        'notes',
        'review_status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'device_info',
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
            'clock_in_latitude' => 'decimal:7',
            'clock_in_longitude' => 'decimal:7',
            'clock_in_distance_meters' => 'integer',
            'clock_out_latitude' => 'decimal:7',
            'clock_out_longitude' => 'decimal:7',
            'clock_out_distance_meters' => 'integer',
            'is_overtime' => 'boolean',
            'overtime_in_latitude' => 'decimal:7',
            'overtime_in_longitude' => 'decimal:7',
            'overtime_in_distance_meters' => 'integer',
            'overtime_out_latitude' => 'decimal:7',
            'overtime_out_longitude' => 'decimal:7',
            'overtime_out_distance_meters' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * The employee who owns the attendance record.
     */
    public function employee(): BelongsTo
    {
        // withTrashed: karyawan terarsip tidak kehilangan nama saat log lama
        // diedit atau dicetak (pola sama dengan PayrollItem).
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * Work location where employee clocked in.
     */
    public function clockInLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'clock_in_location_id');
    }

    /**
     * Work location where employee clocked out.
     */
    public function clockOutLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'clock_out_location_id');
    }

    /**
     * Work location where employee started overtime.
     */
    public function overtimeInLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'overtime_in_location_id');
    }

    /**
     * Work location where employee finished overtime.
     */
    public function overtimeOutLocation(): BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'overtime_out_location_id');
    }

    /**
     * Overtime submission record linked to this attendance.
     */
    public function overtimeSubmission(): HasOne
    {
        return $this->hasOne(OvertimeSubmission::class, 'attendance_id');
    }

    /**
     * User who reviewed this attendance record.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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
     * Scope query to attendances pending review.
     */
    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('review_status', 'pending_review');
    }

    /**
     * Check if attendance is pending review.
     */
    public function isPendingReview(): bool
    {
        return $this->review_status === 'pending_review';
    }

    /**
     * Check if attendance is approved.
     */
    public function isApproved(): bool
    {
        return $this->review_status === 'approved';
    }

    /**
     * Check if attendance is rejected.
     */
    public function isRejected(): bool
    {
        return $this->review_status === 'rejected';
    }

    /**
     * Format a storage path to a web-accessible relative URL.
     */
    private function formatPhotoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $clean = ltrim($path, '/');
        if (str_starts_with($clean, 'storage/')) {
            return '/'.$clean;
        }

        return '/storage/'.$clean;
    }

    /**
     * URL for clock in photo.
     */
    public function getClockInPhotoUrlAttribute(): ?string
    {
        return $this->formatPhotoUrl($this->clock_in_photo_path);
    }

    /**
     * URL for clock out photo.
     */
    public function getClockOutPhotoUrlAttribute(): ?string
    {
        return $this->formatPhotoUrl($this->clock_out_photo_path);
    }

    /**
     * URL for overtime in photo.
     */
    public function getOvertimeInPhotoUrlAttribute(): ?string
    {
        return $this->formatPhotoUrl($this->overtime_in_photo_path);
    }

    /**
     * URL for overtime out photo.
     */
    public function getOvertimeOutPhotoUrlAttribute(): ?string
    {
        return $this->formatPhotoUrl($this->overtime_out_photo_path);
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
