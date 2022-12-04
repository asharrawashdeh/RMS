<?php

namespace App\Listeners;

use App\Events\StockUpdated;
use App\Models\User;
use App\Notifications\LowStockLevelNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyMerchantListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function handle(StockUpdated $event)
    {
        if (!$event->stock->must_notify) {
            return;
        }
        User::supplier()->get()->each(fn ($user) => $user->notify(new LowStockLevelNotification($event->stock)));
    }
}
