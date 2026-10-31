<div>
    <section class="section">
        <div class="section-header" style="border-bottom: 0; padding-bottom: 0;">
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
                <div class="breadcrumb-item">Riwayat Notifikasi</div>
            </div>
        </div>

        <div class="section-body">
            <h2 class="section-title">Riwayat Notifikasi</h2>
            <p class="section-lead" style="color: #334155;">
                Seluruh riwayat notifikasi sistem Anda: persetujuan lembur, kasbon, slip gaji, dan lainnya.
            </p>

            <div class="card" style="border-radius: 14px; border: 1px solid #e9edf2; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px; border-bottom: 1px solid #eef2f7; border-radius: 14px 14px 0 0;">
                    <ul class="nav nav-pills" style="gap: 6px;">
                        <li class="nav-item">
                            <button type="button" wire:click="$set('filter', 'all')" class="btn {{ $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius: 9999px; font-size: 12.5px; padding: 6px 16px;">
                                Semua
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" wire:click="$set('filter', 'unread')" class="btn {{ $filter === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius: 9999px; font-size: 12.5px; padding: 6px 16px;">
                                Belum Dibaca @if ($unreadCount > 0) <span class="badge badge-light font-weight-600" style="font-size: 10px; border-radius: 9999px; padding: 2px 7px;">{{ $unreadCount }}</span> @endif
                            </button>
                        </li>
                    </ul>
                    @if ($unreadCount > 0)
                        <button type="button" wire:click="markAllRead" wire:loading.attr="disabled" class="btn btn-outline-primary" style="border-radius: 9999px; font-size: 12.5px; padding: 6px 16px;">
                            <i class="fas fa-check-double mr-1"></i> Tandai Semua Dibaca
                        </button>
                    @endif
                </div>

                <div class="card-body p-0">
                    @if ($history === null)
                        <div class="p-4" style="color: #334155;">Silakan masuk untuk melihat notifikasi.</div>
                    @else
                        <div wire:loading.delay class="text-center p-2" style="color: #475569; font-size: 12px;">
                            <i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat...
                        </div>
                        @forelse ($history as $notification)
                            <div wire:key="notif-page-{{ $notification->id }}" class="d-flex align-items-start" style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                                <div class="text-white mr-3" style="width: 38px; height: 38px; line-height: 38px; border-radius: 10px; font-size: 14px; text-align: center; flex-shrink: 0; {{ \App\Services\NotificationService::iconGradient($notification->data['level'] ?? 'info') }}">
                                    <i class="{{ $notification->data['icon'] ?? 'far fa-bell' }}"></i>
                                </div>
                                <div style="flex-grow: 1; min-width: 0;">
                                    <div class="d-flex flex-wrap align-items-center" style="gap: 6px;">
                                        <span class="text-dark font-weight-600" style="font-size: 13px;">{{ $notification->data['title'] ?? 'Notifikasi' }}</span>
                                        @if ($notification->read_at === null)
                                            <span class="badge badge-primary font-weight-600" style="font-size: 9px; border-radius: 9999px; padding: 2px 8px;">Baru</span>
                                        @endif
                                        <span style="font-size: 11px; color: #475569;">{{ $notification->created_at->locale('id')->translatedFormat('d M Y H:i') }}</span>
                                    </div>
                                    <div style="font-size: 12.5px; line-height: 1.5; font-weight: 400; color: #334155; margin-top: 2px;">
                                        {{ $notification->data['body'] ?? '' }}
                                    </div>
                                    @if (!empty($notification->data['url']))
                                        <a href="{{ $notification->data['url'] }}" style="font-size: 12px; text-decoration: none; margin-top: 4px; display: inline-block;">
                                            Buka terkait <i class="fas fa-chevron-right ml-1" style="font-size: 9px;"></i>
                                        </a>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 ml-2">
                                    @if ($notification->read_at === null)
                                        <button type="button" wire:click="markRead('{{ $notification->id }}')" wire:loading.attr="disabled" class="btn btn-sm btn-outline-primary" style="border-radius: 9999px; font-size: 11.5px; padding: 4px 12px;" wire:target="markRead('{{ $notification->id }}')">
                                            <i class="fas fa-check-double mr-1"></i> Tandai Dibaca
                                        </button>
                                    @else
                                        <span style="font-size: 11.5px; color: #475569;"><i class="fas fa-check mr-1" style="color: #16a34a;"></i> Dibaca</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 px-4">
                                <i class="fas fa-inbox mb-3" style="font-size: 34px; color: #94a3b8; display: block;"></i>
                                <div class="font-weight-600 text-dark" style="font-size: 14px;">Tidak Ada Notifikasi</div>
                                <div class="mt-1" style="font-size: 12.5px; color: #475569;">
                                    {{ $filter === 'unread' ? 'Semua notifikasi sudah dibaca. Kerja bagus!' : 'Riwayat notifikasi Anda akan tampil di sini.' }}
                                </div>
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>

            @if ($history !== null && $history->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
