@php
    $shop = config('shop.store');
    $warehouse = config('shop.store.warehouse');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $invoice->invoice_number }} | Pick Me Retailers</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Muli', Arial, sans-serif; color: #1a1a1a; margin: 0; padding: 32px; background: #f4f4f4; }
        .sheet { max-width: 820px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; }
        .row { display: flex; justify-content: space-between; gap: 24px; }
        .muted { color: #6b6b6b; }
        .right { text-align: right; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .08em; color: #6b6b6b; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e2e2e2; font-size: 14px; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6b6b6b; }
        td.num, th.num { text-align: right; }
        .totals { margin-top: 16px; width: 320px; margin-left: auto; }
        .totals .line { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .totals .grand { border-top: 2px solid #1a1a1a; margin-top: 6px; padding-top: 10px; font-size: 18px; font-weight: 700; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: #e6f6ec; color: #1a7f43; }
        .actions { max-width: 820px; margin: 16px auto 0; display: flex; gap: 12px; justify-content: flex-end; }
        .btn { border: 0; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-primary { background: #7a1f1f; color: #fff; }
        .btn-ghost { background: #fff; color: #1a1a1a; border: 1px solid #ccc; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; max-width: none; padding: 24px; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="row">
            <div>
                <h1>{{ $shop['name'] ?? 'Pick Me Retailers' }}</h1>
                <p class="muted" style="margin:0;font-size:13px;">
                    {{ $warehouse['address'] ?? '' }}<br>
                    {{ $warehouse['phone'] ?? '' }}
                </p>
            </div>
            <div class="right">
                <h2>Invoice</h2>
                <p style="margin:0;font-size:14px;font-weight:700;">{{ $invoice->invoice_number }}</p>
                <p class="muted" style="margin:4px 0 0;font-size:13px;">
                    Issued {{ $invoice->issued_at?->format('M d, Y') }}<br>
                    Order #{{ $order->id }}
                </p>
                <p style="margin-top:8px;"><span class="badge">{{ $order->paymentStatusLabel() }}</span></p>
            </div>
        </div>

        <div class="row" style="margin-top:32px;">
            <div>
                <h2>Billed to</h2>
                <p style="margin:0;font-size:14px;font-weight:700;">{{ $order->name }}</p>
                <p class="muted" style="margin:4px 0 0;font-size:13px;">
                    {{ $order->address }}<br>
                    {{ $order->phone }}
                </p>
            </div>
            <div class="right">
                <h2>Payment</h2>
                <p class="muted" style="margin:0;font-size:13px;">
                    Method: {{ strtoupper($order->payment_method ?? 'GCash') }}<br>
                    Reference: {{ $order->payment_reference ?? '—' }}<br>
                    Paid: {{ $order->paid_at?->format('M d, Y g:i A') ?? '—' }}
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->product_title ?? 'Item no longer available' }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">&#8369;{{ number_format($item->price, 2) }}</td>
                        <td class="num">&#8369;{{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="line"><span class="muted">Subtotal</span><span>&#8369;{{ number_format($invoice->subtotal, 2) }}</span></div>
            @if((float) $invoice->discount > 0)
                <div class="line"><span class="muted">Discount</span><span>-&#8369;{{ number_format($invoice->discount, 2) }}</span></div>
            @endif
            <div class="line"><span class="muted">Shipping</span><span>FREE</span></div>
            <div class="line grand"><span>Total</span><span>&#8369;{{ number_format($invoice->total, 2) }}</span></div>
        </div>

        <p class="muted" style="margin-top:40px;font-size:12px;text-align:center;">
            This is a system-generated invoice from {{ $shop['name'] ?? 'Pick Me Retailers' }}. Thank you for your order.
        </p>
    </div>

    <div class="actions">
        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-ghost">Back to order</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
    </div>
</body>
</html>
