<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Tanggal libur dalam rentang (inklusif) sebagai set string Y-m-d.
     *
     * @return Collection<string, string>
     */
    public static function datesBetween(string $startDate, string $endDate)
    {
        return self::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d)
            ->flip();
    }
}
