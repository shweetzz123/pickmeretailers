<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function unitPrice(): float
    {
        if ($this->variant) {
            return (float) $this->variant->price;
        }

        return $this->product->priceForQuantity($this->quantity);
    }

    public function subtotal(): float
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function displayName(): string
    {
        $name = $this->product->product_title;
        if ($this->variant) {
            $name .= ' ('.$this->variant->weight.')';
        }

        return $name;
    }
}
