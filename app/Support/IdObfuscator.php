<?php

namespace App\Support;

use Hashids\Hashids;
use Illuminate\Support\Facades\Config;

class IdObfuscator
{
    /**
     * Minimum length of the generated hashid (8 characters: sweet spot).
     */
    public const MIN_LENGTH = 8;

    /**
     * Singleton instances keyed by salt/prefix.
     *
     * @var array<string, Hashids>
     */
    protected static array $instances = [];

    /**
     * Retrieve or create a Hashids instance.
     */
    public static function getInstance(?string $connection = null): Hashids
    {
        $connection = $connection ?? 'default';

        if (! isset(static::$instances[$connection])) {
            $appKey = (string) Config::get('app.key', 'larashiz-hrm-secret-obfuscation-key');
            $salt = hash('sha256', $appKey . ':' . $connection);

            static::$instances[$connection] = new Hashids($salt, self::MIN_LENGTH);
        }

        return static::$instances[$connection];
    }

    /**
     * Encode an integer database ID into a safe 8-character obfuscated string.
     */
    public static function encode(int $id, ?string $connection = null): string
    {
        if ($id <= 0) {
            return '';
        }

        return static::getInstance($connection)->encode($id);
    }

    /**
     * Decode an obfuscated string back into its original database integer ID.
     * If the input is already a positive integer (e.g., during internal calls or test suites),
     * it gracefully returns the integer.
     */
    public static function decode(mixed $value, ?string $connection = null): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If it's already an integer, return it directly
        if (is_int($value) && $value > 0) {
            return $value;
        }

        $strValue = trim((string) $value);

        // First attempt to decode as Hashid
        $decoded = static::getInstance($connection)->decode($strValue);
        if (! empty($decoded) && isset($decoded[0]) && is_numeric($decoded[0])) {
            return (int) $decoded[0];
        }

        // Fallback for purely numeric integer string (backward-compatibility / testing)
        if (ctype_digit($strValue)) {
            $numericVal = (int) $strValue;
            if ($numericVal > 0) {
                return $numericVal;
            }
        }

        return null;
    }

    /**
     * Decode an obfuscated ID or abort with 404 Not Found if tampered/invalid.
     */
    public static function decodeOrFail(mixed $value, ?string $connection = null): int
    {
        $decoded = static::decode($value, $connection);

        if ($decoded === null || $decoded <= 0) {
            abort(404, 'Data tidak ditemukan atau ID tidak valid.');
        }

        return $decoded;
    }
}
