<?php

namespace App\Listeners;

use App\Events\WalletActivityOccurred;
use App\Models\WalletMember;
use App\Notifications\WalletActivityNotification;

class SendWalletActivityNotification
{
    public function handle(WalletActivityOccurred $event): void
    {
        WalletMember::query()->with('user')->where('wallet_id', $event->walletId)->get()->each(function (WalletMember $member) use ($event): void {
            $member->user->notify(new WalletActivityNotification($event->walletId, $event->type, $event->title, $event->body, $event->data));
        });
    }
}
