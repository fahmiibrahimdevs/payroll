<?php

use App\Livewire\Forms\LoginForm;
use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
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
    $companyName = ($company?->name && !in_array($company->name, ['PT Test', 'Laravel'], true))
        ? $company->name
        : 'Larashiz';
    $emailDomain = $company?->email_domain ?: 'perusahaan.com';
    $isTestEnvironment = str_contains(request()->getHost(), 'test-')
        || str_contains((string) config('app.url'), 'test-')
        || str_contains(config('app.name'), 'Testing')
        || str_contains(request()->getHost(), '.test')
        || app()->environment(['local', 'development', 'testing']);
@endphp

<div class="w-full max-w-lg mx-auto">
    <div class="bg-white rounded-none sm:rounded-2xl border-0 sm:border sm:border-slate-200 shadow-none sm:shadow-2xl px-4 py-6 sm:px-14 sm:py-12">
        
        <!-- App Logo & Organization Header -->
        <div class="text-center mb-5">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl overflow-hidden shadow-md border border-slate-200 bg-white mb-3">
                <img src="{{ $company?->logo_url ?? asset('assets/img/larashiz-logo.jpg') }}" alt="{{ $companyName }}" class="w-16 h-16 max-w-full max-h-full object-contain p-1.5 block">
            </div>
            <div class="text-xl font-bold !text-slate-900 tracking-tight">{{ $companyName }}</div>
            <div class="text-xs sm:text-sm font-medium text-slate-600 mt-1.5 flex items-center justify-center gap-2">
                <span>Sistem Manajemen SDM & Penggajian</span>
                @if ($isTestEnvironment)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase border"
                          style="background-color: #fef3c7; color: #92400e; border-color: #fde68a;">
                        <span class="w-1.5 h-1.5 rounded-full mr-1" style="background-color: #d97706;"></span>
                        TEST
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase border"
                          style="background-color: #d1fae5; color: #065f46; border-color: #a7f3d0;">
                        <span class="w-1.5 h-1.5 rounded-full mr-1" style="background-color: #059669;"></span>
                        PROD
                    </span>
                @endif
            </div>
        </div>

        <!-- Greeting Section -->
        <div class="text-center mb-5 pb-0.5">
            <h1 class="text-2xl sm:text-[26px] font-bold !text-slate-900 tracking-tight mb-1.5">Selamat Datang</h1>
            <p class="text-sm font-normal text-slate-600 !opacity-100 mb-0">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        @if (session('status'))
            <div class="bg-blue-50 border border-blue-200 text-blue-900 text-xs sm:text-sm font-medium rounded-xl py-2.5 px-3.5 mb-4 flex items-center justify-between">
                <span>{{ session('status') }}</span>
                <button type="button" class="text-blue-600 hover:text-blue-800 text-lg leading-none" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <!-- Form Login -->
        <form wire:submit="login">
            <!-- Email -->
            <div class="mb-4 text-left">
                <label for="email" class="block text-sm font-semibold !text-slate-900 mb-1.5">Email</label>
                <input wire:model="form.email" id="email" type="email"
                    class="form-control w-full h-12 rounded-xl border border-slate-300 bg-white px-4 text-sm !text-slate-900 placeholder:text-slate-400 focus:border-[#203a82] focus:ring-4 focus:ring-[#203a82]/15 focus:outline-none transition duration-150 @error('form.email') !border-red-500 !ring-red-200 @enderror"
                    name="email" tabindex="1" required autofocus autocomplete="username"
                    placeholder="{{ 'nama@' . $emailDomain }}">
                @error('form.email')
                    <div class="text-red-600 text-xs font-semibold mt-1.5">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-4 text-left" x-data="{ showPassword: false }">
                <label for="password" class="block text-sm font-semibold !text-slate-900 mb-1.5">Password</label>
                <div class="relative w-full">
                    <input wire:model="form.password" id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control w-full h-12 rounded-xl border border-slate-300 bg-white pl-4 pr-12 text-sm !text-slate-900 placeholder:text-slate-400 focus:border-[#203a82] focus:ring-4 focus:ring-[#203a82]/15 focus:outline-none transition duration-150 @error('form.password') !border-red-500 !ring-red-200 @enderror"
                        name="password" tabindex="2" required autocomplete="current-password"
                        placeholder="••••••••">
                    <button type="button" @click="showPassword = !showPassword"
                        tabindex="-1"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 focus:outline-none flex items-center justify-center w-8 h-8 cursor-pointer transition-colors"
                        title="Tampilkan / Sembunyikan Password">
                        <i :class="showPassword ? 'far fa-eye-slash' : 'far fa-eye'" class="text-base"></i>
                    </button>
                </div>
                @error('form.password')
                    <div class="text-red-600 text-xs font-semibold mt-1.5">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="mb-5 text-left">
                <label class="inline-flex items-center cursor-pointer select-none text-slate-700 hover:text-slate-900" for="remember-me">
                    <input wire:model="form.remember" type="checkbox" name="remember" tabindex="3" id="remember-me"
                        class="w-4 h-4 rounded border-slate-400 text-[#203a82] focus:ring-[#203a82]/20 cursor-pointer accent-[#203a82]">
                    <span class="text-xs sm:text-sm font-normal ml-2.5 text-slate-700">Ingat saya di perangkat ini</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button type="submit" tabindex="4" wire:loading.attr="disabled"
                    class="btn w-full h-12 !bg-[#203a82] hover:!bg-[#1a2e68] text-white font-semibold text-sm sm:text-base rounded-xl flex items-center justify-center shadow-lg transition duration-200 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed border-0">
                    <span wire:loading.remove>Masuk</span>
                    <span wire:loading class="inline-flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i> Memproses...</span>
                </button>
            </div>
        </form>

        <!-- Card Footer -->
        <div class="text-center mt-6 pt-2 text-xs sm:text-sm text-slate-600 !opacity-100">
            Belum punya akun?
            <a href="javascript:void(0)" onclick="if(window.Swal){Swal.fire({title:'Informasi Akun',text:'Silakan hubungi Administrator HR / IT untuk pendaftaran atau pembuatan akun Anda.',icon:'info',confirmButtonColor:'#203a82'})}else{alert('Silakan hubungi Administrator HR / IT untuk pendaftaran atau pembuatan akun Anda.')}"
                class="!text-blue-700 hover:!text-blue-900 font-semibold hover:underline ml-1 cursor-pointer transition-colors">
                Hubungi Administrator
            </a>
        </div>

    </div>
</div>
