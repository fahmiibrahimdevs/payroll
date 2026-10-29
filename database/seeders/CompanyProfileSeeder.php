<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    /**
     * Seed the master company profile.
     */
    public function run(): void
    {
        CompanyProfile::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'PT LARASHIZ INDONESIA',
                'code' => 'LARASHIZ',
                'email' => 'info@larashiz.com',
                'phone' => '021-12345678',
                'website' => 'https://larashiz.com',
                'email_domain' => 'larashiz.com',
                'address' => 'Jl. Jenderal Sudirman No. 123, Jakarta Selatan',
                'city' => 'Jakarta Selatan',
                'province' => 'DKI Jakarta',
                'postal_code' => '12190',
                'tax_identification_number' => '01.234.567.8-901.000',
                'work_days_per_week' => 6,
                'default_meal_allowance_per_day' => 25000.00,
                'overtime_rounding_mode' => 'round_60_min',
                'overtime_rounding_threshold_minutes' => 10,
                'is_uniform_meal_allowance' => true,
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
                'bank_name' => 'Bank Mandiri',
                'bank_account_number' => '123-00-9876543-2',
                'bank_account_name' => 'PT Larashiz Indonesia',
            ]
        );

        CompanyProfile::clearProfileCache();
    }
}
