<?php

use App\Livewire\Forms\LoginForm;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('PT. SINTESA NIAGA KARYA - Login')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false));
    }
}; ?>

@php
    $company = CompanyProfile::getProfile();
    $companyName = ($company?->name && ! in_array(strtoupper(trim($company->name)), ['PT TEST', 'LARAVEL', 'PT LARASHIZ INDONESIA', 'LARASHIZ'], true))
        ? $company->name
        : 'PT. SINTESA NIAGA KARYA';
    $emailDomain = ($company?->email_domain && ! in_array(strtolower(trim($company->email_domain)), ['larashiz.com', 'example.com'], true))
        ? $company->email_domain
        : 'sintesaniagakarya.com';
    $brandLogoUrl = $company?->logo_path
        ? $company->logo_url
        : asset('assets/img/sintesa-icon.png');
    $hasCustomUploadedLogo = (bool) $company?->logo_path;
    $host = strtolower((string) request()->getHost());
    $appUrl = strtolower((string) config('app.url'));
    $isPercetakan = str_contains($host, 'percetakansubang') || str_contains($appUrl, 'percetakansubang');
    $brandSubtitle = $isPercetakan ? 'Percetakansubang' : 'Workshop';
    $isTestEnvironment = str_contains($host, 'test-')
        || str_contains($appUrl, 'test-')
        || str_contains(config('app.name'), 'Testing')
        || str_contains($host, '.test')
        || app()->environment(['local', 'development', 'testing']);
@endphp

