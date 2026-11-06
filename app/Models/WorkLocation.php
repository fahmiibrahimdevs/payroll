<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkLocation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to only include active work locations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to search work locations by name or address.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%");
        });
    }

    /**
     * Employees assigned to this location as their primary location.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'work_location_id');
    }

    /**
     * Date-range based location assignments for this location.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeLocationAssignment::class, 'work_location_id');
    }

    /**
     * Attendance records clocked in at this location.
     */
    public function clockInAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'clock_in_location_id');
    }

    /**
     * Attendance records clocked out at this location.
     */
    public function clockOutAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'clock_out_location_id');
    }

    /**
     * Helper accessor for Google Maps URL.
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * Helper accessor for formatted coordinates.
     */
    public function getFormattedCoordinatesAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }
}
