<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use Prunable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'request_id',
        'module',
        'action',
        'level',
        'message',
        'context',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    /**
     * Get the user that triggered the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays((int) config('app.activity_log_retention_days', 365)));
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange(Builder $query, ?string $startDate = null, ?string $endDate = null): Builder
    {
        return $query
            ->when($startDate, fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($startDate)->startOfDay()))
            ->when($endDate, fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($endDate)->endOfDay()));
    }

    /**
     * Scope a query to search logs across message, action, module, IP, request_id, and user name/email.
     */
    public function scopeSearch(Builder $query, ?string $term = null): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('message', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('module', 'like', "%{$term}%")
                ->orWhere('request_id', 'like', "%{$term}%")
                ->orWhere('ip_address', 'like', "%{$term}%")
                ->orWhereHas('user', function (Builder $userQuery) use ($term) {
                    $userQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Scope a query to only include logs of a specific module.
     */
    public function scopeModule(Builder $query, ?string $module = null): Builder
    {
        return $query->when(! empty($module), fn (Builder $q) => $q->where('module', $module));
    }

    /**
     * Scope a query to only include logs of a specific level.
     */
    public function scopeLevel(Builder $query, ?string $level = null): Builder
    {
        return $query->when(! empty($level), fn (Builder $q) => $q->where('level', strtolower($level)));
    }

    /**
     * Get the badge CSS class for the log level.
     *
     * @return Attribute<string, never>
     */
    protected function levelBadgeClass(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $level = strtolower($this->level ?? '');

                return match ($level) {
                    'error', 'critical' => 'badge badge-danger',
                    'warning', 'warn' => 'badge badge-warning',
                    'debug' => 'badge badge-secondary',
                    default => 'badge badge-info',
                };
            }
        );
    }
}
