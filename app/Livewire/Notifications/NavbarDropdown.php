<?php

namespace App\Livewire\Notifications;

use App\Services\DashboardService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class NavbarDropdown extends Component
{
    public function render(DashboardService $dashboardService, NotificationService $notificationService)
    {
        $user = auth()->user();

        if (! $user) {
            return view('livewire.notifications.navbar-dropdown', [
                'actionItems' => [],
                'history' => collect(),
                'unreadCount' => 0,
            ]);
        }

        // Satu cache untuk seluruh payload navbar (tindakan + riwayat + badge)
        // agar navbar hanya menimbulkan 1 query cache per render halaman.
        // Simpan sebagai array polos — model Eloquent tidak aman diserialisasi
        // ke cache database.
        $payload = Cache::remember(
            'navbar_notifications_'.$user->id,
            60,
            function () use ($dashboardService, $notificationService, $user) {
                $hasAccess = $user->hasRole('admin') || $user->hasRole('direktur') || $user->hasRole('karyawan');

                return [
                    'actionItems' => $hasAccess ? $dashboardService->getActionItems() : [],
                    'history' => $notificationService->latest($user)
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'data' => $notification->data,
                            'is_unread' => $notification->read_at === null,
                            'time' => $notification->created_at->locale('id')->diffForHumans(),
                        ])
                        ->all(),
                    'unreadCount' => $notificationService->unreadCount($user),
                ];
            }
        );

        return view('livewire.notifications.navbar-dropdown', [
            'actionItems' => $payload['actionItems'],
            'history' => $payload['history'],
            'unreadCount' => $payload['unreadCount'],
        ]);
    }

    public function open(string $notificationId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();
        if (! $notification) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $this->redirect($notification->data['url'] ?? route('dashboard'), navigate: true);
    }

    public function markRead(string $notificationId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        app(NotificationService::class)->markRead($user, $notificationId);
    }

    public function markAllRead(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        app(NotificationService::class)->markAllRead($user);
    }
}
