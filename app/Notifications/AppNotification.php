<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    public function __construct(
        public string $category,
        public string $title,
        public string $body,
        public string $url,
        public string $icon = 'far fa-bell',
        public string $level = 'info',
    ) {}

    /**
     * deliveryChannels — database only; email/channel expansion happens here.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{category: string, title: string, body: string, url: string, icon: string, level: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
            'level' => $this->level,
        ];
    }
}
