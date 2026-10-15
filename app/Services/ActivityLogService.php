<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogService
{
    /**
     * Sensitive field names that MUST be masked in context payloads.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'authorization',
        'cookie',
        'cvv',
        'pin',
        'otp',
        'credit_card',
        'bank_account_number',
        'bank_account_name',
        'npwp_number',
        'nik',
        'bpjs_kesehatan_number',
        'bpjs_ketenagakerjaan_number',
        'address',
        'birth_date',
        'birth_place',
    ];

    /**
     * Log an activity conforming to Standard Logging v1.0.
     *
     * @param  string  $action  Static uppercase action code e.g. 'CREATE', 'UPDATE', 'DELETE', 'LOGIN_FAILED'
     * @param  string  $module  Module or service domain e.g. 'posts', 'auth', 'users', 'system'
     * @param  string  $level  'DEBUG', 'INFO', 'WARN', 'WARNING', 'ERROR', 'CRITICAL'
     * @param  string  $message  Static descriptive message without embedded dynamic variables
     * @param  array<string, mixed>|null  $context  Structured key-value payload in snake_case
     * @param  int|null  $userId  Internal user ID
     * @param  Throwable|null  $exception  Exception object for error tracking
     * @param  int|null  $durationMs  Duration in milliseconds if measurable
     */
    public function log(
        string $action,
        string $module,
        string $level,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?Throwable $exception = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        $userId = $userId ?? Auth::id();
        $ip = Request::ip();
        $userAgent = Request::userAgent();
        $requestId = Request::header('X-Request-ID') ?? (string) Str::uuid();
        $normalizedLevel = strtoupper($level === 'warn' ? 'warning' : $level);

        // Sanitize and mask context payload
        $sanitizedContext = $this->maskSensitiveData($context ?? []);

        // If duration is provided, add to context
        if ($durationMs !== null) {
            $sanitizedContext['duration_ms'] = $durationMs;
        }

        // Attach structured error payload if exception is present
        if ($exception instanceof Throwable) {
            $sanitizedContext['error'] = $this->maskSensitiveData([
                'type' => get_class($exception),
                'message' => '[redacted]',
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stack' => Str::limit($exception->getTraceAsString(), 2048),
            ]);
        }

        // 1. Write structured JSON line to standard Laravel/Monolog logging
        $standardLogPayload = [
            'timestamp' => now()->toIso8601ZuluString(),
            'level' => $normalizedLevel,
            'service' => config('app.name', 'larashiz'),
            'env' => config('app.env', 'production'),
            'request_id' => $requestId,
            'module' => strtolower($module),
            'action' => strtoupper($action),
            'message' => $message,
            'user_id' => $userId,
            'ip' => $ip,
            'context' => empty($sanitizedContext) ? null : $sanitizedContext,
        ];

        match ($normalizedLevel) {
            'DEBUG' => Log::debug($message, $standardLogPayload),
            'WARN', 'WARNING' => Log::warning($message, $standardLogPayload),
            'ERROR' => Log::error($message, $standardLogPayload),
            'CRITICAL' => Log::critical($message, $standardLogPayload),
            default => Log::info($message, $standardLogPayload),
        };

        // 2. Persist to Database table with fail-safe try-catch
        try {
            return ActivityLog::create([
                'user_id' => $userId,
                'request_id' => $requestId,
                'module' => strtolower($module),
                'action' => strtoupper($action),
                'level' => strtolower($normalizedLevel === 'WARN' ? 'warning' : $normalizedLevel),
                'message' => $message,
                'context' => empty($sanitizedContext) ? null : $sanitizedContext,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        } catch (Throwable $e) {
            Log::emergency('Failed to write ActivityLog to database', [
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }

    /**
     * Helper for DEBUG level logging.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function debug(
        string $action,
        string $module,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?Throwable $exception = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        return $this->log($action, $module, 'debug', $message, $context, $userId, $exception, $durationMs);
    }

    /**
     * Helper for INFO level logging.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function info(
        string $action,
        string $module,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        return $this->log($action, $module, 'info', $message, $context, $userId, null, $durationMs);
    }

    /**
     * Helper for WARNING / WARN level logging.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function warning(
        string $action,
        string $module,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?Throwable $exception = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        return $this->log($action, $module, 'warning', $message, $context, $userId, $exception, $durationMs);
    }

    /**
     * Alias for warning().
     *
     * @param  array<string, mixed>|null  $context
     */
    public function warn(
        string $action,
        string $module,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?Throwable $exception = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        return $this->warning($action, $module, $message, $context, $userId, $exception, $durationMs);
    }

    /**
     * Helper for ERROR level logging.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function error(
        string $action,
        string $module,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?Throwable $exception = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        return $this->log($action, $module, 'error', $message, $context, $userId, $exception, $durationMs);
    }

    /**
     * Helper for CRITICAL level logging.
     *
     * @param  array<string, mixed>|null  $context
     */
    public function critical(
        string $action,
        string $module,
        string $message,
        ?array $context = null,
        ?int $userId = null,
        ?Throwable $exception = null,
        ?int $durationMs = null
    ): ?ActivityLog {
        return $this->log($action, $module, 'critical', $message, $context, $userId, $exception, $durationMs);
    }

    /**
     * Mask email string: f***@domain.com
     */
    public function maskEmail(?string $email): ?string
    {
        if (empty($email) || ! str_contains($email, '@')) {
            return $email;
        }

        [$username, $domain] = explode('@', $email, 2);
        $maskedUsername = substr($username, 0, 1).str_repeat('*', max(3, strlen($username) - 1));

        return "{$maskedUsername}@{$domain}";
    }

    /**
     * Mask phone number string: 0812****5678
     */
    public function maskPhone(?string $phone): ?string
    {
        if (empty($phone) || strlen($phone) < 8) {
            return $phone;
        }

        $prefix = substr($phone, 0, 4);
        $suffix = substr($phone, -4);

        return "{$prefix}****{$suffix}";
    }

    /**
     * Recursively mask sensitive fields in context arrays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function maskSensitiveData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (in_array($lowerKey, self::SENSITIVE_KEYS, true)) {
                $sanitized[$key] = '********';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->maskSensitiveData($value);
            } elseif ($lowerKey === 'email' && is_string($value)) {
                $sanitized[$key] = $this->maskEmail($value);
            } elseif (in_array($lowerKey, ['phone', 'phone_number', 'telepon'], true) && is_string($value)) {
                $sanitized[$key] = $this->maskPhone($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Retrieve paginated activity logs with filters.
     */
    public function getPaginatedLogs(
        string $search = '',
        string $startDate = '',
        string $endDate = '',
        string $level = '',
        string $module = '',
        int $perPage = 10
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        return ActivityLog::query()
            ->with('user')
            ->dateRange($startDate ?: null, $endDate ?: null)
            ->level($level ?: null)
            ->module($module ?: null)
            ->search($search ?: null)
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get aggregate statistics of activity logs for the specified date range.
     *
     * @return array{total: int, info: int, warning: int, error: int}
     */
    public function getLogStatistics(string $startDate = '', string $endDate = ''): array
    {
        $baseQuery = ActivityLog::query()
            ->dateRange($startDate ?: null, $endDate ?: null);

        $counts = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN LOWER(level) = 'info' THEN 1 ELSE 0 END) as info_count,
                SUM(CASE WHEN LOWER(level) IN ('warning', 'warn') THEN 1 ELSE 0 END) as warning_count,
                SUM(CASE WHEN LOWER(level) IN ('error', 'critical') THEN 1 ELSE 0 END) as error_count
            ")
            ->first();

        return [
            'total' => (int) ($counts?->total ?? 0),
            'info' => (int) ($counts?->info_count ?? 0),
            'warning' => (int) ($counts?->warning_count ?? 0),
            'error' => (int) ($counts?->error_count ?? 0),
        ];
    }

    /**
     * Get list of distinct modules that have logs.
     *
     * @return array<string>
     */
    public function getDistinctModules(): array
    {
        return ActivityLog::query()
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->toArray();
    }
}
