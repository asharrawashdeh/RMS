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
        // go through this product ingredients and set must_notify and left_amount temporary
        $updatedStocks = Stock::whereIn('ingredient_id', $this->product->ingredients->pluck('id'))
            ->get()->map(function ($stock) {
                $grossAmount = $this->product->ingredients()->where('ingredients.id', $stock->ingredient_id)->get()->first()->pivot->amount * $this->quantity;
                $leftAmount = $stock->left_amount - $grossAmount / 1000;

                if ($leftAmount >= 0) {
                    $stock->must_notify = $stock->mustNotify($leftAmount);
                    $stock->left_amount = $leftAmount;
                    return $stock;
                }
            })->filter();
        // if at least one ingredient was not sufficient from the above calculation, detach the whole product from the order.
        if ($updatedStocks->count() != $this->product->ingredients->count()) {
            $this->delete();
            return;
        }
        $updatedStocks->each(fn ($stock) => $stock->save());
    }
}
