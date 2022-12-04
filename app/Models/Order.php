<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function products()
    {
        return $this->belongsToMany(Product::class)->using(OrderProduct::class)->withPivot(['quantity'])->withTimestamps();
    }
    public static function createFromArray($data)
    {
        $order = Order::create();
        $order->products()->attach($data);
    }
}
