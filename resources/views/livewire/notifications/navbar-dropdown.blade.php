{{-- Dropdown bel: tindakan perlu perhatian (computed) + riwayat notifikasi (database) --}}
<div style="display: contents;" wire:poll.60s>
<li class="dropdown dropdown-list-toggle">
    <a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg {{ count($actionItems) > 0 ? 'beep' : '' }}" title="Tindakan Perlu Perhatian">
        <i class="far fa-bell"></i>
    </a>
    <div class="dropdown-menu dropdown-list dropdown-menu-right shadow-md" style="border-radius: 12px; border: 1px solid #e9edf2; overflow: hidden; width: 360px;">
        <div class="dropdown-header d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 14px 18px;">
            <span class="font-weight-600 text-dark" style="font-size: 13.5px;">Tindakan Perlu Perhatian</span>
            @if (count($actionItems) > 0)
                <span class="badge badge-warning font-weight-600" style="font-size: 10.5px; border-radius: 9999px; padding: 3px 8px;">
                    {{ count($actionItems) }} Hal
                </span>
            @endif
        </div>
        <div class="dropdown-list-content dropdown-list-icons" style="max-height: 360px; overflow-y: auto;">
            @forelse ($actionItems as $item)
                <a href="{{ $item['url'] }}" class="dropdown-item dropdown-item-unread" style="padding: 12px 16px; border-bottom: 1px solid #f8fafc; text-decoration: none; display: flex; align-items: flex-start;">
                    <div class="dropdown-item-icon text-white mr-3" style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; font-size: 13px; text-align: center; flex-shrink: 0; {{ $item['icon_style'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </div>
                    <div class="dropdown-item-desc" style="margin-left: 0; flex-grow: 1;">
                        @if (!empty($item['title']))
                            <div class="text-dark font-weight-600" style="font-size: 12px; line-height: 1.35; margin-bottom: 2px;">
                                {{ $item['title'] }}
                            </div>
                            <div style="font-size: 11.5px; line-height: 1.45; font-weight: 400; color: #475569;">
                                {{ $item['text'] }}
                            </div>
                        @else
                            <div style="font-size: 12px; line-height: 1.45; font-weight: 400; color: #334155;">
                                {{ $item['text'] }}
                            </div>
                        @endif
                        <div class="time {{ $item['type'] === 'danger' ? 'text-danger' : ($item['type'] === 'warning' ? 'text-warning' : 'text-primary') }}" style="font-size: 11px; margin-top: 5px; font-weight: 600;">
                            {{ $item['action_label'] }} &rarr;
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-4 px-3 text-muted">
                    <i class="far fa-bell-slash mb-2" style="font-size: 26px; color: #94a3b8; display: block;"></i>
                    <div class="font-weight-600 text-dark" style="font-size: 13px;">Semua Selesai!</div>
                    <div class="small text-muted mt-1" style="font-size: 11.5px;">Tidak ada tindakan yang memerlukan perhatian saat ini.</div>
                </div>
            @endforelse
        </div>
        <div class="dropdown-footer text-center" style="background-color: #fafbfc; border-top: 1px solid #f1f5f9; padding: 10px 15px;">
            <a href="{{ route('dashboard') }}" class="font-weight-600 text-primary" style="font-size: 12px; text-decoration: none;">
                Buka Dashboard Utama <i class="fas fa-chevron-right ml-1" style="font-size: 9px;"></i>
            </a>
        </div>
    </div>
</li>
<li class="dropdown dropdown-list-toggle">
    <a href="#" data-toggle="dropdown" class="nav-link nav-link-lg {{ $unreadCount > 0 ? 'beep' : '' }}" title="Riwayat Notifikasi">
        <span class="position-relative">
            <i class="fas fa-history"></i>
            @if ($unreadCount > 0)
                <span class="badge badge-danger font-weight-600" style="font-size: 9px; border-radius: 9999px; padding: 1px 5px; position: absolute; top: -7px; right: -10px;">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </span>
    </a>
    <div class="dropdown-menu dropdown-list dropdown-menu-right shadow-md" style="border-radius: 12px; border: 1px solid #e9edf2; overflow: hidden; width: 380px;">
        <div class="dropdown-header d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 14px 18px;">
            <span class="font-weight-600 text-dark" style="font-size: 13.5px;">Riwayat Notifikasi</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" wire:loading.attr="disabled" class="btn btn-link text-primary p-0 font-weight-600" style="font-size: 11px; text-decoration: none;">
                    Tandai semua dibaca
                </button>
            @endif
        </div>
        <div class="dropdown-list-content" style="max-height: 380px; overflow-y: auto;">
            @forelse ($history as $notification)
                <div wire:key="notif-{{ $notification['id'] }}" class="d-flex align-items-start" style="padding: 10px 12px 10px 16px; border-bottom: 1px solid #f8fafc; {{ $notification['is_unread'] ? 'background-color: #f8fafc;' : '' }}">
                    <a href="#" wire:click="open('{{ $notification['id'] }}')" wire:loading.attr="disabled" class="d-flex align-items-start text-decoration-none" style="flex-grow: 1; min-width: 0;">
                        <div class="text-white mr-3" style="width: 32px; height: 32px; line-height: 32px; border-radius: 9px; font-size: 12px; text-align: center; flex-shrink: 0; {{ $notification['icon_style'] }}">
                            <i class="{{ $notification['data']['icon'] ?? 'far fa-bell' }}"></i>
                        </div>
                        <div style="flex-grow: 1; min-width: 0;">
                            <div class="text-dark font-weight-600" style="font-size: 12px; line-height: 1.35;">
                                {{ $notification['data']['title'] ?? 'Notifikasi' }}
                                @if ($notification['is_unread'])
                                    <span class="badge badge-primary" style="font-size: 8.5px; border-radius: 9999px; padding: 1px 6px; vertical-align: middle;">Baru</span>
                                @endif
                            </div>
                            <div style="font-size: 11.5px; line-height: 1.45; font-weight: 400; color: #475569;">
                                {{ $notification['data']['body'] ?? '' }}
                            </div>
                            <div style="font-size: 10.5px; margin-top: 3px; color: #475569;">
                                {{ $notification['time'] }}
                            </div>
                        </div>
                    </a>
                    @if ($notification['is_unread'])
                        <button type="button" wire:click="markRead('{{ $notification['id'] }}')" wire:loading.attr="disabled" class="btn btn-sm text-primary p-1 ml-1" style="font-size: 11px; flex-shrink: 0;" title="Tandai sudah dibaca" wire:target="markRead('{{ $notification['id'] }}')">
                            <i class="fas fa-check-double"></i>
                        </button>
                    @endif
                </div>
            @empty
                <div class="text-center py-4 px-3">
                    <i class="fas fa-inbox mb-2" style="font-size: 26px; color: #94a3b8; display: block;"></i>
                    <div class="font-weight-600 text-dark" style="font-size: 13px;">Belum Ada Notifikasi</div>
                    <div class="mt-1" style="font-size: 11.5px; color: #475569;">Riwayat notifikasi Anda akan tampil di sini.</div>
                </div>
            @endforelse
        </div>
        <div class="dropdown-footer text-center" style="background-color: #fafbfc; border-top: 1px solid #f1f5f9; padding: 10px 15px;">
            <a href="{{ route('notifications.index') }}" class="font-weight-600 text-primary" style="font-size: 12px; text-decoration: none;">
                Lihat Semua Riwayat <i class="fas fa-chevron-right ml-1" style="font-size: 9px;"></i>
            </a>
        </div>
    </div>
</li>
</div>
