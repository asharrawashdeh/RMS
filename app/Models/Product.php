<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class)->withPivot('amount')->withTimestamps();
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class)->using(OrderProduct::class)->withPivot('quantity');
    }
}
