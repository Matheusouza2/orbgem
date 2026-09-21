<?php

namespace App\Notifications;

use App\Notifications\Channels\InAppChannel;
use App\Notifications\Channels\FirebasePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private int $walletId, private string $type, private string $title, private ?string $body, private array $data = [], private ?string $deduplicationKey = null) {}

    public function via(object $notifiable): array
    {
        $channels = config('notifications.channels', [InAppChannel::class]);

        if (config('notifications.firebase_push_enabled', false)) {
            $channels[] = FirebasePushChannel::class;
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function toInApp(object $notifiable): array
    {
        return ['wallet_id' => $this->walletId, 'type' => $this->type, 'title' => $this->title, 'body' => $this->body, 'data' => $this->data + ($this->deduplicationKey === null ? [] : ['deduplication_key' => $this->deduplicationKey])];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->line($this->body ?? $this->title);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toInApp($notifiable));
    }

    /** @return array{title: string, body: ?string, data: array<string, mixed>} */
    public function toFirebase(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'data' => ['wallet_id' => $this->walletId, 'type' => $this->type, ...$this->data, ...($this->deduplicationKey === null ? [] : ['deduplication_key' => $this->deduplicationKey])]];
    }
}
