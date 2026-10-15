<?php

namespace App\Livewire\CompanyProfile;

use App\Services\CompanyProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
#[Title('Profil Perusahaan - Larashiz HRM')]
class Index extends Component
{
    use WithFileUploads;

    public string $name = '';

    public ?string $code = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $website = null;

    public ?string $email_domain = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $province = null;

    public ?string $postal_code = null;

    public ?string $tax_identification_number = null;

    public bool $enable_pph21 = true;

    public bool $include_bpjs_in_tax = true;

    public float $bpjs_jkk_risk_rate = 0.0024;

    public float $bpjs_jkm_rate = 0.0030;

    public float $bpjs_kes_emp_rate = 0.0100;

    public float $bpjs_kes_comp_rate = 0.0400;

    public float $bpjs_jht_emp_rate = 0.0200;

    public float $bpjs_jht_comp_rate = 0.0370;

    public float $bpjs_jp_emp_rate = 0.0100;

    public float $bpjs_jp_comp_rate = 0.0200;

    public float $bpjs_kes_cap = 12000000.00;

    public float $bpjs_jp_cap = 10042300.00;

    public ?string $bank_name = null;

    public ?string $bank_account_number = null;

    public ?string $bank_account_name = null;

    public $logo;

    public ?string $currentLogoUrl = null;

    public function mount(CompanyProfileService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $profile = $service->getProfile();

        $this->name = $profile->name;
        $this->code = $profile->code;
        $this->email = $profile->email;
        $this->phone = $profile->phone;
        $this->website = $profile->website;
        $this->email_domain = $profile->email_domain ?? $profile->getEmailDomain();
        $this->address = $profile->address;
        $this->city = $profile->city;
        $this->province = $profile->province;
        $this->postal_code = $profile->postal_code;
        $this->tax_identification_number = $profile->tax_identification_number;
        $this->enable_pph21 = (bool) $profile->enable_pph21;
        $this->include_bpjs_in_tax = (bool) $profile->include_bpjs_in_tax;
        $this->bpjs_jkk_risk_rate = (float) ($profile->bpjs_jkk_risk_rate ?? 0.0024);
        $this->bpjs_jkm_rate = (float) ($profile->bpjs_jkm_rate ?? 0.0030);
        $this->bpjs_kes_emp_rate = (float) ($profile->bpjs_kes_emp_rate ?? 0.0100);
        $this->bpjs_kes_comp_rate = (float) ($profile->bpjs_kes_comp_rate ?? 0.0400);
        $this->bpjs_jht_emp_rate = (float) ($profile->bpjs_jht_emp_rate ?? 0.0200);
        $this->bpjs_jht_comp_rate = (float) ($profile->bpjs_jht_comp_rate ?? 0.0370);
        $this->bpjs_jp_emp_rate = (float) ($profile->bpjs_jp_emp_rate ?? 0.0100);
        $this->bpjs_jp_comp_rate = (float) ($profile->bpjs_jp_comp_rate ?? 0.0200);
        $this->bpjs_kes_cap = (float) ($profile->bpjs_kes_cap ?? 12000000.00);
        $this->bpjs_jp_cap = (float) ($profile->bpjs_jp_cap ?? 10042300.00);
        $this->bank_name = $profile->bank_name;
        $this->bank_account_number = $profile->bank_account_number;
        $this->bank_account_name = $profile->bank_account_name;
        $this->currentLogoUrl = $profile->logo_url;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:150',
            'email_domain' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_identification_number' => 'nullable|string|max:50',
            'enable_pph21' => 'boolean',
            'include_bpjs_in_tax' => 'boolean',
            'bpjs_jkk_risk_rate' => 'numeric|min:0|max:1',
            'bpjs_jkm_rate' => 'numeric|min:0|max:1',
            'bpjs_kes_emp_rate' => 'numeric|min:0|max:1',
            'bpjs_kes_comp_rate' => 'numeric|min:0|max:1',
            'bpjs_jht_emp_rate' => 'numeric|min:0|max:1',
            'bpjs_jht_comp_rate' => 'numeric|min:0|max:1',
            'bpjs_jp_emp_rate' => 'numeric|min:0|max:1',
            'bpjs_jp_comp_rate' => 'numeric|min:0|max:1',
            'bpjs_kes_cap' => 'numeric|min:0',
            'bpjs_jp_cap' => 'numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:150',
            'logo' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'logo.image' => 'File logo harus berupa gambar (jpg, png, jpeg).',
            'logo.max' => 'Ukuran file logo maksimal 2MB.',
        ];
    }

    public function save(CompanyProfileService $service): void
    {
        abort_unless(auth()->user()?->hasRole(['admin', 'direktur']), 403);

        $this->validate();

        try {
            $profile = $service->updateProfile([
                'name' => $this->name,
                'code' => $this->code,
                'email' => $this->email,
                'phone' => $this->phone,
                'website' => $this->website,
                'email_domain' => $this->email_domain,
                'address' => $this->address,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postal_code,
                'tax_identification_number' => $this->tax_identification_number,
                'enable_pph21' => $this->enable_pph21,
                'include_bpjs_in_tax' => $this->include_bpjs_in_tax,
                'bpjs_jkk_risk_rate' => $this->bpjs_jkk_risk_rate,
                'bpjs_jkm_rate' => $this->bpjs_jkm_rate,
                'bpjs_kes_emp_rate' => $this->bpjs_kes_emp_rate,
                'bpjs_kes_comp_rate' => $this->bpjs_kes_comp_rate,
                'bpjs_jht_emp_rate' => $this->bpjs_jht_emp_rate,
                'bpjs_jht_comp_rate' => $this->bpjs_jht_comp_rate,
                'bpjs_jp_emp_rate' => $this->bpjs_jp_emp_rate,
                'bpjs_jp_comp_rate' => $this->bpjs_jp_comp_rate,
                'bpjs_kes_cap' => $this->bpjs_kes_cap,
                'bpjs_jp_cap' => $this->bpjs_jp_cap,
                'bank_name' => $this->bank_name,
                'bank_account_number' => $this->bank_account_number,
                'bank_account_name' => $this->bank_account_name,
            ], $this->logo);

            $this->currentLogoUrl = $profile->logo_url;
            $this->logo = null;
            $this->dispatch('company-profile-updated', logoUrl: $profile->logo_url, code: $profile->getDisplayCode());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Profil perusahaan berhasil diperbarui.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.company-profile.index');
    }
}
