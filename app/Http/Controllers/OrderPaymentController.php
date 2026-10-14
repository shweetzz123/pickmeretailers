<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderPaymentController extends Controller
{
    /**
     * The GCash payment page for an unpaid/rejected order: shows the shop's
     * GCash details and QR code and lets the customer submit their reference.
     */
    public function show(Order $order)
    {
        $this->authorizeOrder($order);

        $order->load('items.product');

        return view('orders.pay', [
            'order' => $order,
            'gcash' => $this->gcashDetails(),
        ]);
    }

    public function submit(Request $request, Order $order, PaymentService $paymentService)
    {
        $this->authorizeOrder($order);

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order->id)
                ->with('orderMessage', 'This order is already paid.');
        }

        if ($order->status === 'cancelled') {
            return redirect()->route('orders.show', $order->id)
                ->withErrors(['payment' => 'This order has been cancelled and can no longer be paid.']);
        }

        $validated = $request->validate([
            'reference' => ['required', 'digits:13'],
            'proof' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'confirm_address' => ['accepted'],
        ], [
            'reference.required' => 'Please enter your GCash reference number.',
            'reference.digits' => 'The GCash reference number must be exactly 13 digits.',
            'proof.image' => 'The proof of payment must be an image.',
            'proof.mimes' => 'The screenshot must be a JPG, PNG, or WebP image.',
            'proof.max' => 'The screenshot may not be larger than 2 MB.',
            'confirm_address.accepted' => 'Please confirm your delivery address before submitting payment.',
        ]);

        $paymentService->submit($order, trim($validated['reference']), $request->file('proof'));

        return redirect()->route('orders.show', $order->id)
            ->with('orderMessage', 'Payment submitted! We will verify it shortly.');
    }

    /**
     * Printable invoice for a paid order.
     */
    public function invoice(Order $order)
    {
        $this->authorizeOrder($order);

        $invoice = $order->invoice;

        abort_if($invoice === null, 404, 'This order does not have an invoice yet.');

        $order->load('items.product');

        return view('orders.invoice', [
            'order' => $order,
            'invoice' => $invoice,
            'gcash' => $this->gcashDetails(),
        ]);
    }

    protected function authorizeOrder(Order $order): void
    {
        abort_unless(
            $order->user_id === Auth::id() || (Auth::user()?->isAdmin() ?? false),
            403,
        );
    }

    /**
     * @return array{number: ?string, name: ?string, qr: ?string, instructions: string}
     */
    protected function gcashDetails(): array
    {
        $qr = Setting::get('gcash_qr_path');

        return [
            'number' => Setting::get('gcash_number'),
            'name' => Setting::get('gcash_name'),
            'qr' => $qr ? asset('payment-qr/'.$qr) : null,
            'instructions' => Setting::get(
                'gcash_instructions',
                'Open your GCash app, scan the QR code or send to the number above, then enter the reference number below.'
            ),
        ];
    }
}
