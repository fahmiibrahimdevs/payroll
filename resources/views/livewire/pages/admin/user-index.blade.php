<?php

use App\Models\User;
use App\Services\UserService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $role = '';

    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'role']);
        $this->resetPage();
    }

    public function confirmToggleStatus(int $userId): void
    {
        if (! auth()->user()->hasRole('admin')) {
            abort(403, 'Akses ditolak.');
        }

        $user = User::find($userId);
        if (! $user || in_array($user->email, UserService::HIDDEN_DUMMY_EMAILS, true)) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Pengguna tidak ditemukan.',
            ]);

            return;
        }

        if ($userId === auth()->id()) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Aksi Ditolak',
                'message' => 'Anda tidak dapat mengubah status akun Anda sendiri.',
            ]);

            return;
        }

        $actionTitle = $user->is_active ? 'Nonaktifkan Akun?' : 'Aktifkan Akun?';
        $actionText = $user->is_active
            ? "Akun {$user->name} ({$user->email}) akan dinonaktifkan."
            : "Akun {$user->name} ({$user->email}) akan diaktifkan kembali.";
        $confirmText = $user->is_active ? 'Ya, Nonaktifkan!' : 'Ya, Aktifkan!';
        $confirmColor = $user->is_active ? '#ffa426' : '#47c363';
        $icon = $user->is_active ? 'warning' : 'question';

        $this->dispatch('swal:confirm', [
            'title' => $actionTitle,
            'text' => $actionText,
            'icon' => $icon,
            'confirmButtonText' => $confirmText,
            'confirmButtonColor' => $confirmColor,
            'action' => 'toggle-user-status',
            'params' => ['id' => $userId],
        ]);
    }

    #[On('toggle-user-status')]
    public function toggleStatus(int $id): void
    {
        if (! auth()->user()->hasRole('admin')) {
            abort(403, 'Akses ditolak.');
        }

        $userService = app(UserService::class);

        try {
            $user = $userService->toggleUserStatus($id, auth()->id());
            $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Akun {$user->name} berhasil {$statusText}.",
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Peringatan',
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan sistem saat memperbarui status akun.',
            ]);
        }
    }

    public function confirmDelete(int $userId): void
    {
        if (! auth()->user()->hasRole('admin')) {
            abort(403, 'Akses ditolak.');
        }

        $user = User::find($userId);
        if (! $user || in_array($user->email, UserService::HIDDEN_DUMMY_EMAILS, true)) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Pengguna tidak ditemukan.',
            ]);

            return;
        }

        if ($userId === auth()->id()) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Aksi Ditolak',
                'message' => 'Anda tidak dapat mengarsipkan akun Anda sendiri.',
            ]);

            return;
        }

        $this->dispatch('swal:confirm-delete', [
            'id' => $userId,
            'title' => $user->name,
            'text' => "Akun {$user->name} ({$user->email}) akan diarsipkan dan dinonaktifkan.",
            'action' => 'delete-user',
            'params' => ['id' => $userId],
        ]);
    }

    #[On('delete-user')]
    public function deleteUser(int $id): void
    {
        if (! auth()->user()->hasRole('admin')) {
            abort(403, 'Akses ditolak.');
        }

        $userService = app(UserService::class);

        try {
            $user = $userService->deleteUser($id, auth()->id());

            $this->dispatch('swal:alert', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Akun {$user->name} ({$user->email}) berhasil diarsipkan.",
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('swal:alert', [
                'type' => 'warning',
                'title' => 'Peringatan',
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('swal:alert', [
                'type' => 'error',
                'title' => 'Gagal Mengarsipkan!',
                'message' => 'Terjadi kesalahan sistem saat mengarsipkan data pengguna.',
            ]);
        }
    }

    public function with(): array
    {
        $userService = app(UserService::class);

        return [
            'users' => $userService->getPaginatedUsers(
                search: $this->search,
                role: $this->role,
                perPage: $this->perPage
            ),
            'statistics' => $userService->getUserStatistics(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h1>Kelola Pengguna</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Kelola Pengguna</div>
        </div>
    </x-slot>

    <!-- Section Title & Lead -->
    <h2 class="section-title">Kelola Pengguna</h2>
    <p class="section-lead mb-3">
        Kelola data seluruh pengguna sistem, penetapan hak akses role, serta status aktifasi akun pengguna.
    </p>

    <!-- Statistic Summary Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Pengguna</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['total']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pengguna Aktif</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['active']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-warning">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Nonaktif</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['inactive']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Administrator</h4>
                    </div>
                    <div class="card-body">
                        {{ number_format($statistics['admins']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table Card -->
    <div class="card">
        <div class="card-header">
            <h4>Daftar Pengguna</h4>
        </div>

        <div class="card-body p-0">
            <!-- Controls: Show Entries & Search Bar -->
            <div class="dt-control-wrapper">
                <div class="dt-show-entries">
                    Show
                    <select wire:model.live="perPage" class="dt-select-entries">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    Entries
                </div>

                <div class="dt-search-wrapper">
                    <span>Search:</span>
                    <input type="text" wire:model.live.debounce.300ms="search" class="dt-search-input" placeholder="Cari nama atau email...">
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-clean">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-center text-nowrap" style="width: 50px;">NO</th>
                            <th class="text-nowrap">NAMA PENGGUNA</th>
                            <th class="text-nowrap">EMAIL</th>
                            <th class="text-center text-nowrap" style="width: 140px;">ROLE</th>
                            <th class="text-center text-nowrap" style="width: 130px;">STATUS AKUN</th>
                            <th class="text-nowrap" style="width: 180px;">TANGGAL DAFTAR</th>
                            <th class="text-center text-nowrap" style="width: 170px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $index => $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td class="text-center font-weight-600 text-muted align-middle">
                                    {{ $users->firstItem() + $index }}
                                </td>
                                <td class="align-middle">
                                    <div class="font-weight-600 text-dark">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="align-middle text-muted">
                                    {{ $user->email }}
                                </td>
                                <td class="align-middle text-center text-nowrap">
                                    @forelse ($user->roles as $role)
                                        <span class="badge {{ $role->name === 'admin' ? 'badge-primary' : 'badge-secondary' }}">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td class="align-middle text-center text-nowrap">
                                    @if ($user->is_active)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock mr-1"></i> Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle text-nowrap text-muted">
                                    {{ $user->created_at ? $user->created_at->translatedFormat('d M Y, H:i') : '-' }}
                                </td>
                                <td class="align-middle text-center text-nowrap">
                                    @if ($user->id === auth()->id())
                                        <span class="badge badge-light border text-muted">Terkunci</span>
                                    @else
                                        <button type="button" wire:click="confirmToggleStatus({{ $user->id }})" wire:loading.attr="disabled" wire:target="confirmToggleStatus({{ $user->id }})" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} mr-1" title="{{ $user->is_active ? 'Nonaktifkan Akun Pengguna' : 'Aktifkan Akun Pengguna' }}"><i wire:loading wire:target="confirmToggleStatus({{ $user->id }})" class="fas fa-spinner fa-spin mr-1"></i><span wire:loading.remove wire:target="confirmToggleStatus({{ $user->id }})"><i class="fas {{ $user->is_active ? 'fa-user-times' : 'fa-user-check' }} mr-1"></i> {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span></button>
                                        <button type="button" wire:click="confirmDelete({{ $user->id }})" wire:loading.attr="disabled" wire:target="confirmDelete({{ $user->id }})" class="btn btn-sm btn-outline-danger" title="Arsipkan Pengguna"><i wire:loading wire:target="confirmDelete({{ $user->id }})" class="fas fa-spinner fa-spin"></i><i wire:loading.remove wire:target="confirmDelete({{ $user->id }})" class="fas fa-user-slash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-users-slash text-muted mb-2" style="font-size: 36px; opacity: 0.4;"></i>
                                        <div class="font-weight-600 mt-2 text-dark">Tidak ada data pengguna</div>
                                        <small class="text-muted d-block mt-1">
                                            @if ($search || $role)
                                                Tidak ditemukan pengguna yang sesuai dengan kriteria pencarian "{{ $search }}".
                                            @else
                                                Belum ada data pengguna yang terdaftar di dalam sistem.
                                            @endif
                                        </small>
                                        @if ($search || $role)
                                            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" wire:target="resetFilters" class="btn btn-primary btn-sm mt-3 px-3"><i wire:loading wire:target="resetFilters" class="fas fa-spinner fa-spin mr-1"></i><i wire:loading.remove wire:target="resetFilters" class="fas fa-undo-alt mr-1"></i> Reset Pencarian</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer: Info & Pagination -->
            <div class="dt-footer-wrapper">
                <div>
                    Showing {{ $users->total() > 0 ? $users->firstItem() : 0 }} to {{ $users->total() > 0 ? $users->lastItem() : 0 }} of {{ $users->total() }} entries
                </div>
                <div>
                    {{ $users->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>
</div>
