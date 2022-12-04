<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'quantity' => $this->pivot->quantity,
            'name' => $this->name,
            'ingredients' => $this->whenLoaded('ingredients', fn () => IngredientsResource::collection($this->ingredients)),
        ];
    }
}
