<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_title',
        'product_description',
        'product_image',
        'product_images',
        'product_category',
        'wholesale_price',
    ];

    protected $casts = [
        'product_quantity' => 'integer',
        'reorder_level' => 'integer',
        'expiry_date' => 'date',
        'product_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'product_images' => 'array',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlistedBy()
    {
        return $this->hasMany(WishlistedItem::class);
    }

    public function inventoryHistories()
    {
        return $this->hasMany(InventoryHistory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('weight');
    }

    public function pairings()
    {
        return $this->belongsToMany(Product::class, 'product_pairings', 'product_id', 'paired_product_id')
            ->withTimestamps();
    }

    /**
     * Short badge for a product card. Flags bulk pricing when the product
     * offers one; views omit the badge when this is empty.
     */
    public function typeLabel(): string
    {
        return $this->hasWholesalePrice() ? 'Bulk' : '';
    }

    public function hasVariants(): bool
    {
        return $this->variants()->count() > 0;
    }

    /**
     * All image filenames for the product, with the main image first.
     *
     * @return array<int, string>
     */
    public function galleryImages(): array
    {
        $images = [];

        if ($this->product_image) {
            $images[] = $this->product_image;
        }

        foreach ((array) $this->product_images as $image) {
            if (is_string($image) && $image !== '' && ! in_array($image, $images, true)) {
                $images[] = $image;
            }
        }

        return $images;
    }

    /**
     * Minimum quantity that earns the bulk/resale price on a single line.
     */
    public function bulkPriceThreshold(): int
    {
        return max(1, (int) config('shop.bulk_pricing.threshold', 10));
    }

    /**
     * The unit price for a given quantity. Once a line reaches the bulk
     * threshold and a resale price is set, every unit on that line is charged
     * that price; otherwise the regular retail price applies.
     */
    public function priceForQuantity(int $quantity): float
    {
        if ($quantity >= $this->bulkPriceThreshold() && $this->hasWholesalePrice()) {
            return (float) $this->wholesale_price;
        }

        return (float) $this->product_price;
    }

    public function hasWholesalePrice(): bool
    {
        return $this->wholesale_price !== null && (float) $this->wholesale_price > 0;
    }

    public function averageRating(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function reviewCount(): int
    {
        return $this->reviews()->count();
    }

    public function inStock(): bool
    {
        return $this->product_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (! $this->reorder_level) {
            return false;
        }

        return $this->product_quantity <= $this->reorder_level;
    }

    public function isExpiringSoon(int $days = 3): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($days));
    }

    public function recordInventoryChange(string $type, int $change, ?string $notes = null, ?string $refType = null, ?int $refId = null): InventoryHistory
    {
        // Lock the product row so concurrent adjustments serialize instead of
        // racing on the current quantity (callers should wrap this in a
        // transaction so the lock is held until commit).
        $locked = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
        $before = $locked->product_quantity;

        if ($type === 'sale') {
            $locked->decrement('product_quantity', abs($change));
        } elseif ($type === 'restock') {
            $locked->increment('product_quantity', abs($change));
        } elseif ($type === 'adjustment') {
            $locked->product_quantity = $change;
            $locked->save();
        }

        $locked->refresh();
        $this->product_quantity = $locked->product_quantity;

        return InventoryHistory::create([
            'product_id' => $locked->id,
            'type' => $type,
            'quantity_change' => $change,
            'quantity_before' => $before,
            'quantity_after' => $locked->product_quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'notes' => $notes,
        ]);
    }
}
