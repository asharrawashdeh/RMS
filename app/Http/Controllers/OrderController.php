<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrdersResource;
use App\Models\Order;

class OrderController extends Controller
{
    public function index()
    {
        return response(OrdersResource::collection(Order::with('products.ingredients')->paginate(15)));
    }
    public function store(CreateOrderRequest $request)
    {
        Order::createFromArray($request->products);
    }
}
