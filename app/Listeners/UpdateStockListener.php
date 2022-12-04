<?php

namespace App\Listeners;

use App\Events\OrderCreated;

class UpdateStockListener
{
    public function handle(OrderCreated $event)
    {
        $event->orderProduct->updateStock();
    }
}
