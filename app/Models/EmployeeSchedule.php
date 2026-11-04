<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class EmployeeSchedule extends Model
{
    use HasFactory;

    public const STATUS_WORK = 'work';

    public const STATUS_OFF = 'off';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'notes',
        'created_by',
        'updated_by',
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
        ];
    }

    /**
     * Relasi ke Employee (termasuk soft-deleted employee).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * Relasi ke pembuat record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengupdate record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope jadwal dalam rentang tanggal tertentu.
     */
    public function scopeDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope jadwal untuk bulan dan tahun tertentu.
     */
    public function scopeMonth(Builder $query, int $year, int $month): Builder
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Alias scope jadwal untuk bulan dan tahun tertentu.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $this->scopeMonth($query, $year, $month);
    }

    /**
     * Apakah hari ini dijadwalkan libur (OFF).
     */
    public function isOff(): bool
    {
        return $this->status === self::STATUS_OFF;
    }

    /**
     * Apakah hari ini dijadwalkan masuk kerja (WORK).
     */
    public function isWork(): bool
    {
        return $this->status === self::STATUS_WORK;
    }

    /**
     * Mengambil mapping jadwal karyawan dalam rentang tanggal, diindeks per [employee_id][Y-m-d].
     *
     * @param  array<int>  $employeeIds
     * @return Collection<int, Collection<string, EmployeeSchedule>>
     */
    public static function schedulesForEmployees(array $employeeIds, string $startDate, string $endDate): Collection
    {
        return self::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($records) => $records->keyBy(fn ($s) => $s->date->toDateString()));
    }
}
