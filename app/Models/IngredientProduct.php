<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class IngredientProduct extends Pivot
{
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
