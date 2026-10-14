<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;

class TransactionService
{
    /**
     * Records the completed payment for an order in the transaction ledger.
     * Idempotent: re-confirming a paid order does not add a second ledger row.
     */
    public function recordPayment(Order $order): Transaction
    {
        return Transaction::firstOrCreate(
            [
                'order_id' => $order->id,
                'type' => Transaction::TYPE_PAYMENT,
            ],
            [
                'user_id' => $order->user_id,
                'method' => $order->payment_method ?: 'gcash',
                'status' => 'completed',
                'amount' => (float) $order->total,
                'reference' => $order->payment_reference,
                'notes' => 'Payment verified and confirmed.',
                'recorded_at' => $order->paid_at ?? now(),
            ],
        );
    }
}
