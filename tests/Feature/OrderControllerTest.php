<?php

namespace Tests\Feature;

use App\Mail\NotifyMerchantMail;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\LowStockLevelNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * @test
     */
    public function gets_orders()
    {
        // ARRANGE
        $product = Product::factory()->create(['name' => 'Burger']);
        $product->ingredients()->attach(Ingredient::whereName('Beef')->first(), ['amount' => 150]);
        $product->ingredients()->attach(Ingredient::whereName('Cheese')->first(), ['amount' => 30]);
        $order = Order::factory()->create();
        $order->products()->attach($product, ['quantity' => 4]);
        // ACT + // ASSERT
        $this->json('get', route('orders.index'))->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => $product->name])
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'products' => [
                        '*' => [
                            'quantity',
                            'name',
                            'ingredients' => [
                                '*' => [
                                    'amount',
                                    'name'
                                ]
                            ]
                        ]
                    ],
                ]
            ]);
    }
    /**
     * @test
     */
    public function creates_an_order()
    {
        // ARRANGE
        $product = Product::factory()->create();
        // ACT
        $this->json('post', route('orders.store'), [
            'products' => $orders = [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertOk();
        // ASSERT
        $this->assertDatabaseHas('order_product', data_get($orders, 0));
    }
    /**
     * @test
     */
    public function order_updates_stock()
    {
        // ARRANGE
        $ingredient1 = Ingredient::whereName('Beef')->first();
        $ingredient2 = Ingredient::whereName('Cheese')->first();
        $ingredient3 = Ingredient::whereName('Onion')->first();

        $product1 = Product::factory()->create(['name' => 'Burger']);
        $product1->ingredients()->attach($ingredient1, ['amount' => 150]);
        $product1->ingredients()->attach($ingredient2, ['amount' => 30]);
        $product1->ingredients()->attach($ingredient3, ['amount' => 20]);

        $product2 = Product::factory()->create(['name' => 'Pizza']);
        $product2->ingredients()->attach($ingredient1, ['amount' => 100]);
        $product2->ingredients()->attach($ingredient2, ['amount' => 10]);
        $product2->ingredients()->attach($ingredient3, ['amount' => 15]);

        $this->assertEquals(Stock::where('ingredient_id', $ingredient1->id)->get()->first()->left_amount, 20);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient2->id)->get()->first()->left_amount, 5);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient3->id)->get()->first()->left_amount, 1);

        // ACT
        $this->json('post', route('orders.store'), [
            'products' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 4,
                ],
            ],
        ])->assertOk();

        // ASSERT
        $this->assertEquals(Stock::where('ingredient_id', $ingredient1->id)->get()->first()->left_amount, 20 - .7);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient2->id)->get()->first()->left_amount, 5 - .1);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient3->id)->get()->first()->left_amount, 1 - .1);
    }
    /**
     * @test
     */
    public function notify_merchant_when_stock_level_is_low()
    {
        // ARRANGE
        Notification::fake();
        $ingredient1 = Ingredient::firstOrCreate(['name' => 'Beef']);
        $ingredient2 = Ingredient::firstOrCreate(['name' => 'Cheese']);
        $ingredient3 = Ingredient::firstOrCreate(['name' => 'Onion']);

        $product = Product::factory()->create(['name' => 'Burger']);
        $product->ingredients()->attach($ingredient1, ['amount' => 300]);
        $product->ingredients()->attach($ingredient2, ['amount' => 30]);
        $product->ingredients()->attach($ingredient3, ['amount' => 20]);

        Stock::updateOrCreate(['ingredient_id' => $ingredient1->id], [
            'start_amount' => 20,
            'left_amount' => 20
        ]);
        Stock::updateOrCreate(['ingredient_id' => $ingredient2->id], [
            'start_amount' => 3,
            'left_amount' => 3,
        ]);
        Stock::updateOrCreate(['ingredient_id' => $ingredient3->id], [
            'start_amount' => 1.4,
            'left_amount' => 1.4
        ]);

        // ACT
        $this->json('post', route('orders.store'), [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 35,
                ],
            ],
        ])->assertOk();

        // ASSERT
        $this->assertEquals(Stock::where('ingredient_id', $ingredient1->id)->get()->first()->left_amount, 20 - 0.3 * 35);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient2->id)->get()->first()->left_amount, 3 - 0.03 * 35);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient3->id)->get()->first()->left_amount, 1.4 - 0.02 * 35);
        //ONLY SENT FOR BEEF AND ONION
        Notification::assertSentToTimes(User::supplier()->get()->first(), LowStockLevelNotification::class, 2);
    }
    /**
     * @test
     */
    public function skips_notifying_merchant_if_already_notified()
    {
        // ARRANGE
        Notification::fake();
        $ingredient1 = Ingredient::firstOrCreate(['name' => 'Beef']);
        $ingredient2 = Ingredient::firstOrCreate(['name' => 'Cheese']);
        $ingredient3 = Ingredient::firstOrCreate(['name' => 'Onion']);

        $product = Product::factory()->create(['name' => 'Burger']);
        $product->ingredients()->attach($ingredient1, ['amount' => 300]);
        $product->ingredients()->attach($ingredient2, ['amount' => 30]);
        $product->ingredients()->attach($ingredient3, ['amount' => 20]);

        Stock::updateOrCreate(['ingredient_id' => $ingredient1->id], [
            'start_amount' => 20,
            'left_amount' => 20
        ]);
        Stock::updateOrCreate(['ingredient_id' => $ingredient2->id], [
            'start_amount' => 3,
            'left_amount' => 3,
        ]);
        Stock::updateOrCreate(['ingredient_id' => $ingredient3->id], [
            'start_amount' => 1.4,
            'left_amount' => 1.4
        ]);

        // ACT
        $this->json('post', route('orders.store'), [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 35,
                ],
            ],
        ])->assertOk();
        $this->json('post', route('orders.store'), [
            'products' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertOk();

        // ASSERT
        $this->assertEquals(Stock::where('ingredient_id', $ingredient1->id)->get()->first()->left_amount, 20 - 0.3 * 36);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient2->id)->get()->first()->left_amount, 3 - 0.03 * 36);
        $this->assertEquals(Stock::where('ingredient_id', $ingredient3->id)->get()->first()->left_amount, 1.4 - 0.02 * 36);
        //ONLY SENT FOR BEEF AND ONION
        Notification::assertSentToTimes(User::supplier()->get()->first(), LowStockLevelNotification::class, 2);
    }
}
