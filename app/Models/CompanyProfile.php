<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyProfile extends Model
{
    use HasFactory;

    public const OVERTIME_ROUNDING_MODES = [
        'round_30_min' => 'Kelipatan 30 Menit (0.5 Jam) — Rekomendasi',
        'round_60_min' => 'Kelipatan 1 Jam Penuh',
        'floor_hour' => 'Jam Penuh ke Bawah (Floor)',
        'exact' => 'Menit Riil / Eksak (Tanpa Pembulatan)',
    ];

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'website',
        'email_domain',
        'address',
        'city',
        'province',
        'postal_code',
        'logo_path',
        'tax_identification_number',
        'work_days_per_week',
        'default_meal_allowance_per_day',
        'overtime_rounding_mode',
        'overtime_rounding_threshold_minutes',
        'is_uniform_meal_allowance',
        'payroll_cutoff_day',
        'payroll_pay_day',
        'enable_pph21',
        'include_bpjs_in_tax',
        'bpjs_jkk_risk_rate',
        'bpjs_jkm_rate',
        'bpjs_kes_emp_rate',
        'bpjs_kes_comp_rate',
        'bpjs_jht_emp_rate',
        'bpjs_jht_comp_rate',
        'bpjs_jp_emp_rate',
        'bpjs_jp_comp_rate',
        'bpjs_kes_cap',
        'bpjs_jp_cap',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_days_per_week' => 'integer',
            'default_meal_allowance_per_day' => 'decimal:2',
            'overtime_rounding_mode' => 'string',
            'overtime_rounding_threshold_minutes' => 'integer',
            'is_uniform_meal_allowance' => 'boolean',
            'payroll_cutoff_day' => 'integer',
            'payroll_pay_day' => 'integer',
            'enable_pph21' => 'boolean',
            'include_bpjs_in_tax' => 'boolean',
            'bpjs_jkk_risk_rate' => 'decimal:4',
            'bpjs_jkm_rate' => 'decimal:4',
            'bpjs_kes_emp_rate' => 'decimal:4',
            'bpjs_kes_comp_rate' => 'decimal:4',
            'bpjs_jht_emp_rate' => 'decimal:4',
            'bpjs_jht_comp_rate' => 'decimal:4',
            'bpjs_jp_emp_rate' => 'decimal:4',
            'bpjs_jp_comp_rate' => 'decimal:4',
            'bpjs_kes_cap' => 'decimal:2',
            'bpjs_jp_cap' => 'decimal:2',
        ];
    }

    public const CACHE_KEY = 'company_profile_singleton';

    /**
     * Get the singleton or first company profile record.
     */
    public static function getProfile(): self
    {
        $attributes = Cache::remember(self::CACHE_KEY, 86400, function () {
            $record = static::firstOrCreate([], [
                'name' => 'PT LARASHIZ INDONESIA',
                'code' => 'LARASHIZ',
                'email' => 'info@larashiz.com',
                'phone' => '021-12345678',
                'website' => 'https://larashiz.com',
                'email_domain' => 'larashiz.com',
                'address' => 'Jl. Jenderal Sudirman No. 123, Jakarta Selatan',
                'tax_identification_number' => '01.234.567.8-901.000',
                'work_days_per_week' => 6,
                'payroll_cutoff_day' => 20,
                'payroll_pay_day' => 25,
                'enable_pph21' => true,
                'include_bpjs_in_tax' => true,
                'bpjs_jkk_risk_rate' => 0.0024,
                'bpjs_jkm_rate' => 0.0030,
                'bpjs_kes_emp_rate' => 0.0100,
                'bpjs_kes_comp_rate' => 0.0400,
                'bpjs_jht_emp_rate' => 0.0200,
                'bpjs_jht_comp_rate' => 0.0370,
                'bpjs_jp_emp_rate' => 0.0100,
                'bpjs_jp_comp_rate' => 0.0200,
                'bpjs_kes_cap' => 12000000.00,
                'bpjs_jp_cap' => 10042300.00,
            ]);

            return $record->getAttributes();
        });

        return (new static)->newFromBuilder($attributes);
    }

    public static function clearProfileCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::clearProfileCache());
        static::deleted(fn () => static::clearProfileCache());
    }

    /**
     * Resolve the payroll period date range and payment date based on company cutoff & pay day settings.
     *
     * @return array{start_date: \Carbon\Carbon, end_date: \Carbon\Carbon, payment_date: \Carbon\Carbon}
     */
    public function resolvePayrollCycleDates(int $year, int $month): array
    {
        $currMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $cutoffDay = (int) ($this->payroll_cutoff_day ?? 20);
        $payDaySetting = (int) ($this->payroll_pay_day ?? 25);

        // If cutoffDay is 0 or >= 31, use full calendar month (1st to last day of month)
        if ($cutoffDay === 0 || $cutoffDay >= 31) {
            $startDate = $currMonth->copy()->startOfDay();
            $endDate = $currMonth->copy()->endOfMonth();
            $payDay = $payDaySetting > 0 ? min($payDaySetting, $currMonth->daysInMonth) : $currMonth->daysInMonth;
            $paymentDate = $currMonth->copy()->day($payDay)->startOfDay();

            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payment_date' => $paymentDate,
            ];
        }

        // Standard Cutoff Cycle: (Previous month cutoff + 1) to (Current month cutoff)
        $endDay = min($cutoffDay, $currMonth->daysInMonth);
        $endDate = $currMonth->copy()->day($endDay)->endOfDay();

        $prevMonth = $currMonth->copy()->subMonth();
        $prevCutoffDay = min($cutoffDay, $prevMonth->daysInMonth);
        $startDate = $prevMonth->copy()->day($prevCutoffDay)->addDay()->startOfDay();

        // If payment day is on or after cutoff day, payment is in the current month.
        // If payment day is before cutoff day, payment is in the next month.
        if ($payDaySetting >= $endDay) {
            $paymentDay = min($payDaySetting, $currMonth->daysInMonth);
            $paymentDate = $currMonth->copy()->day($paymentDay)->startOfDay();
        } else {
            $nextMonth = $currMonth->copy()->addMonth();
            $paymentDay = min($payDaySetting, $nextMonth->daysInMonth);
            $paymentDate = $nextMonth->copy()->day($paymentDay)->startOfDay();
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_date' => $paymentDate,
        ];
    }

    /**
     * Resolve the active company email domain (e.g. larashiz.com).
     */
    public function getEmailDomain(): string
    {
        if (! empty($this->email_domain)) {
            return ltrim(trim($this->email_domain), '@');
        }

        if (! empty($this->website)) {
            $host = parse_url($this->website, PHP_URL_HOST) ?: $this->website;
            $host = preg_replace('/^www\./i', '', trim($host));
            if (! empty($host)) {
                return strtolower($host);
            }
        }

        if (! empty($this->email) && str_contains($this->email, '@')) {
            $parts = explode('@', $this->email);

            return strtolower($parts[1]);
        }

        $slug = Str::slug($this->code ?: $this->name);

        return ($slug ?: 'namaperusahaan').'.com';
    }

    /**
     * Automatically generate employee email from their full name (e.g. Arif Fahmi Abdillah -> arif.fahmi.abdillah@company.com).
     */
    public static function generateEmployeeEmail(string $name, ?string $domain = null): string
    {
        $name = trim($name);
        if (empty($name)) {
            return '';
        }

        // Clean out special characters and punctuation
        $cleaned = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $words = array_values(array_filter(preg_split('/\s+/', strtolower($cleaned))));
        $username = implode('.', $words);

        if (empty($username)) {
            return '';
        }

        $domain = $domain ?: static::getProfile()->getEmailDomain();
        $domain = ltrim(trim($domain), '@');

        return $username.'@'.$domain;
    }

    /**
     * Get a compact brand label from the configured company code or its initials.
     */
    public function getDisplayCode(): string
    {
        $code = trim((string) $this->code);
        if ($code !== '') {
            return $code;
        }

        $words = array_values(array_filter(preg_split('/\s+/', trim((string) $this->name)) ?: []));
        $initials = collect($words)
            ->take(4)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'HRM';
    }

    protected function formattedDefaultMealAllowancePerDay(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'Rp '.number_format((float) ($this->default_meal_allowance_per_day ?? 0), 0, ',', '.'),
        );
    }

    /**
     * Get logo URL.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->logo_path && Storage::disk('public')->exists($this->logo_path)) {
                    return Storage::disk('public')->url($this->logo_path);
                }

                return asset('assets/img/larashiz-logo.jpg');
            }
        );
    }
}
