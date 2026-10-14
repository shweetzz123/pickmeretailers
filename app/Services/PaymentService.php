<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Orchestrates the manual GCash payment flow:
 *
 *   unpaid --(customer submits reference)--> awaiting_verification
 *          --(admin confirms)--> paid   (invoice + ledger entry, cart cleared)
 *          --(admin rejects)--> rejected (customer can resubmit)
 */
class PaymentService
{
    public function __construct(
        protected InvoiceService $invoices,
        protected TransactionService $transactions,
    ) {}

    /**
     * Records the customer's GCash reference (and optional screenshot) and
     * moves the order into the admin verification queue.
     */
    public function submit(Order $order, string $reference, ?UploadedFile $proof = null): Order
    {
        return DB::transaction(function () use ($order, $reference, $proof) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_status === 'paid' || $locked->status === 'cancelled') {
                return $locked;
            }

            $locked->payment_reference = $reference;
            $locked->payment_submitted_at = now();
            $locked->payment_status = 'awaiting_verification';
            $locked->payment_notes = null;

            if ($proof) {
                $locked->payment_proof_path = $this->storeProof($proof);
            }

            $locked->save();

            return $locked;
        });
    }

    /**
     * Marks an order paid, issues its invoice, records the ledger entry and
     * queues the confirmation email. Returns the order when newly confirmed,
     * or null when the order was already paid or is cancelled.
     */
    public function confirm(Order $order, ?User $verifier = null, ?string $reference = null): ?Order
    {
        $confirmed = null;

        DB::transaction(function () use ($order, $verifier, $reference, &$confirmed) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_status === 'paid' || $locked->status === 'cancelled') {
                return;
            }

            if ($reference !== null && trim($reference) !== '') {
                $locked->payment_reference = $reference;
            }

            $locked->payment_status = 'paid';
            $locked->paid_at = now();
            $locked->payment_verified_by = $verifier?->id;
            $locked->payment_notes = null;
            $locked->save();

            $this->invoices->generateFor($locked);
            $this->transactions->recordPayment($locked);

            Cart::where('user_id', $locked->user_id)->delete();

            $confirmed = $locked;
        });

        if ($confirmed) {
            $this->sendConfirmation($confirmed);
        }

        return $confirmed;
    }

    /**
     * Rejects a submitted payment so the customer can correct it and resubmit.
     */
    public function reject(Order $order, User $verifier, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $verifier, $reason) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_status === 'paid' || $locked->status === 'cancelled') {
                return $locked;
            }

            $locked->payment_status = 'rejected';
            $locked->payment_verified_by = $verifier->id;
            $locked->payment_notes = $reason;
            $locked->save();

            return $locked;
        });
    }

    protected function storeProof(UploadedFile $proof): string
    {
        $name = uniqid('proof_', true).'.'.($proof->guessExtension() ?: 'jpg');
        $proof->move(public_path('payment-proofs'), $name);

        return $name;
    }

    protected function sendConfirmation(Order $order): void
    {
        $order->loadMissing(['items', 'user']);

        if (! $order->user) {
            return;
        }

        try {
            Mail::to($order->user->email)->queue(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            Log::error('Failed to queue the order confirmation email.', [
                'order_id' => $order->id,
                'recipient' => $order->user->email,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
