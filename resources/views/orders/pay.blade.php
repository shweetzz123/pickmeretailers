@extends('maindesign')

@section('content')

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  <div class="max-w-3xl mx-auto">

    <nav class="flex items-center gap-2 text-sm font-body-md text-on-surface-variant mb-8">
      <a href="{{ route('orders.show', $order->id) }}" class="hover:text-primary transition-colors">Order #{{ $order->id }}</a>
      <span class="material-symbols-outlined text-[16px]">chevron_right</span>
      <span class="text-on-surface font-bold">Pay with GCash</span>
    </nav>

    <header class="mb-8">
      <h1 class="font-headline-md text-headline-md text-on-surface mb-2">Pay with GCash</h1>
      <p class="font-body-md text-body-md text-on-surface-variant">
        Order #{{ $order->id }} &middot; Total due
        <span class="font-price-display text-primary font-bold">&#8369;{{ number_format($order->total, 2) }}</span>
      </p>
    </header>

    @if(session('orderMessage'))
      <div class="mb-6 p-4 bg-green-50 border border-green-300 rounded-lg text-green-700 text-sm">{{ session('orderMessage') }}</div>
    @endif

    @if($errors->any())
      <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-lg text-red-700 text-sm">
        <ul class="list-disc list-inside space-y-1">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($order->payment_status === 'rejected')
      <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-lg text-red-700 text-sm">
        <p class="font-bold mb-1">We could not verify your previous payment.</p>
        <p>{{ $order->payment_notes ?: 'Please double-check your GCash reference number and submit it again.' }}</p>
      </div>
    @endif

    @if($order->isAwaitingVerification())
      <div class="mb-6 p-6 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-blue-500">hourglass_top</span>
          <div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">Payment submitted</h2>
            <p class="font-body-md text-on-surface-variant text-sm mt-1">
              We received your GCash reference number{{ $order->payment_reference ? ' ('.$order->payment_reference.')' : '' }} and will verify it shortly.
              You can resubmit below if you notice a mistake.
            </p>
          </div>
        </div>
      </div>
    @endif

    {{-- Delivery address confirmation --}}
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 mb-6">
      <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary">local_shipping</span>
        Delivering to
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 font-body-md text-body-md">
        <div>
          <p class="text-on-surface-variant mb-1">Name</p>
          <p class="text-on-surface font-bold">{{ $order->name }}</p>
        </div>
        <div>
          <p class="text-on-surface-variant mb-1">Phone</p>
          <p class="text-on-surface font-bold">{{ $order->phone }}</p>
        </div>
        <div class="sm:col-span-2">
          <p class="text-on-surface-variant mb-1">Address</p>
          <p class="text-on-surface font-bold">{{ $order->address }}</p>
        </div>
      </div>
      <p class="text-xs text-on-surface-variant mt-4">
        Please make sure this delivery address is correct before you pay. If you need to change it,
        <a href="{{ route('contact_us') }}" class="text-primary font-bold hover:underline">contact us</a> before completing payment.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      {{-- QR / account details --}}
      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6 text-center">
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">Pay via GCash</h2>

        @if($gcash['qr'])
          <img src="{{ $gcash['qr'] }}" alt="GCash QR code" class="w-56 h-56 object-contain mx-auto border border-outline-variant rounded-lg mb-4">
        @else
          <div class="w-56 h-56 mx-auto mb-4 flex items-center justify-center bg-surface-container-low border border-dashed border-outline-variant rounded-lg">
            <span class="material-symbols-outlined text-4xl text-on-surface-variant">qr_code_2</span>
          </div>
          <p class="font-body-md text-on-surface-variant text-sm mb-4">No QR code has been configured yet. Use the GCash number below.</p>
        @endif

        @if($gcash['number'])
          <p class="font-body-md text-on-surface-variant text-sm">GCash number</p>
          <p class="font-headline-sm text-headline-sm text-on-surface mb-2">{{ $gcash['number'] }}</p>
        @endif
        @if($gcash['name'])
          <p class="font-body-md text-on-surface-variant text-sm">Account name</p>
          <p class="font-bold text-on-surface">{{ $gcash['name'] }}</p>
        @endif
      </div>

      {{-- Submit reference --}}
      <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-6">
        <h2 class="font-headline-sm text-headline-sm text-on-surface mb-4">Submit your payment</h2>

        <p class="font-body-md text-on-surface-variant text-sm mb-4">{{ $gcash['instructions'] }}</p>

        <form method="POST" action="{{ route('orders.pay.submit', $order->id) }}" enctype="multipart/form-data" class="space-y-4">
          @csrf

          <div>
            <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block" for="reference">GCash reference number</label>
            <input type="text" name="reference" id="reference" value="{{ old('reference', $order->payment_reference) }}" required maxlength="13" minlength="13" inputmode="numeric" pattern="\d{13}" autocomplete="off"
              class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-3 text-body-md focus:ring-primary focus:border-primary outline-none">
            <p class="text-xs text-on-surface-variant mt-1">The 13-digit reference number shown in your GCash receipt.</p>
          </div>

          <div>
            <label class="font-label-caps text-label-caps text-on-surface-variant mb-1 block" for="proof">Screenshot (optional)</label>
            <input type="file" name="proof" id="proof" accept="image/*"
              class="w-full bg-surface border border-outline-variant rounded-lg px-4 py-2 text-body-md focus:ring-primary focus:border-primary outline-none">
          </div>

          <label class="flex items-start gap-3 p-3 bg-surface-container-low rounded-lg text-sm text-on-surface cursor-pointer">
            <input type="checkbox" name="confirm_address" id="confirm_address" value="1" class="mt-0.5 h-4 w-4 accent-primary" required>
            <span>I confirm this delivery address is correct.</span>
          </label>

          <button type="submit" id="submit-payment-btn" disabled class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:opacity-90 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
            {{ $order->payment_status === 'rejected' ? 'Resubmit Payment' : 'Submit Payment' }}
          </button>
        </form>
      </div>
    </div>

    <div class="mt-8 text-center">
      <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center gap-2 text-primary font-bold hover:underline underline-offset-4 transition-all">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        Back to Order
      </a>
    </div>
  </div>
</main>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('confirm_address');
    var submitBtn = document.getElementById('submit-payment-btn');
    var reference = document.getElementById('reference');

    if (checkbox && submitBtn) {
      checkbox.addEventListener('change', function () {
        submitBtn.disabled = !checkbox.checked;
      });
    }

    if (reference) {
      reference.addEventListener('input', function () {
        var digits = reference.value.replace(/\D/g, '').slice(0, 13);
        if (reference.value !== digits) {
          reference.value = digits;
        }
      });
    }
  });
</script>

@endsection
