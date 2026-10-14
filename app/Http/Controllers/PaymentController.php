<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public const STATUSES = ['awaiting_verification', 'paid', 'unpaid', 'rejected'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
        ]);

        $status = $validated['status'] ?? 'awaiting_verification';

        $orders = Order::with(['user', 'items'])
            ->where('payment_status', $status)
            ->orderByDesc('payment_submitted_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends(['status' => $status]);

        $stats = [
            'awaiting_verification' => Order::where('payment_status', 'awaiting_verification')->count(),
            'paid' => Order::where('payment_status', 'paid')->count(),
            'unpaid' => Order::where('payment_status', 'unpaid')->count(),
            'rejected' => Order::where('payment_status', 'rejected')->count(),
        ];

        $pendingValue = Order::where('payment_status', 'awaiting_verification')->sum('total');

        return view('admin.payments', compact('orders', 'stats', 'status', 'pendingValue'));
    }

    public function verify(Request $request, Order $order, PaymentService $paymentService)
    {
        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $confirmed = $paymentService->confirm($order, Auth::user(), $validated['reference'] ?? null);

        if (! $confirmed) {
            return redirect()->back()->with('success', 'This order was already settled or cancelled.');
        }

        return redirect()->back()->with('success', 'Payment verified. Invoice generated and ledger updated.');
    }

    public function reject(Request $request, Order $order, PaymentService $paymentService)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $paymentService->reject($order, Auth::user(), $validated['reason'] ?? null);

        return redirect()->back()->with('success', 'Payment rejected. The customer can resubmit.');
    }
}
