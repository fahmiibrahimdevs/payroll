<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'website',
        'email_domain',
        'address',
        'logo_path',
        'tax_identification_number',
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

    /**
     * Get the singleton or first company profile record.
     */
    public static function getProfile(): self
    {
        return static::firstOrCreate([], [
            'name' => 'PT LARASHIZ INDONESIA',
            'code' => 'LARASHIZ',
            'email' => 'info@larashiz.com',
            'phone' => '021-12345678',
            'website' => 'https://larashiz.com',
            'email_domain' => 'larashiz.com',
            'address' => 'Jl. Jenderal Sudirman No. 123, Jakarta Selatan',
            'tax_identification_number' => '01.234.567.8-901.000',
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
