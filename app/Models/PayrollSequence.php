<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_key',
        'current_number',
    ];

    protected function casts(): array
    {
        return [
            'current_number' => 'integer',
        ];
    }
}
