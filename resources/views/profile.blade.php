<x-app-layout>
    <x-slot name="title">
        Profile
    </x-slot>

    <x-slot name="header">
        <h1>Profile</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Profile</div>
        </div>
    </x-slot>

    <h2 class="section-title">Hai, {{ auth()->user()->name }}!</h2>
    <p class="section-lead mb-3">
        Kelola informasi akun, email, dan keamanan kata sandi Anda di halaman ini.
    </p>

    <div class="row">
        <!-- Kolom Kiri: Kartu Profil & Hapus Akun -->
        <div class="col-12 col-md-12 col-lg-5">
            <div class="card profile-widget">
                <div class="profile-widget-header">
                    <img alt="avatar" src="{{ asset('assets/img/avatar/avatar-1.png') }}"
                        class="rounded-circle profile-widget-picture">
                    <div class="profile-widget-items">
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Role</div>
                            <div class="profile-widget-item-value">
                                <span class="badge badge-primary text-capitalize">
                                    {{ auth()->user()->roles->first()->display_name ?? (auth()->user()->roles->first()->name ?? 'User') }}
                                </span>
                            </div>
                        </div>
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Status</div>
                            <div class="profile-widget-item-value">
                                <span class="badge {{ auth()->user()->is_active ? 'badge-success' : 'badge-warning' }}">
                                    {{ auth()->user()->is_active ? 'Aktif' : 'Pending' }}
                                </span>
                            </div>
                        </div>
                        <div class="profile-widget-item">
                            <div class="profile-widget-item-label">Bergabung</div>
                            <div class="profile-widget-item-value text-small">
                                {{ auth()->user()->created_at ? auth()->user()->created_at->format('d M Y') : '-' }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="profile-widget-description">
                    <div class="profile-widget-name">
                        {{ auth()->user()->name }}
                        <div class="text-muted d-inline font-weight-normal">
                            <div class="slash"></div> {{ auth()->user()->email }}
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        Akun ini terdaftar di portal sistem <strong>Larashiz</strong> dengan hak akses
                        <strong class="text-primary text-capitalize">{{ auth()->user()->roles->first()->name ?? 'user' }}</strong>.
                    </p>
                </div>
            </div>

            @if (! auth()->user()->hasRole('karyawan'))
            <!-- Delete Account Card -->
            <div class="card card-danger">
                <div class="card-header">
                    <h4 class="text-danger"><i class="fas fa-trash-alt mr-2"></i> Hapus Akun</h4>
                </div>
                <div class="card-body">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
            @endif
        </div>

        <!-- Kolom Kanan: Form Edit Profil & Ganti Password -->
        <div class="col-12 col-md-12 col-lg-7">
            <!-- Update Profile Information Form -->
            <div class="card card-primary mb-4">
                <div class="card-header">
                    <h4><i class="fas fa-user-edit mr-2 text-primary"></i> Edit Informasi Profil</h4>
                </div>
                <div class="card-body">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <!-- Update Password Form -->
            <div class="card card-warning">
                <div class="card-header">
                    <h4><i class="fas fa-key mr-2 text-warning"></i> Perbarui Kata Sandi</h4>
                </div>
                <div class="card-body">
                    <livewire:profile.update-password-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
