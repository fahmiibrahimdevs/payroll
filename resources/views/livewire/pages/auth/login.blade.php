<?php

use App\Livewire\Forms\LoginForm;
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

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
    <div class="login-brand">
        <img src="{{ asset('assets/img/larashiz-logo.jpg') }}" alt="Larashiz" width="100" height="100" class="shadow-light rounded-circle">
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h4>Login</h4>
        </div>

        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-info alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <form wire:submit="login">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input wire:model="form.email" id="email" type="email" class="form-control @error('form.email') is-invalid @enderror" name="email" tabindex="1" required autofocus autocomplete="username">
                    @error('form.email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="d-block">
                        <label for="password" class="control-label">Password</label>
                        @if (Route::has('password.request'))
                            <div class="float-right">
                                <a href="{{ route('password.request') }}" class="text-small" wire:navigate>
                                    Lupa Password?
                                </a>
                            </div>
                        @endif
                    </div>
                    <input wire:model="form.password" id="password" type="password" class="form-control @error('form.password') is-invalid @enderror" name="password" tabindex="2" required autocomplete="current-password">
                    @error('form.password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input wire:model="form.remember" type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                        <label class="custom-control-label" for="remember-me">Ingat Saya</label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4" wire:loading.attr="disabled"><span wire:loading.remove>Login</span><span wire:loading><i class="fas fa-spinner fa-spin"></i> Memproses...</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 text-muted text-center">
        Belum punya akun? <a href="{{ route('register') }}" wire:navigate>Daftar Sekarang</a>
    </div>
    <div class="simple-footer">
        Copyright &copy; {{ config('app.name', 'Larashiz') }} {{ date('Y') }}
    </div>
</div>
