<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;

class InvoiceService
{
    /**
     * Generates (once) the invoice for a paid order. Idempotent so it can be
     * called from both the payment confirmation flow and a manual re-issue.
     */
    public function generateFor(Order $order): Invoice
    {
        return Invoice::firstOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => $this->numberFor($order),
                'subtotal' => $order->subtotal(),
                'discount' => (float) $order->discount,
                'shipping_fee' => (float) $order->shipping_fee,
                'total' => (float) $order->total,
                'status' => 'issued',
                'issued_at' => now(),
            ],
        );
    }

    /**
     * Invoice numbers are sequential per day and unique per order, e.g.
     * INV-20261013-000042.
     */
    protected function numberFor(Order $order): string
    {
        $date = $order->created_at ?? now();

        return 'INV-'.$date->format('Ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
    }
}
