<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    public const PER_PAGE = 15;

    /**
     * Send a database notification and invalidate the recipient's navbar cache.
     *
     * @param  User|iterable<User>  $recipients
     */
    public function send(User|iterable $recipients, string $category, string $title, string $body, string $url, string $icon = 'far fa-bell', string $level = 'info'): void
    {
        $recipients = $recipients instanceof User ? [$recipients] : $recipients;
        $notification = new AppNotification($category, $title, $body, $url, $icon, $level);

        foreach ($recipients as $recipient) {
            if (! $recipient instanceof User) {
                continue;
            }

            $recipient->notify($notification);
            Cache::forget('navbar_notifications_'.$recipient->id);
        }
    }

    /**
     * Latest notifications of a user, unread first, for the navbar dropdown.
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function latest(User $user, int $limit = 8): Collection
    {
        return $user->notifications()
            ->limit($limit)
            ->get()
            ->sortBy(fn (DatabaseNotification $n) => (int) (bool) $n->read_at)
            ->values();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Paginated history for the full notification page.
     *
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function history(User $user, bool $unreadOnly, int $page = 1)
    {
        return $user->notifications()
            ->when($unreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->orderByRaw('read_at IS NOT NULL, created_at DESC')
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);
    }

    /**
     * Mark one notification as read — only when it belongs to the user.
     */
    public function markRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return false;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return true;
    }

    /**
     * Mark every notification of the user as read.
     */
    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->each->markAsRead();
    }
}
