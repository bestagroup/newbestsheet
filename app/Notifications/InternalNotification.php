<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InternalNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => (string) ($this->payload['title'] ?? 'اعلان جدید'),
            'message' => (string) ($this->payload['message'] ?? ''),
            'url' => $this->payload['url'] ?? null,
            'icon' => (string) ($this->payload['icon'] ?? 'mdi-bell-outline'),
            'category' => (string) ($this->payload['category'] ?? 'system'),
            'actor_id' => $this->payload['actor_id'] ?? null,
            'severity' => (string) ($this->payload['severity'] ?? 'info'),
            'related_type' => $this->payload['related_type'] ?? null,
            'related_id' => $this->payload['related_id'] ?? null,
        ];
    }
}
