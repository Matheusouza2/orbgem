<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebasePushChannel
{
    public function __construct(private Messaging $messaging) {}

    public function send(object $notifiable, object $notification): void
    {
        if (! method_exists($notification, 'toFirebase')) {
            return;
        }

        $tokens = $notifiable->pushTokens()->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $payload = $notification->toFirebase($notifiable);
        $message = CloudMessage::new()
            ->withNotification(Notification::create($payload['title'] ?? null, $payload['body'] ?? null))
            ->withData($this->stringifyData($payload['data'] ?? []));

        try {
            foreach (array_chunk($tokens, 500) as $chunk) {
                $report = $this->messaging->sendMulticast($message, $chunk);
                $invalidTokens = array_values(array_unique([...$report->invalidTokens(), ...$report->unknownTokens()]));

                if ($invalidTokens !== []) {
                    $notifiable->pushTokens()->whereIn('token', $invalidTokens)->delete();
                }
            }
        } catch (Throwable $exception) {
            Log::error('Firebase push notification failed.', [
                'user_id' => $notifiable->getKey(),
                'notification' => $notification::class,
                'exception' => $exception,
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    /** @return array<string, string> */
    private function stringifyData(array $data): array
    {
        return collect($data)->mapWithKeys(static fn (mixed $value, string|int $key): array => [(string) $key => is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR)])->all();
    }
}
