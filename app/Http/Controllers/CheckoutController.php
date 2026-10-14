<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AddressNormalizer;
use App\Services\InventoryService;
use App\Services\OrderPricingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function checkout(OrderPricingService $pricing)
    {
        $cart = Cart::where('user_id', Auth::id())->with(['product', 'variant'])->get();
        $coupon = $this->couponFromSession();
        $totals = $pricing->totalsFromItems($cart, $coupon);

        // A fresh idempotency key per checkout page load. It round-trips via a
        // hidden field so a duplicate submission of the same form (double-click,
        // browser resubmit, or retry) reuses the original order instead of
        // creating a second one.
        $idempotencyKey = (string) Str::uuid();
        session(['checkout_idempotency_key' => $idempotencyKey]);

        return view('checkout', [
            'cart' => $cart,
            'total' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'grandTotal' => $totals['grandTotal'],
            'coupon' => $coupon,
            'couponCode' => $coupon?->code,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }

    public function applyCoupon(Request $request, OrderPricingService $pricing)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ], [
            'code.required' => 'Please enter a coupon code.',
        ]);

        $code = trim($validated['code']);
        $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();

        if (! $coupon) {
            return response()->json(['success' => false, 'message' => 'That coupon code does not exist.'], 422);
        }

        $cart = Cart::where('user_id', Auth::id())->with(['product', 'variant'])->get();
        $subtotal = $pricing->subtotal($cart);

        if (! $coupon->appliesTo($subtotal)) {
            return response()->json(['success' => false, 'message' => 'That coupon is not valid for this order.'], 422);
        }

        session(['coupon_code' => $coupon->code]);

        $totals = $pricing->totals($subtotal, $coupon);

        return response()->json([
            'success' => true,
            'couponCode' => $coupon->code,
            'discount' => $totals['discount'],
            'freeShipping' => $totals['freeShipping'],
            'grandTotal' => $totals['grandTotal'],
            'message' => 'Coupon applied.',
        ]);
    }

    public function removeCoupon()
    {
        session()->forget('coupon_code');

        return response()->json(['success' => true, 'discount' => 0]);
    }

    public function placeOrder(Request $request, OrderPricingService $pricing, InventoryService $inventory)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s\-\']+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z\s\-\']+$/'],
            'address' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255', 'regex:/[A-Za-zÀ-ÿ]/'],
            'province' => ['nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'postal' => ['required', 'string', 'regex:/^\d{4}$/'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'first_name.regex' => 'First name may only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.regex' => 'Last name may only contain letters, spaces, hyphens, and apostrophes.',
            'city.regex' => 'Please enter a valid city name, not a postal code.',
            'postal.regex' => 'Postal code must be exactly 4 digits.',
            'phone.regex' => 'Phone number must start with 09 and be exactly 11 digits (e.g. 09171234567).',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $cart = Cart::where('user_id', Auth::id())->with('product')->get();

        if ($cart->isEmpty()) {
            return redirect()->route('shop')->with('cartMessage', 'Your cart is empty.');
        }

        foreach ($cart as $item) {
            if (! $item->product) {
                throw ValidationException::withMessages([
                    'cart' => 'One or more items in your cart are no longer available.',
                ]);
            }

            $availableStock = $item->variant ? $item->variant->quantity : $item->product->product_quantity;
            if ($item->quantity > $availableStock) {
                throw ValidationException::withMessages([
                    'cart' => $item->displayName().' no longer has enough stock for your requested quantity.',
                ]);
            }
        }

        $subtotal = $pricing->subtotal($cart);
        $coupon = $this->couponFromSession();

        if ($coupon && ! $coupon->appliesTo($subtotal)) {
            session()->forget('coupon_code');

            throw ValidationException::withMessages([
                'coupon' => 'The applied coupon is no longer valid for this order.',
            ]);
        }

        // Browser address autofill or manual typing often repeats the street or
        // appends the city/region; collapse those before persisting so orders,
        // saved addresses, and delivery dispatch get one clean street line.
        $validated['address'] = app(AddressNormalizer::class)->line(
            $validated['address'],
            $validated['city'] ?? '',
            $validated['region'] ?? '',
        );

        $address = trim($validated['address'].' '.($validated['address2'] ?? ''));
        $fullAddress = trim($address.', '.$validated['barangay'].', '.$validated['city'].', '.$validated['region'].' '.$validated['postal']);

        $totals = $pricing->totals($subtotal, $coupon);

        // Reuse an existing order when the same checkout form is submitted more
        // than once (double-click, browser resubmit, or a retried request).
        $idempotencyKey = trim((string) ($request->input('idempotency_key') ?? session('checkout_idempotency_key', '')));

        $existingOrder = $idempotencyKey !== ''
            ? Order::where('user_id', Auth::id())->where('idempotency_key', $idempotencyKey)->first()
            : null;

        if ($existingOrder) {
            if ($existingOrder->payment_status === 'paid') {
                return redirect()->route('orders.show', $existingOrder->id)
                    ->with('orderMessage', 'You have already paid for this order.');
            }

            if ($existingOrder->status !== 'cancelled') {
                return redirect()->route('orders.pay', $existingOrder->id);
            }

            return redirect()->route('checkout')->withErrors([
                'checkout' => 'Your previous order for this cart was cancelled. Please try again.',
            ]);
        }

        try {
            $order = DB::transaction(function () use ($validated, $fullAddress, $totals, $cart, $inventory, $coupon, $idempotencyKey) {
                $order = new Order();
                $order->user_id = Auth::id();
                $order->idempotency_key = $idempotencyKey !== '' ? $idempotencyKey : null;
                $order->name = $validated['first_name'].' '.$validated['last_name'];
                $order->address = $fullAddress;
                $order->barangay = $validated['barangay'];
                $order->region = $validated['region'];
                $order->city = $validated['city'];
                $order->phone = $validated['phone'];
                $order->total = $totals['grandTotal'];
                $order->status = 'pending';
                $order->payment_status = 'unpaid';
                $order->payment_method = 'gcash';
                $order->shipping_fee = $totals['shippingFee'];
                $order->discount = $totals['discount'];
                $order->coupon_code = $coupon?->code;
                $order->notes = $validated['notes'];
                $order->save();

                foreach ($cart as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'price' => $item->unitPrice(),
                    ]);
                }

                $inventory->reserve($order, $cart);

                if ($coupon) {
                    $lockedCoupon = Coupon::whereKey($coupon->id)->lockForUpdate()->first();

                    if ($lockedCoupon && $lockedCoupon->usage_limit !== null && $lockedCoupon->used_count >= $lockedCoupon->usage_limit) {
                        throw new \RuntimeException('Coupon usage limit reached.');
                    }

                    Coupon::whereKey($coupon->id)->increment('used_count');

                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => Auth::id(),
                        'order_id' => $order->id,
                        'discount_amount' => $totals['discount'],
                    ]);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent duplicate submission of the same form won the race;
            // reuse its order instead of creating another one.
            $existing = Order::where('user_id', Auth::id())
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing && $existing->status !== 'cancelled') {
                return redirect()->route('orders.pay', $existing->id);
            }

            return redirect()->route('checkout')->withErrors([
                'checkout' => 'This order was already submitted. Please try again.',
            ]);
        }

        session()->forget('coupon_code');

        return redirect()->route('orders.pay', $order->id)
            ->with('orderMessage', 'Order placed! Complete your GCash payment to confirm it.');
    }

    protected function releaseCouponForOrder(Order $order): void
    {
        $usage = CouponUsage::where('order_id', $order->id)->first();

        if (! $usage) {
            return;
        }

        Coupon::whereKey($usage->coupon_id)->decrement('used_count');
        $usage->delete();
    }

    protected function couponFromSession(): ?Coupon
    {
        $code = session('coupon_code');

        if (! $code) {
            return null;
        }

        return Coupon::where('code', $code)->first();
    }
}
