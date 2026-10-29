<?php

namespace App\Traits;

use App\Support\IdObfuscator;
use Illuminate\Database\Eloquent\Model;

trait HasObfuscatedId
{
    /**
     * Get the obfuscated hashid for this model instance.
     */
    public function getHashidAttribute(): string
    {
        return IdObfuscator::encode((int) $this->getKey(), static::class);
    }

    /**
     * Get the value of the model's route key (uses hashid for URLs).
     */
    public function getRouteKey(): mixed
    {
        return $this->hashid;
    }

    /**
     * Retrieve the model for a bound route value.
     * Decodes the obfuscated hashid back to integer ID.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null && $field !== $this->getKeyName()) {
            return parent::resolveRouteBinding($value, $field);
        }

        $decodedId = IdObfuscator::decode($value, static::class);

        if ($decodedId === null || $decodedId <= 0) {
            return null;
        }

        return $this->where($this->getQualifiedKeyName(), $decodedId)->first();
    }
}
