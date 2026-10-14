@extends('admin.maindesign')

@section('page_title', 'Transaction Ledger')
@section('page_header', 'Transaction Ledger')
@section('page_subtitle', 'Every payment, refund and adjustment recorded against customer orders.')

@section('page_actions')
<a href="{{ route('admin.transactions.export', request()->query()) }}" class="btn btn-success btn-sm">
    <i class="fa fa-download mr-1"></i> Export CSV
</a>
@endsection

@section('transactions')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-list-ol"></i></div><strong>Transactions</strong>
                    </div>
                    <div class="number dashtext-1">{{ number_format($totals['count']) }}</div>
                </div>
                <small class="text-muted">Matching current filters</small>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-money"></i></div><strong>Payments</strong>
                    </div>
                    <div class="number dashtext-2">PHP {{ number_format((float) $totals['payments'], 2) }}</div>
                </div>
                <small class="text-muted">Completed payments</small>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-undo"></i></div><strong>Refunds</strong>
                    </div>
                    <div class="number dashtext-3">PHP {{ number_format((float) $totals['refunds'], 2) }}</div>
                </div>
                <small class="text-muted">Refunded amounts</small>
            </div>
        </div>
    </div>

    <div class="block">
        <form method="GET" action="{{ route('admin.transactions') }}" class="form-inline mb-3">
            <div class="form-group mr-2 mb-2">
                <label class="sr-only" for="tx-from">From</label>
                <input type="date" id="tx-from" name="from" value="{{ $validated['from'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="form-group mr-2 mb-2">
                <label class="sr-only" for="tx-to">To</label>
                <input type="date" id="tx-to" name="to" value="{{ $validated['to'] ?? '' }}" class="form-control form-control-sm">
            </div>
            <div class="form-group mr-2 mb-2">
                <label class="sr-only" for="tx-type">Type</label>
                <select id="tx-type" name="type" class="form-control form-control-sm">
                    <option value="">All types</option>
                    @foreach(['payment' => 'Payment', 'refund' => 'Refund', 'adjustment' => 'Adjustment'] as $key => $label)
                        <option value="{{ $key }}" @selected(($validated['type'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mr-2 mb-2">
                <label class="sr-only" for="tx-q">Search</label>
                <input type="text" id="tx-q" name="q" value="{{ $validated['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Reference or customer">
            </div>
            <button type="submit" class="btn btn-primary btn-sm mb-2 mr-2"><i class="fa fa-filter mr-1"></i> Filter</button>
            <a href="{{ route('admin.transactions') }}" class="btn btn-outline-secondary btn-sm mb-2">Reset</a>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->recorded_at?->format('M d, Y g:i A') ?? '—' }}</td>
                            <td>{{ $transaction->reference ?? '—' }}</td>
                            <td>{{ $transaction->order_id ? '#'.$transaction->order_id : '—' }}</td>
                            <td>{{ $transaction->user->name ?? ($transaction->order->name ?? '—') }}</td>
                            <td><span class="badge badge-light text-uppercase">{{ $transaction->type }}</span></td>
                            <td>{{ strtoupper($transaction->method ?? '—') }}</td>
                            <td>{{ ucfirst($transaction->status ?? '—') }}</td>
                            <td class="text-right">PHP {{ number_format((float) $transaction->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No transactions match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $transactions->links() }}
    </div>
</div>
@endsection
