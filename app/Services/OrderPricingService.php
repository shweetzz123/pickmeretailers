<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Setting;
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
     * Flat delivery fee configured by the admin in store settings.
     *
     * Stored as the `shipping_fee` setting; zero means free shipping.
     */
    public function shippingFee(): float
    {
        return round(max(0, (float) Setting::get('shipping_fee', 0)), 2);
    }

    /**
     * Build order totals.
     *
     * A flat shipping fee is added to every order. A free-shipping coupon
     * waives that fee; other coupon types discount the subtotal instead.
     */
    public function totals(float $subtotal, ?Coupon $coupon = null): array
    {
        $discount = 0.0;
        $freeShipping = false;
        $shippingFee = $this->shippingFee();

        if ($coupon && $coupon->appliesTo($subtotal)) {
            if ($coupon->isFreeShipping()) {
                $freeShipping = true;
                $shippingFee = 0.0;
            } else {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shippingFee' => round($shippingFee, 2),
            'freeShipping' => $freeShipping,
            'grandTotal' => round($subtotal - $discount + $shippingFee, 2),
        ];
    }

    public function totalsFromSubtotal(float $subtotal, ?Coupon $coupon = null): array
    {
        return $this->totals($subtotal, $coupon);
    }
}
