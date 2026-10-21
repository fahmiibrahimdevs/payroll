<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    public const LOOKUP_CACHE_KEY = 'departments_lookup';

    public static function getLookup(): Collection
    {
        $items = Cache::remember(self::LOOKUP_CACHE_KEY, 86400, function () {
            return static::query()->select(['id', 'name'])->orderBy('name')->get()->toArray();
        });

        return (new static)->hydrate($items);
    }

    public static function clearLookupCache(): void
    {
        Cache::forget(self::LOOKUP_CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearLookupCache());
        static::deleted(fn () => static::clearLookupCache());
    }

    /**
     * Get the employees belonging to the department.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Scope a query to search departments by name or description.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
