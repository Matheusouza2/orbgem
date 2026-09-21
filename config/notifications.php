<?php

use App\Notifications\Channels\InAppChannel;

return [
    'channels' => [InAppChannel::class],
    'firebase_push_enabled' => (bool) env('FIREBASE_PUSH_NOTIFICATIONS_ENABLED', false),
];
