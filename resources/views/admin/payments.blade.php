@extends('admin.maindesign')

@section('page_title', 'Payment Verification')
@section('page_header', 'GCash Payment Verification')
@section('page_subtitle', 'Review customer-submitted GCash references and screenshots before marking orders paid.')

@section('payments')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-hourglass-half"></i></div><strong>Awaiting</strong>
                    </div>
                    <div class="number dashtext-3">{{ number_format($stats['awaiting_verification']) }}</div>
                </div>
                <small class="text-muted">PHP {{ number_format((float) $pendingValue, 2) }} pending</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-check-circle"></i></div><strong>Paid</strong>
                    </div>
                    <div class="number dashtext-2">{{ number_format($stats['paid']) }}</div>
                </div>
                <small class="text-muted">Verified orders</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-clock-o"></i></div><strong>Unpaid</strong>
                    </div>
                    <div class="number dashtext-1">{{ number_format($stats['unpaid']) }}</div>
                </div>
                <small class="text-muted">Awaiting customer payment</small>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="statistic-block block">
                <div class="progress-details d-flex align-items-end justify-content-between">
                    <div class="title">
                        <div class="icon"><i class="fa fa-times-circle"></i></div><strong>Rejected</strong>
                    </div>
                    <div class="number dashtext-4">{{ number_format($stats['rejected']) }}</div>
                </div>
                <small class="text-muted">Need resubmission</small>
            </div>
        </div>
    </div>

    <div class="block">
        <div class="title d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
                <strong>Payments</strong>
                <p class="text-muted mb-0">Filter by payment status</p>
            </div>
            <div class="btn-group">
                @foreach(['awaiting_verification' => 'Awaiting', 'paid' => 'Paid', 'unpaid' => 'Unpaid', 'rejected' => 'Rejected'] as $key => $label)
                    <a href="{{ route('admin.payments', ['status' => $key]) }}"
                       class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Reference</th>
                        <th>Submitted</th>
                        <th>Proof</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.vieworders') }}">#{{ $order->id }}</a></td>
                            <td>
                                {{ $order->user->name ?? $order->name }}<br>
                                <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                            </td>
                            <td>PHP {{ number_format((float) $order->total, 2) }}</td>
                            <td>{{ $order->payment_reference ?? '—' }}</td>
                            <td>{{ $order->payment_submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                            <td>
                                @if($order->payment_proof_path)
                                    <a href="{{ asset('payment-proofs/'.$order->payment_proof_path) }}" target="_blank" rel="noopener">View</a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($order->payment_status === 'awaiting_verification')
                                    <a href="{{ route('admin.vieworders') }}" class="btn btn-sm btn-outline-secondary mb-1">Open order</a>
                                @endif
                            </td>
                        </tr>

                        @if($order->payment_status === 'awaiting_verification')
                            <tr>
                                <td colspan="7" class="bg-light">
                                    <div class="d-flex flex-wrap align-items-end">
                                        <form method="POST" action="{{ route('admin.payments.verify', $order) }}" class="form-inline mr-3 mb-2" data-submit-once>
                                            @csrf
                                            <div class="form-group mr-2 mb-0">
                                                <label class="sr-only" for="reference-{{ $order->id }}">Reference</label>
                                                <input type="text" id="reference-{{ $order->id }}" name="reference"
                                                       value="{{ $order->payment_reference }}"
                                                       class="form-control form-control-sm" placeholder="Reference">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fa fa-check mr-1"></i> Verify &amp; mark paid
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.payments.reject', $order) }}" class="form-inline mb-2" data-submit-once>
                                            @csrf
                                            <div class="form-group mr-2 mb-0">
                                                <label class="sr-only" for="reason-{{ $order->id }}">Reason</label>
                                                <input type="text" id="reason-{{ $order->id }}" name="reason"
                                                       class="form-control form-control-sm" placeholder="Reason for rejection">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fa fa-times mr-1"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No orders with this payment status.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    </div>
</div>
@endsection
