<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
    <div class="login-brand">
        <img src="{{ asset('assets/img/larashiz-logo.jpg') }}" alt="Larashiz" width="100" height="100" class="shadow-light rounded-circle">
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h4>Konfirmasi Password</h4>
        </div>

        <div class="card-body">
            <p class="text-muted">Ini adalah area aman aplikasi. Harap konfirmasi password Anda sebelum melanjutkan.</p>

            <form wire:submit="confirmPassword">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input wire:model="password" id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" autofocus>
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" wire:loading.attr="disabled"><span wire:loading.remove>Konfirmasi</span><span wire:loading><i class="fas fa-spinner fa-spin"></i> Memvalidasi...</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="simple-footer">
        Copyright &copy; {{ config('app.name', 'Larashiz') }} {{ date('Y') }}
    </div>
</div>
