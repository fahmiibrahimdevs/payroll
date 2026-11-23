<?php

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // Karyawan corporate email is managed by HRD and immutable from profile self-service
        if ($user->hasRole('karyawan')) {
            $this->email = $user->email;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        if ($user->hasRole('karyawan')) {
            $validated['email'] = $user->email;
        }

        $user->fill($validated);
        $changedFields = array_keys($user->getDirty());

        $user->save();

        if ($changedFields !== []) {
            app(ActivityLogService::class)->info(
                action: 'PROFILE_UPDATED',
                module: 'users',
                message: 'User profile updated',
                context: [
                    'user_id' => $user->id,
                    'changed_fields' => $changedFields,
                ],
                userId: $user->id
            );
        }

        $this->dispatch('profile-updated', name: $user->name);
        session()->flash('profile_status', 'Informasi profil berhasil disimpan!');
    }
}; ?>

<div>
    @if (session('profile_status'))
        <div class="alert alert-success alert-dismissible show fade">
            <div class="alert-body">
                <button class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
                <i class="fas fa-check-circle mr-2"></i> {{ session('profile_status') }}
            </div>
        </div>
    @endif

    <form wire:submit="updateProfileInformation">
        <div class="form-group">
            <label for="profile_name">Nama Lengkap</label>
            <input wire:model="name" id="profile_name" type="text" class="form-control @error('name') is-invalid @enderror" required autocomplete="name">
            @error('name')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="profile_email">Alamat Email</label>
            <input wire:model="email" id="profile_email" type="email" class="form-control @error('email') is-invalid @enderror" required autocomplete="username" @if (auth()->user()->hasRole('karyawan')) readonly @endif>
            @if (auth()->user()->hasRole('karyawan'))
                <small class="d-block mt-1 font-weight-500" style="color: #475569; font-size: 12px;">Email resmi perusahaan dikelola oleh Administrator HRD.</small>
            @endif
            @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"><span wire:loading.remove><i class="fas fa-save mr-1"></i> Simpan Perubahan</span><span wire:loading><i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...</span></button>
        </div>
    </form>
</div>
