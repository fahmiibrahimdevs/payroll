<?php

namespace App\Livewire\Notifications;

use App\Services\NotificationService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Riwayat Notifikasi')]
class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $filter = 'all';

    public function updatedFilter(): void
    {
        $this->resetPage();
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
        $this->resetPage();
    }

    public function render(NotificationService $notificationService)
    {
        $user = auth()->user();

        $history = $user
            ? $notificationService->history($user, $this->filter === 'unread')
            : null;
        $unreadCount = $user ? $notificationService->unreadCount($user) : 0;

        return view('livewire.notifications.index', [
            'history' => $history,
            'unreadCount' => $unreadCount,
        ]);
    }
}
