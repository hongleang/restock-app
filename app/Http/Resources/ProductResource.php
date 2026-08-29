<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'category' => $this->category,
            'cost_price' => $this->cost_price,
            'sell_price' => $this->sell_price,
            'current_stock' => $this->current_stock,
            'reorder_point' => $this->reorder_point,
            'reorder_qty' => $this->reorder_qty,
            'shop' => $this->whenLoaded('shop', fn() => new ShopResource($this->shop)),
            'supplier' => $this->whenLoaded('supplier', fn() => new SupplierResource($this->supplier)),
        ];
    }
}
