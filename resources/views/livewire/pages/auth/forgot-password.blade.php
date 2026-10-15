<?php

use Illuminate\Support\Facades\Password;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        app(ActivityLogService::class)->info(
            action: 'PASSWORD_RESET_LINK_SENT',
            module: 'auth',
            message: 'Password reset link sent',
            context: ['email' => $this->email]
        );

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
    <div class="login-brand">
        <img src="{{ asset('assets/img/larashiz-logo.jpg') }}" alt="Larashiz" width="100" height="100" class="shadow-light rounded-circle">
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <h4>Lupa Password</h4>
        </div>

        <div class="card-body">
            <p class="text-muted">Masukkan email Anda, kami akan mengirimkan tautan reset password ke email Anda.</p>

            @if (session('status'))
                <div class="alert alert-success alert-dismissible show fade">
                    <div class="alert-body">
                        <button class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <form wire:submit="sendPasswordResetLink">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input wire:model="email" id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" tabindex="1" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="2" wire:loading.attr="disabled"><span wire:loading.remove>Kirim Link Reset</span><span wire:loading><i class="fas fa-spinner fa-spin"></i> Mengirim...</span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4 text-muted text-center">
        Ingat password Anda? <a href="{{ route('login') }}" wire:navigate>Kembali ke Login</a>
    </div>
    <div class="simple-footer">
        Copyright &copy; {{ config('app.name', 'Larashiz') }} {{ date('Y') }}
    </div>
</div>
