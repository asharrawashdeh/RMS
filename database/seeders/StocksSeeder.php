<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Stock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StocksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stocks = [[
            'ingredient_id' => Ingredient::updateOrCreate(['name' => 'Beef'])->id,
            'start_amount' => 20,
            'left_amount' => 20,
        ], [
            'ingredient_id' => Ingredient::updateOrCreate(['name' => 'Cheese'])->id,
            'start_amount' => 5,
            'left_amount' => 5,
        ], [
            'ingredient_id' => Ingredient::updateOrCreate(['name' => 'Onion'])->id,
            'start_amount' => 1,
            'left_amount' => 1,
        ]];
        foreach ($stocks as $stock) {
            Stock::updateOrCreate($stock);
        }
    }
}
