<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = false;

        $skipEmailVerification = app()->environment(['local', 'development']);

        if ($skipEmailVerification) {
            $validated['email_verified_at'] = now();
        }

        $user = User::create($validated);
        $user->syncRoles(['user']);

        if (! $skipEmailVerification) {
            event(new Registered($user));
        }

        session()->flash('status', 'Pendaftaran berhasil! Akun Anda berstatus nonaktif dan sedang menunggu aktivasi oleh Admin.');

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-8 offset-lg-2 col-xl-6 offset-xl-3">
    <div class="login-brand">
        <img src="{{ asset('assets/img/larashiz-logo.jpg') }}" alt="Larashiz" width="100" height="100" class="shadow-light rounded-circle">
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h4>Daftar Akun Baru</h4>
        </div>

        <div class="card-body">
            <form wire:submit="register">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input wire:model="name" id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" required autofocus autocomplete="name">
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input wire:model="email" id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" required autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="password" class="d-block">Password</label>
                            <input wire:model="password" id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="password_confirmation" class="d-block">Konfirmasi Password</label>
                            <input wire:model="password_confirmation" id="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required autocomplete="new-password">
                            @error('password_confirmation')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" wire:loading.attr="disabled"><span wire:loading.remove>Daftar Sekarang</span><span wire:loading><i class="fas fa-spinner fa-spin"></i> Mendaftar...</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 text-muted text-center">
        Sudah punya akun? <a href="{{ route('login') }}" wire:navigate>Login Disini</a>
    </div>
    <div class="simple-footer">
        Copyright &copy; {{ config('app.name', 'Larashiz') }} {{ date('Y') }}
    </div>
</div>
