<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\StoreLocation;
use Illuminate\Support\Collection;

class OrderPricingService
{
    /**
     * The fixed pickup location used as the origin for every delivery.
     *
     * Admin-managed store location wins; config/.env warehouse is the fallback.
     */
    public function warehouse(): ?array
    {
        $location = StoreLocation::pickup()->active()->first();

        if ($location) {
            return [
                'name' => $location->name,
                'phone' => $location->phone,
                'address' => $location->fullAddress(),
                'lat' => $location->lat,
                'lng' => $location->lng,
            ];
        }

        $warehouse = config('shop.store.warehouse', []);

        if (isset($warehouse['lat'], $warehouse['lng'], $warehouse['address'])) {
            return $warehouse;
        }

        return config('shop.store.branches')[0] ?? null;
    }

    public function subtotal(Collection $items): float
    {
        return round($items->sum(function ($item) {
            if (method_exists($item, 'unitPrice')) {
                $price = $item->unitPrice();
            } else {
                $price = (float) data_get($item, 'product.product_price', 0);
            }
            $quantity = (int) data_get($item, 'quantity', 1);

            return $price * $quantity;
        }), 2);
    }

    public function totalsFromItems(Collection $items, ?Coupon $coupon = null): array
    {
        return $this->totalsFromSubtotal($this->subtotal($items), $coupon);
    }

    /**
     * Build order totals.
     *
     * Delivery is always free, so the shipping fee is always zero. A
     * free-shipping coupon is still accepted and simply reports that shipping
     * is free; it never changes the totals.
     */
    public function totals(float $subtotal, ?Coupon $coupon = null): array
    {
        $discount = 0.0;
        $freeShipping = false;

        if ($coupon && $coupon->appliesTo($subtotal)) {
            if ($coupon->isFreeShipping()) {
                $freeShipping = true;
            } else {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shippingFee' => 0.0,
            'freeShipping' => $freeShipping,
            'grandTotal' => round($subtotal - $discount, 2),
        ];
    }

    public function totalsFromSubtotal(float $subtotal, ?Coupon $coupon = null): array
    {
        return $this->totals($subtotal, $coupon);
    }
}
