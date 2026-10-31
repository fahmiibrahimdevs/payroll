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
     * Inline gradient style for a notification icon.
     *
     * Level-based: pass only $level ('success' | 'danger' | 'warning' | anything else = blue).
     * Title-based (navbar action items): pass $title to keep the exact legacy
     * str_contains matching — title keyword wins over the level fallback.
     */
    public static function iconGradient(?string $level, ?string $title = null): string
    {
        if ($title !== null) {
            return match (true) {
                str_contains($title, 'Karyawan Nonaktif'),
                str_contains($title, 'Data Payroll'),
                str_contains($title, 'Kontrak'),
                str_contains($title, 'Cicilan'),
                str_contains($title, 'Perlu Diperiksa') => 'background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 5px 12px rgba(109, 40, 217, 0.28);',
                str_contains($title, 'Kasbon'),
                str_contains($title, 'Pembayaran Kasbon') => 'background: linear-gradient(135deg, #f59e0b 0%, #ea8c00 100%); box-shadow: 0 5px 12px rgba(234, 140, 0, 0.28);',
                $level === 'success' => 'background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); box-shadow: 0 5px 12px rgba(22, 163, 74, 0.28);',
                str_contains($title, 'Lembur') => 'background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); box-shadow: 0 5px 12px rgba(13, 148, 136, 0.28);',
                str_contains($title, 'Penggajian'),
                str_contains($title, 'Periode'),
                str_contains($title, 'Slip') => 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 5px 12px rgba(37, 99, 235, 0.28);',
                default => 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 5px 12px rgba(37, 99, 235, 0.28);',
            };
        }

        return match ($level ?? 'info') {
            'success' => 'background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); box-shadow: 0 5px 12px rgba(22, 163, 74, 0.28);',
            'danger' => 'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 5px 12px rgba(220, 38, 38, 0.28);',
            'warning' => 'background: linear-gradient(135deg, #f59e0b 0%, #ea8c00 100%); box-shadow: 0 5px 12px rgba(234, 140, 0, 0.28);',
            default => 'background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); box-shadow: 0 5px 12px rgba(37, 99, 235, 0.28);',
        };
    }

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

        Cache::forget('navbar_notifications_'.$user->id);

        return true;
    }

    /**
     * Mark every notification of the user as read.
     */
    public function markAllRead(User $user): void
    {
        $user->unreadNotifications->each->markAsRead();

        Cache::forget('navbar_notifications_'.$user->id);
    }
}
