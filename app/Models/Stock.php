<?php

namespace App\Models;

use App\Events\StockUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public const DANGER_PERCENTAGE = 0.5;

    protected static function booted()
    {
        parent::booted();
        static::updated(function (self $model) {
            StockUpdated::dispatch($model);
        });
    }
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
    public function mustNotify($leftAmount): bool
    {
        return $this->left_amount / $this->start_amount > self::DANGER_PERCENTAGE && $leftAmount / $this->start_amount <= self::DANGER_PERCENTAGE;
    }
}
