<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'in:payment,refund,adjustment'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $from = isset($validated['from']) ? \Carbon\Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? \Carbon\Carbon::parse($validated['to'])->endOfDay() : null;

        $query = $this->buildQuery($validated, $from, $to);

        $transactions = (clone $query)
            ->with(['order', 'user'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends(request()->query());

        $totals = [
            'count' => (clone $query)->count(),
            'payments' => (clone $query)->where('type', 'payment')->where('status', 'completed')->sum('amount'),
            'refunds' => (clone $query)->where('type', 'refund')->sum('amount'),
        ];

        return view('admin.transactions', compact('transactions', 'totals', 'validated'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', 'in:payment,refund,adjustment'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $from = isset($validated['from']) ? \Carbon\Carbon::parse($validated['from'])->startOfDay() : null;
        $to = isset($validated['to']) ? \Carbon\Carbon::parse($validated['to'])->endOfDay() : null;

        $transactions = $this->buildQuery($validated, $from, $to)
            ->with(['order', 'user'])
            ->orderByDesc('recorded_at')
            ->get();

        $filename = 'transactions_'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            $escape = static function (mixed $value): string {
                $value = (string) $value;

                if ($value !== '' && str_contains('=+-@'."\t".chr(13), $value[0])) {
                    return "'".$value;
                }

                return $value;
            };

            fputcsv($file, ['Date', 'Reference', 'Order ID', 'Customer', 'Type', 'Method', 'Status', 'Amount']);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $escape($transaction->recorded_at?->format('Y-m-d H:i:s')),
                    $escape($transaction->reference),
                    $escape($transaction->order_id),
                    $escape($transaction->user->name ?? ($transaction->order->name ?? '')),
                    $escape($transaction->type),
                    $escape($transaction->method),
                    $escape($transaction->status),
                    $escape(number_format((float) $transaction->amount, 2, '.', '')),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function buildQuery(array $validated, ?Carbon $from, ?Carbon $to)
    {
        $search = trim($validated['q'] ?? '');

        return Transaction::query()
            ->when($from, fn ($q) => $q->where('recorded_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('recorded_at', '<=', $to))
            ->when(! empty($validated['type']), fn ($q) => $q->where('type', $validated['type']))
            ->when($search !== '', function ($q) use ($search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $q->where(function ($sub) use ($escaped) {
                    $sub->where('reference', 'LIKE', '%'.$escaped.'%')
                        ->orWhere('notes', 'LIKE', '%'.$escaped.'%')
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'LIKE', '%'.$escaped.'%'));
                });
            });
    }
}
