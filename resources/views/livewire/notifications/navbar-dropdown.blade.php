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
                @php
                    $itemTitle = $item['title'] ?? '';
                    $itemIconStyle = match (true) {
                        str_contains($itemTitle, 'Karyawan Nonaktif'),
                        str_contains($itemTitle, 'Data Payroll'),
                        str_contains($itemTitle, 'Kontrak'),
                        str_contains($itemTitle, 'Cicilan'),
                        str_contains($itemTitle, 'Perlu Diperiksa') => 'background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 5px 12px rgba(109, 40, 217, 0.28);',
                        str_contains($itemTitle, 'Kasbon'),
                        str_contains($itemTitle, 'Pembayaran Kasbon') => 'background: linear-gradient(135deg, #f59e0b 0%, #ea8c00 100%); box-shadow: 0 5px 12px rgba(234, 140, 0, 0.28);',
                        $item['type'] === 'success' => 'background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); box-shadow: 0 5px 12px rgba(22, 163, 74, 0.28);',
                        str_contains($itemTitle, 'Lembur') => 'background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); box-shadow: 0 5px 12px rgba(13, 148, 136, 0.28);',
                        str_contains($itemTitle, 'Penggajian'),
                        str_contains($itemTitle, 'Periode'),
                        str_contains($itemTitle, 'Slip') => 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 5px 12px rgba(37, 99, 235, 0.28);',
                        default => 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 5px 12px rgba(37, 99, 235, 0.28);',
                    };
                @endphp
                <a href="{{ $item['url'] }}" class="dropdown-item dropdown-item-unread" style="padding: 12px 16px; border-bottom: 1px solid #f8fafc; text-decoration: none; display: flex; align-items: flex-start;">
                    <div class="dropdown-item-icon text-white mr-3" style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; font-size: 13px; text-align: center; flex-shrink: 0; {{ $itemIconStyle }}">
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
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                    $levelIconStyle = match ($data['level'] ?? 'info') {
                        'success' => 'background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); box-shadow: 0 5px 12px rgba(22, 163, 74, 0.28);',
                        'danger' => 'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 5px 12px rgba(220, 38, 38, 0.28);',
                        'warning' => 'background: linear-gradient(135deg, #f59e0b 0%, #ea8c00 100%); box-shadow: 0 5px 12px rgba(234, 140, 0, 0.28);',
                        default => 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 5px 12px rgba(37, 99, 235, 0.28);',
                    };
                @endphp
                <div wire:key="notif-{{ $notification->id }}" class="d-flex align-items-start" style="padding: 10px 12px 10px 16px; border-bottom: 1px solid #f8fafc; {{ $isUnread ? 'background-color: #f8fafc;' : '' }}">
                    <a href="#" wire:click="open('{{ $notification->id }}')" wire:loading.attr="disabled" class="d-flex align-items-start text-decoration-none" style="flex-grow: 1; min-width: 0;">
                        <div class="text-white mr-3" style="width: 32px; height: 32px; line-height: 32px; border-radius: 9px; font-size: 12px; text-align: center; flex-shrink: 0; {{ $levelIconStyle }}">
                            <i class="{{ $data['icon'] ?? 'far fa-bell' }}"></i>
                        </div>
                        <div style="flex-grow: 1; min-width: 0;">
                            <div class="text-dark font-weight-600" style="font-size: 12px; line-height: 1.35;">
                                {{ $data['title'] ?? 'Notifikasi' }}
                                @if ($isUnread)
                                    <span class="badge badge-primary" style="font-size: 8.5px; border-radius: 9999px; padding: 1px 6px; vertical-align: middle;">Baru</span>
                                @endif
                            </div>
                            <div style="font-size: 11.5px; line-height: 1.45; font-weight: 400; color: #475569;">
                                {{ $data['body'] ?? '' }}
                            </div>
                            <div style="font-size: 10.5px; margin-top: 3px; color: #475569;">
                                {{ $notification->created_at->locale('id')->diffForHumans() }}
                            </div>
                        </div>
                    </a>
                    @if ($isUnread)
                        <button type="button" wire:click="markRead('{{ $notification->id }}')" wire:loading.attr="disabled" class="btn btn-sm text-primary p-1 ml-1" style="font-size: 11px; flex-shrink: 0;" title="Tandai sudah dibaca" wire:target="markRead('{{ $notification->id }}')">
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
