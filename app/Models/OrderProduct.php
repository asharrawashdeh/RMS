<?php

namespace App\Models;

use App\Events\OrderCreated;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProduct extends Pivot
{

    public static function boot()
    {
        parent::boot();
        static::created(function ($pivot) {
            OrderCreated::dispatch($pivot);
        });
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function updateStock()
    {
        $grossAmountPerIngredient = $this->product->grossAmountPerIngredient($this->quantity);

        $ingredientIds = $grossAmountPerIngredient->keys()->unique()->toArray();
        Stock::whereIn('ingredient_id', $ingredientIds)->each(function ($stock) use ($grossAmountPerIngredient) {
            $leftAmount = $stock->left_amount - data_get($grossAmountPerIngredient, $stock->ingredient_id) / 1000;
            $stock->update([
                'left_amount' => $leftAmount < 0 ? 0 : $leftAmount,
                'must_notify' => $stock->mustNotify($leftAmount)
            ]);
        });
    }
}
