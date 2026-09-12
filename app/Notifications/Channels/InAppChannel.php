<?php

namespace App\Notifications\Channels;

use App\Models\InAppNotification;

class InAppChannel
{
    public function send(object $notifiable, object $notification): void
    {
        $data = $notification->toInApp($notifiable);
        InAppNotification::query()->create($data + ['user_id' => $notifiable->getKey()]);
    }
}
