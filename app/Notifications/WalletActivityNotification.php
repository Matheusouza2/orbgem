<?php

namespace App\Notifications;

use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private int $walletId, private string $type, private string $title, private ?string $body, private array $data = []) {}

    public function via(object $notifiable): array
    {
        return config('notifications.channels', [InAppChannel::class]);
    }

    /** @return array<string, mixed> */
    public function toInApp(object $notifiable): array
    {
        return ['wallet_id' => $this->walletId, 'type' => $this->type, 'title' => $this->title, 'body' => $this->body, 'data' => $this->data];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->line($this->body ?? $this->title);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toInApp($notifiable));
    }
}
