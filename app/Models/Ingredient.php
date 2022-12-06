<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;
    public $guarded = [];
    public function product(){
        return $this->belongsToMany(Product::class)->withPivot('amount')->withTimestamps();
    }
}
