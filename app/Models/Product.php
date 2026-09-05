<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'category',
        'cost_price',
        'sell_price',
        'current_stock',
        'reorder_point',
        'reorder_qty',
        'shop_id',
        'supplier_id',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
