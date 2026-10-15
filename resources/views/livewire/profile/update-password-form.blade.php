<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Services\ActivityLogService;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        app(ActivityLogService::class)->info(
            action: 'PASSWORD_CHANGED',
            module: 'auth',
            message: 'User password changed',
            context: ['user_id' => $user->id],
            userId: $user->id
        );

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
        session()->flash('password_status', 'Kata sandi Anda berhasil diperbarui!');
    }
}; ?>

<div>
    @if (session('password_status'))
        <div class="alert alert-success alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
                <i class="fas fa-check-circle mr-2"></i> {{ session('password_status') }}
            </div>
        </div>
    @endif

    <form wire:submit="updatePassword">
        <div class="form-group">
            <label for="current_password">Kata Sandi Saat Ini</label>
            <input wire:model="current_password" id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
            @error('current_password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="new_password">Kata Sandi Baru</label>
                    <input wire:model="password" id="new_password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="form-group">
                    <label for="new_password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input wire:model="password_confirmation" id="new_password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password">
                    @error('password_confirmation')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="text-right mt-1">
            <button type="submit" class="btn btn-warning" wire:loading.attr="disabled"><span wire:loading.remove><i class="fas fa-key mr-1"></i> Perbarui Password</span><span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i> Memproses...</span></button>
        </div>
    </form>
</div>
