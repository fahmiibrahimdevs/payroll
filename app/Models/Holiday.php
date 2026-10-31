<?php

namespace App\Models;

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
     * Nama libur dalam rentang (inklusif), diindeks tanggal Y-m-d.
     *
     * @return Collection<string, string>
     */
    public static function datesBetween(string $startDate, string $endDate)
    {
        return self::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->get(['date', 'name'])
            ->mapWithKeys(fn ($holiday) => [
                $holiday->date->toDateString() => $holiday->name,
            ]);
    }
}
