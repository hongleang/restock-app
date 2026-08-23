<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'contact_name',
        'email',
        'phone',
        'lead_time_days',
        'shop_id',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn (array $value, array $attributes) => ucfirst($attributes['first_name']).' '.ucfirst($attributes['last_name']),
        );
    }
}
