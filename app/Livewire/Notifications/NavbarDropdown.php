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

        $actionItems = [];
        if ($user && ($user->hasRole('admin') || $user->hasRole('direktur') || $user->hasRole('karyawan'))) {
            $actionItems = Cache::remember('navbar_notifications_'.$user->id, 60, fn () => $dashboardService->getActionItems());
        }

        return view('livewire.notifications.navbar-dropdown', [
            'actionItems' => $actionItems,
            'history' => $user ? $notificationService->latest($user) : collect(),
            'unreadCount' => $user ? $notificationService->unreadCount($user) : 0,
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