@push('styles')
<style>
    .pnj-login-wrap {
        width: 100%;
        max-width: 448px;
        margin: 0 auto;
    }

    .pnj-login-card {
        background-color: transparent;
        border-radius: 0;
        border: 0;
        box-shadow: none;
        padding: 24px 16px;
    }

    @media (min-width: 640px) {
        .pnj-login-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            padding: 40px 32px;
        }
    }

    .pnj-brand-section {
        text-align: center;
        margin-bottom: 24px;
    }

    .pnj-brand-box {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        overflow: hidden;
    }

    .pnj-brand-box--navy {
        background-color: #1e3a8a;
        box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.25), 0 4px 6px -4px rgba(30, 58, 138, 0.2);
        padding: 14px;
    }

    .pnj-brand-box--custom {
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
        padding: 8px;
    }

    .pnj-brand-title {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        line-height: 1.3;
        margin: 0 0 2px 0;
    }

    @media (min-width: 640px) {
        .pnj-brand-title {
            font-size: 22px;
        }
    }

    .pnj-brand-subtitle {
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        margin: 0;
        line-height: 1.4;
    }

    .pnj-env-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        margin-top: 10px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        line-height: 1;
    }

    .pnj-env-status--prod {
        color: #059669;
    }

    .pnj-env-status--test {
        color: #d97706;
    }

    .pnj-env-dot {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        display: inline-block;
        flex-shrink: 0;
    }

    .pnj-env-dot--prod {
        background-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.16);
    }

    .pnj-env-dot--test {
        background-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
    }

    .pnj-greeting {
        text-align: center;
        margin-bottom: 28px;
    }

    .pnj-greeting-title {
        font-size: 26px;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
        margin: 0 0 8px 0;
    }

    @media (min-width: 640px) {
        .pnj-greeting-title {
            font-size: 30px;
        }
    }

    .pnj-greeting-subtitle {
        font-size: 14px;
        font-weight: 400;
        color: #4b5563;
        margin: 0;
        line-height: 1.45;
    }

    .pnj-field {
        margin-bottom: 16px;
        text-align: left;
    }

    .pnj-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }

    .pnj-input-wrap {
        position: relative;
        width: 100%;
    }

    .pnj-input {
        width: 100%;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background-color: #ffffff;
        font-size: 14px;
        line-height: 1.4;
        color: #111827;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .pnj-input--password {
        padding-right: 48px;
    }

    .pnj-input::placeholder {
        color: #9ca3af;
    }

    .pnj-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(191, 219, 254, 0.8);
    }

    .pnj-input--error {
        border-color: #ef4444;
    }

    .pnj-input--error:focus {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(254, 202, 202, 0.8);
    }

    .pnj-eye-btn {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border: 0;
        background: transparent;
        color: #6b7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border-radius: 6px;
        transition: color 0.15s ease;
    }

    .pnj-eye-btn:hover {
        color: #374151;
    }

    .pnj-eye-btn:focus {
        outline: none;
    }

    .pnj-remember {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 24px;
    }

    .pnj-remember input[type="checkbox"] {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        border: 1px solid #d1d5db;
        accent-color: #1e3a8a;
        cursor: pointer;
        margin: 0;
    }

    .pnj-submit-btn {
        width: 100%;
        background-color: #1e3a8a;
        color: #ffffff;
        padding: 12px 16px;
        border: 0;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .pnj-submit-btn:hover:not(:disabled) {
        background-color: #1e40af;
    }

    .pnj-submit-btn:focus {
        outline: none;
        box-shadow: 0 0 0 4px rgba(147, 197, 253, 0.7);
    }

    .pnj-submit-btn:disabled {
        opacity: 0.75;
        cursor: not-allowed;
    }

    .pnj-footer {
        text-align: center;
        margin-top: 24px;
        font-size: 14px;
        color: #4b5563;
        line-height: 1.5;
    }

    .pnj-footer-link {
        color: #2563eb !important;
        font-weight: 500;
        text-decoration: none;
        margin-left: 3px;
        cursor: pointer;
    }

    .pnj-footer-link:hover {
        color: #1d4ed8 !important;
        text-decoration: underline;
    }

    .pnj-error-text {
        color: #dc2626;
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
    }
</style>
@endpush

<div class="pnj-login-wrap">
    <div class="pnj-login-card">

        <!-- Logo & Title Section -->
        <div class="pnj-brand-section">
            <div class="pnj-brand-box {{ $hasCustomUploadedLogo ? 'pnj-brand-box--custom' : 'pnj-brand-box--navy' }}">
                <img src="{{ $brandLogoUrl }}"
                     alt="{{ $companyName }}"
                     class="w-full h-full object-contain block">
            </div>

            <h1 class="pnj-brand-title">{{ $companyName }}</h1>

            <p class="pnj-brand-subtitle">{{ $brandSubtitle }}</p>

            <div>
                @if ($isTestEnvironment)
                    <span class="pnj-env-status pnj-env-status--test">
                        <span class="pnj-env-dot pnj-env-dot--test"></span>
                        <span>Test Environment</span>
                    </span>
                @else
                    <span class="pnj-env-status pnj-env-status--prod">
                        <span class="pnj-env-dot pnj-env-dot--prod"></span>
                        <span>Production</span>
                    </span>
                @endif
            </div>
        </div>

        <!-- Welcome Text -->
        <div class="pnj-greeting">
            <h2 class="pnj-greeting-title">Selamat Datang</h2>
            <p class="pnj-greeting-subtitle">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        @if (session('status'))
            <div class="bg-blue-50 border border-blue-200 text-blue-900 text-xs sm:text-sm font-medium rounded-lg py-2.5 px-3.5 mb-4 flex items-center justify-between">
                <span>{{ session('status') }}</span>
                <button type="button" class="text-blue-600 hover:text-blue-800 text-lg leading-none" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <!-- Login Form -->
        <form wire:submit="login">
            <!-- Email Address -->
            <div class="pnj-field">
                <label for="email" class="pnj-label">Email</label>
                <input wire:model="form.email"
                    id="email"
                    type="email"
                    name="email"
                    tabindex="1"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="{{ 'nama@' . $emailDomain }}"
                    class="pnj-input @error('form.email') pnj-input--error @enderror">
                @error('form.email')
                    <div class="pnj-error-text">{{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="pnj-field" x-data="{ showPassword: false }">
                <label for="password" class="pnj-label">Password</label>
                <div class="pnj-input-wrap">
                    <input wire:model="form.password"
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        tabindex="2"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="pnj-input pnj-input--password @error('form.password') pnj-input--error @enderror">
                    <button type="button"
                        @click="showPassword = !showPassword"
                        tabindex="-1"
                        class="pnj-eye-btn"
                        aria-label="Tampilkan atau sembunyikan password"
                        title="Tampilkan / Sembunyikan Password">
                        <i :class="showPassword ? 'far fa-eye-slash' : 'far fa-eye'" class="text-base"></i>
                    </button>
                </div>
                @error('form.password')
                    <div class="pnj-error-text">{{ $message }}</div>
                @enderror
            </div>

            <!-- Remember Me -->
            <div>
                <label class="pnj-remember" for="remember-me">
                    <input wire:model="form.remember" type="checkbox" name="remember" tabindex="3" id="remember-me">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" tabindex="4" wire:loading.attr="disabled" class="pnj-submit-btn">
                <span wire:loading.remove>Masuk</span>
                <span wire:loading class="inline-flex items-center">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Memproses...
                </span>
            </button>

            <!-- Footer -->
            <div class="pnj-footer">
                Belum punya akun?
                <a href="javascript:void(0)"
                   onclick="if(window.Swal){Swal.fire({title:'Informasi Akun',text:'Silakan hubungi Administrator HR / IT PT. SINTESA NIAGA KARYA untuk pendaftaran atau pembuatan akun Anda.',icon:'info',confirmButtonColor:'#1e3a8a'})}else{alert('Silakan hubungi Administrator HR / IT PT. SINTESA NIAGA KARYA untuk pendaftaran atau pembuatan akun Anda.')}"
                   class="pnj-footer-link">
                    Hubungi Administrator
                </a>
            </div>
        </form>

    </div>
</div>
