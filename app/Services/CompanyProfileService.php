<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\EmployeeDetail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyProfileService
{
    public function __construct(
        protected ActivityLogService $logger
    ) {}

    /**
     * Get the active company profile record.
     */
    public function getProfile(): CompanyProfile
    {
        return CompanyProfile::getProfile();
    }

    /**
     * Update the company profile information.
     */
    public function updateProfile(array $data, ?UploadedFile $logo = null): CompanyProfile
    {
        $profile = $this->getProfile();

        if ($logo) {
            if ($profile->logo_path && Storage::disk('public')->exists($profile->logo_path)) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $logo->store('company', 'public');
        }

        $attributes = [
            'name' => trim((string) ($data['name'] ?? $profile->name)),
            'code' => ! empty($data['code']) ? trim((string) $data['code']) : null,
            'email' => ! empty($data['email']) ? trim((string) $data['email']) : null,
            'phone' => ! empty($data['phone']) ? trim((string) $data['phone']) : null,
            'website' => ! empty($data['website']) ? trim((string) $data['website']) : null,
            'email_domain' => ! empty($data['email_domain']) ? ltrim(trim((string) $data['email_domain']), '@') : null,
            'address' => ! empty($data['address']) ? trim((string) $data['address']) : null,
            'city' => ! empty($data['city']) ? trim((string) $data['city']) : null,
            'province' => ! empty($data['province']) ? trim((string) $data['province']) : null,
            'postal_code' => ! empty($data['postal_code']) ? trim((string) $data['postal_code']) : null,
            'tax_identification_number' => ! empty($data['tax_identification_number']) ? trim((string) $data['tax_identification_number']) : null,
            'work_days_per_week' => isset($data['work_days_per_week']) ? (int) $data['work_days_per_week'] : ($profile->work_days_per_week ?? 6),
            'default_meal_allowance_per_day' => isset($data['default_meal_allowance_per_day']) ? (float) $data['default_meal_allowance_per_day'] : ($profile->default_meal_allowance_per_day ?? 25000),
            'overtime_rounding_mode' => $data['overtime_rounding_mode'] ?? $profile->overtime_rounding_mode ?? 'round_60_min',
            'overtime_rounding_threshold_minutes' => isset($data['overtime_rounding_threshold_minutes']) ? (int) $data['overtime_rounding_threshold_minutes'] : ($profile->overtime_rounding_threshold_minutes ?? 10),
            'is_uniform_meal_allowance' => isset($data['is_uniform_meal_allowance']) ? (bool) $data['is_uniform_meal_allowance'] : (bool) ($profile->is_uniform_meal_allowance ?? true),
            'payroll_cutoff_day' => isset($data['payroll_cutoff_day']) ? (int) $data['payroll_cutoff_day'] : ($profile->payroll_cutoff_day ?? 20),
            'payroll_pay_day' => isset($data['payroll_pay_day']) ? (int) $data['payroll_pay_day'] : ($profile->payroll_pay_day ?? 25),
            'enable_pph21' => isset($data['enable_pph21']) ? (bool) $data['enable_pph21'] : $profile->enable_pph21,
            'include_bpjs_in_tax' => isset($data['include_bpjs_in_tax']) ? (bool) $data['include_bpjs_in_tax'] : $profile->include_bpjs_in_tax,
            'bpjs_jkk_risk_rate' => isset($data['bpjs_jkk_risk_rate']) ? (float) $data['bpjs_jkk_risk_rate'] : $profile->bpjs_jkk_risk_rate,
            'bpjs_jkm_rate' => isset($data['bpjs_jkm_rate']) ? (float) $data['bpjs_jkm_rate'] : $profile->bpjs_jkm_rate,
            'bpjs_kes_emp_rate' => isset($data['bpjs_kes_emp_rate']) ? (float) $data['bpjs_kes_emp_rate'] : $profile->bpjs_kes_emp_rate,
            'bpjs_kes_comp_rate' => isset($data['bpjs_kes_comp_rate']) ? (float) $data['bpjs_kes_comp_rate'] : $profile->bpjs_kes_comp_rate,
            'bpjs_jht_emp_rate' => isset($data['bpjs_jht_emp_rate']) ? (float) $data['bpjs_jht_emp_rate'] : $profile->bpjs_jht_emp_rate,
            'bpjs_jht_comp_rate' => isset($data['bpjs_jht_comp_rate']) ? (float) $data['bpjs_jht_comp_rate'] : $profile->bpjs_jht_comp_rate,
            'bpjs_jp_emp_rate' => isset($data['bpjs_jp_emp_rate']) ? (float) $data['bpjs_jp_emp_rate'] : $profile->bpjs_jp_emp_rate,
            'bpjs_jp_comp_rate' => isset($data['bpjs_jp_comp_rate']) ? (float) $data['bpjs_jp_comp_rate'] : $profile->bpjs_jp_comp_rate,
            'bpjs_kes_cap' => isset($data['bpjs_kes_cap']) ? (float) $data['bpjs_kes_cap'] : $profile->bpjs_kes_cap,
            'bpjs_jp_cap' => isset($data['bpjs_jp_cap']) ? (float) $data['bpjs_jp_cap'] : $profile->bpjs_jp_cap,
            'bank_name' => ! empty($data['bank_name']) ? trim((string) $data['bank_name']) : null,
            'bank_account_number' => ! empty($data['bank_account_number']) ? trim((string) $data['bank_account_number']) : null,
            'bank_account_name' => ! empty($data['bank_account_name']) ? trim((string) $data['bank_account_name']) : null,
            'logo_path' => $data['logo_path'] ?? $profile->logo_path,
        ];

        $oldDomain = $profile->getEmailDomain();

        $profile->fill($attributes);
        $changedFields = array_keys($profile->getDirty());
        $profile->save();

        $newDomain = $profile->getEmailDomain();
        if (! empty($newDomain) && ! empty($oldDomain) && strcasecmp($oldDomain, $newDomain) !== 0) {
            $this->syncEmployeeEmailDomains($newDomain);
        }

        if ($changedFields !== []) {
            $this->logger->info(
                action: 'PROFILE_UPDATED',
                module: 'company_profile',
                message: 'Company profile updated',
                context: [
                    'company_profile_id' => $profile->id,
                    'changed_fields' => $changedFields,
                    'pph21_enabled' => (bool) $profile->enable_pph21,
                    'bpjs_included_in_tax' => (bool) $profile->include_bpjs_in_tax,
                ]
            );
        }

        return $profile;
    }

    /**
     * Bulk update all employee emails and linked user accounts to a new company email domain.
     */
    public function syncEmployeeEmailDomains(string $newDomain): int
    {
        $newDomain = ltrim(trim($newDomain), '@');
        if ($newDomain === '') {
            return 0;
        }

        $updatedCount = 0;

        DB::transaction(function () use ($newDomain, &$updatedCount) {
            $details = EmployeeDetail::with('employee.user')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            foreach ($details as $detail) {
                $email = $detail->email;
                $username = Str::before($email, '@');
                if (empty($username)) {
                    continue;
                }

                $newEmail = strtolower($username . '@' . $newDomain);
                if (strtolower($email) === $newEmail) {
                    continue;
                }

                $oldEmail = $detail->email;
                $detail->update(['email' => $newEmail]);

                if ($detail->employee?->user) {
                    $detail->employee->user->update(['email' => $newEmail]);
                } else {
                    User::where('email', $oldEmail)->update(['email' => $newEmail]);
                }

                $updatedCount++;
            }
        });

        if ($updatedCount > 0) {
            $this->logger->info(
                action: 'EMPLOYEE_EMAIL_DOMAINS_SYNCED',
                module: 'company_profile',
                message: "Bulk updated {$updatedCount} employee emails to domain @{$newDomain}",
                context: [
                    'new_domain' => $newDomain,
                    'updated_count' => $updatedCount,
                ]
            );
        }

        return $updatedCount;
    }
}
