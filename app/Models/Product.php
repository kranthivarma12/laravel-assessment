<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name',
        'quantity_in_stock',
        'price_per_item',
    ];

    protected $casts = [
        'quantity_in_stock' => 'integer',
        'price_per_item' => 'decimal:2',
    ];

    protected $appends = [
        'total_value',
        'datetime_submitted',
    ];

    public function getTotalValueAttribute(): float
    {
        return $this->quantity_in_stock * (float) $this->price_per_item;
    }

    public function getDatetimeSubmittedAttribute(): string
    {
        return $this->created_at?->format('Y-m-d H:i:s') ?? '';
    }
}