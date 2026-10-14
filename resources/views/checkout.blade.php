@php
    $seo = ['robots' => 'noindex, nofollow'];
@endphp

@extends('maindesign')
@section('checkout')

<main class="max-w-container-max mx-auto px-4 md:px-margin-desktop py-8 md:py-12">
  @if($cart->isEmpty())
    <div class="flex flex-col items-center justify-center py-20 text-center">
      <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-6">shopping_cart</span>
      <h1 class="font-display-lg text-display-lg-mobile text-on-surface mb-4">Your basket is empty.</h1>
      <p class="font-body-lg text-body-lg text-on-surface-variant mb-8">You cannot proceed to checkout without items in your cart.</p>
      <a href="{{ route('shop') }}" class="bg-primary text-white font-bold py-4 px-8 rounded-lg hover:opacity-90 transition-all active:scale-95">Return To Shop</a>
    </div>
  @else
    @if ($errors->any())
      <div class="mb-8 p-4 bg-red-50 border border-red-300 rounded-lg text-red-700 text-sm">
        <p class="font-semibold mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('checkout.placeOrder') }}" method="POST" id="checkout-form">
      @csrf
      <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-12">
        <div class="lg:col-span-7 space-y-12">
          <section>
            <div class="flex items-center justify-between mb-6">
              <h2 class="font-headline-md text-headline-md text-on-surface flex items-center">
                <span class="bg-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">1</span>
                Contact Information
              </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">EMAIL ADDRESS *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('email') border-red-500 @enderror"
                  name="email"
                  value="{{ old('email', Auth::user()->email ?? '') }}"
                  placeholder="chef@example.com"
                  type="email"
                  required
                  maxlength="255"
                >
                @error('email')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">FIRST NAME *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('first_name') border-red-500 @enderror"
                  name="first_name"
                  value="{{ old('first_name') }}"
                  placeholder="First name"
                  type="text"
                  required
                  maxlength="255"
                >
                @error('first_name')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">LAST NAME *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('last_name') border-red-500 @enderror"
                  name="last_name"
                  value="{{ old('last_name') }}"
                  placeholder="Last name"
                  type="text"
                  required
                  maxlength="255"
                >
                @error('last_name')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
            </div>
          </section>

          <section>
            <h2 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center">
              <span class="bg-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">2</span>
              Shipping Details
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">STREET ADDRESS *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('address') border-red-500 @enderror"
                  name="address"
                  value="{{ old('address') }}"
                  placeholder="House number and street name"
                  type="text"
                  required
                  maxlength="255"
                  autocomplete="off"
                >
                @error('address')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">APARTMENT, SUITE, ETC. (OPTIONAL)</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('address2') border-red-500 @enderror"
                  name="address2"
                  value="{{ old('address2') }}"
                  placeholder="Unit 4B"
                  type="text"
                  maxlength="255"
                  autocomplete="off"
                >
                @error('address2')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">POSTAL CODE *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('postal') border-red-500 @enderror"
                  name="postal"
                  value="{{ old('postal') }}"
                  placeholder="1200"
                  type="text"
                  inputmode="numeric"
                  required
                  maxlength="4"
                  minlength="4"
                >
                @error('postal')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">REGION *</label>
                <select
                  id="select-region"
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('region') border-red-500 @enderror"
                  name="region"
                  required
                >
                  <option value="">Select Region</option>
                </select>
                @error('region')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">CITY / MUNICIPALITY *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('city') border-red-500 @enderror"
                  name="city"
                  value="{{ old('city') }}"
                  placeholder="Pasay"
                  type="text"
                  required
                  maxlength="255"
                  autocomplete="off"
                  inputmode="text"
                >
                @error('city')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">BARANGAY *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('barangay') border-red-500 @enderror"
                  name="barangay"
                  value="{{ old('barangay') }}"
                  placeholder="San Isidro"
                  type="text"
                  required
                  maxlength="255"
                  autocomplete="off"
                >
                @error('barangay')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div class="md:col-span-2">
                <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">PHONE NUMBER *</label>
                <input
                  class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('phone') border-red-500 @enderror"
                  name="phone"
                  value="{{ old('phone') }}"
                  placeholder="09171234567"
                  type="tel"
                  inputmode="numeric"
                  required
                  maxlength="11"
                  minlength="11"
                >
                @error('phone')
                  <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
              </div>
            </div>
            <div class="mt-4 p-3 rounded-lg bg-surface-container-low border border-outline-variant text-sm text-on-surface-variant">
              <strong class="text-on-surface font-bold">Same-day delivery:</strong> available from
              {{ app(\App\Services\DeliveryWindow::class)->label() }}. Orders placed after
              {{ app(\App\Services\DeliveryWindow::class)->endLabel() }} are delivered the next day.
            </div>
          </section>

          <section>
            <h2 class="font-headline-md text-headline-md text-on-surface mb-6 flex items-center">
              <span class="bg-primary text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">3</span>
              Order Notes
            </h2>
            <div>
              <label class="font-label-caps text-label-caps block mb-2 text-on-surface-variant">DELIVERY INSTRUCTIONS (OPTIONAL)</label>
              <textarea
                class="w-full p-4 border border-outline-variant rounded-lg focus:border-primary focus:ring-1 focus:ring-primary transition-colors @error('notes') border-red-500 @enderror"
                name="notes"
                rows="4"
                placeholder="Optional delivery notes"
                maxlength="2000"
              >{{ old('notes') }}</textarea>
              @error('notes')
                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
          </section>
        </div>

        <div class="lg:col-span-5">
          <div class="sticky top-28 bg-surface-container-lowest border border-outline-variant p-8 rounded-lg">
            <h2 class="font-headline-md text-headline-md text-on-surface mb-8 border-b border-outline-variant pb-4">Order Summary</h2>
            <div class="space-y-6 mb-8">
              @foreach($cart as $cartProduct)
                <div class="flex gap-4">
                  <div class="w-20 h-20 bg-surface-container-high rounded overflow-hidden flex-shrink-0">
                    <img class="w-full h-full object-cover" alt="{{ $cartProduct->product->product_title }}" src="{{ asset('products/'.$cartProduct->product->product_image) }}">
                  </div>
                  <div class="flex-1">
                    <h3 class="font-headline-sm text-headline-sm text-sm">{{ $cartProduct->product->product_title }}</h3>
                    @if($cartProduct->variant)
                      <p class="text-on-surface-variant text-sm font-label-caps">{{ $cartProduct->variant->weight }}</p>
                    @endif
                    <p class="text-on-surface-variant text-sm">{{ $cartProduct->quantity }} x &#8369;{{ number_format($cartProduct->unitPrice(), 2) }}</p>
                    <p class="font-price-display text-price-display text-primary mt-1">&#8369;{{ number_format($cartProduct->subtotal(), 2) }}</p>
                  </div>
                </div>
              @endforeach
            </div>
            <div class="space-y-3 pt-6 border-t border-outline-variant">
              <div class="flex justify-between text-on-surface-variant">
                <span>Subtotal</span>
                <span class="font-price-display text-lg">&#8369;{{ number_format($total, 2) }}</span>
              </div>
              <div id="coupon-entry" class="{{ $couponCode ? 'hidden' : '' }}">
                <div class="flex gap-2">
                  <input
                    type="text"
                    id="coupon-input"
                    class="flex-1 p-3 border border-outline-variant rounded-lg text-sm focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                    placeholder="Coupon code"
                    maxlength="50"
                  >
                  <button type="button" id="coupon-apply-btn" class="px-4 py-3 bg-primary text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">Apply</button>
                </div>
                <p id="coupon-message" class="text-xs mt-2 hidden"></p>
              </div>
              <div id="coupon-applied" class="{{ $couponCode ? '' : 'hidden' }}">
                <div class="flex justify-between items-center text-on-surface">
                  <span class="text-sm font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-base">confirmation_number</span>
                    <span id="coupon-applied-code">{{ $couponCode }}</span>
                  </span>
                  <button type="button" id="coupon-remove-btn" class="text-xs text-on-surface-variant underline hover:text-red-600 transition-colors">Remove</button>
                </div>
              </div>
              <div id="discount-row" class="flex justify-between text-on-surface-variant {{ $discount > 0 ? '' : 'hidden' }}">
                <span>Coupon Discount</span>
                <span class="font-price-display text-lg text-green-600">-&#8369;<span id="discount-amount">{{ number_format($discount, 2) }}</span></span>
              </div>
              <div class="flex justify-between text-on-surface-variant">
                <span>Shipping</span>
                <span class="font-price-display text-lg text-green-600 font-semibold">FREE</span>
              </div>
              <div class="flex justify-between text-on-surface pt-4 border-t border-outline-variant mt-4">
                <span class="font-headline-md text-headline-md">Total</span>
                <span class="font-price-display text-2xl text-primary font-bold">
                  <span id="grand-total-display">&#8369;{{ number_format($grandTotal, 2) }}</span>
                </span>
              </div>
            </div>
            <div class="mt-8 space-y-4">
              <div class="flex items-start gap-3 p-4 bg-surface-container-low rounded-lg text-sm text-on-surface-variant">
                <span class="material-symbols-outlined text-primary">verified</span>
                <p>Your order is protected by our Satisfaction Guarantee. If you're not satisfied, we offer a money-back guarantee.</p>
              </div>
              <button type="submit" id="place-order-btn" class="w-full bg-primary text-white font-headline-md py-5 rounded-lg hover:opacity-90 active:scale-95 transition-all shadow-lg flex items-center justify-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined">lock</span>
                <span id="btn-text">PLACE ORDER</span>
                <svg id="btn-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
              </button>
              <div class="flex items-start gap-2 p-3 bg-surface-container-low rounded-lg text-xs text-on-surface-variant">
                <span class="material-symbols-outlined text-primary text-sm">open_in_new</span>
                <p>After placing your order, you will be shown our GCash QR code and account details to complete your payment. We verify every payment before processing your order.</p>
              </div>
              <p class="text-center text-xs text-on-surface-variant mt-4">
                By clicking "Place Order", you agree to Pick Me Retailers <a class="underline" href="{{ route('terms.conditions') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>, <a class="underline" href="{{ route('privacy.policy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>, and <a class="underline" href="{{ route('store.policies') }}#refund-policy" target="_blank" rel="noopener noreferrer">Refund Policy</a>.
              </p>
            </div>
          </div>
        </div>
      </div>
    </form>

    {{-- Address confirmation before the order is created --}}
    <div id="confirm-address-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-address-title">
      <div class="absolute inset-0 bg-black/50" id="confirm-address-backdrop"></div>
      <div class="relative bg-surface-container-lowest border border-outline-variant rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 md:p-8 shadow-xl">
        <div class="flex items-center gap-3 mb-6">
          <span class="material-symbols-outlined text-primary">local_shipping</span>
          <h2 id="confirm-address-title" class="font-headline-md text-headline-md text-on-surface">Confirm your delivery details</h2>
        </div>

        <div class="space-y-5 text-sm">
          <div>
            <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">CONTACT</p>
            <p id="confirm-contact-name" class="text-on-surface font-bold"></p>
            <p id="confirm-contact-email" class="text-on-surface"></p>
            <p id="confirm-contact-phone" class="text-on-surface"></p>
          </div>
          <div>
            <p class="font-label-caps text-label-caps text-on-surface-variant mb-1">DELIVER TO</p>
            <p id="confirm-address-text" class="text-on-surface font-bold"></p>
          </div>
        </div>

        <div class="mt-6 p-4 bg-surface-container-low rounded-lg flex justify-between items-center">
          <span class="font-headline-sm text-headline-sm text-on-surface">Total</span>
          <span id="confirm-total" class="font-price-display text-price-display text-primary"></span>
        </div>

        <p class="text-xs text-on-surface-variant mt-4">Please make sure these details are correct. Your order will be created once you confirm.</p>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
          <button type="button" id="confirm-address-back" class="flex-1 bg-surface border border-outline-variant text-on-surface font-bold py-4 rounded-lg hover:bg-surface-container-high transition-all active:scale-95">Back to edit</button>
          <button type="button" id="confirm-address-submit" class="flex-1 bg-primary text-white font-bold py-4 rounded-lg hover:opacity-90 transition-all active:scale-95">Confirm &amp; Place Order</button>
        </div>
      </div>
    </div>
  @endif
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('checkout-form');
  var btn = document.getElementById('place-order-btn');
  var btnText = document.getElementById('btn-text');
  var btnSpinner = document.getElementById('btn-spinner');
  var grandTotalDisplay = document.getElementById('grand-total-display');
  var couponEntry = document.getElementById('coupon-entry');
  var couponApplied = document.getElementById('coupon-applied');
  var couponAppliedCode = document.getElementById('coupon-applied-code');
  var couponInput = document.getElementById('coupon-input');
  var couponApplyBtn = document.getElementById('coupon-apply-btn');
  var couponRemoveBtn = document.getElementById('coupon-remove-btn');
  var couponMessage = document.getElementById('coupon-message');
  var discountRow = document.getElementById('discount-row');
  var discountAmount = document.getElementById('discount-amount');

  var regionSelect = document.getElementById('select-region');

  var state = {
    subtotal: {{ $total }},
    discount: {{ $discount }}
  };

  var csrfHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': '{{ csrf_token() }}'
  };

  function formatPeso(value) {
    return value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function renderTotals() {
    var grandTotal = state.subtotal - state.discount;
    grandTotalDisplay.innerHTML = '&#8369;' + formatPeso(grandTotal);
  }

  function showCouponMessage(msg, isError) {
    couponMessage.textContent = msg;
    couponMessage.classList.remove('hidden', 'text-green-600', 'text-red-600');
    couponMessage.classList.add(isError ? 'text-red-600' : 'text-green-600');
  }

  var modal = document.getElementById('confirm-address-modal');
  var modalBackdrop = document.getElementById('confirm-address-backdrop');
  var modalSubmit = document.getElementById('confirm-address-submit');
  var modalBack = document.getElementById('confirm-address-back');
  var confirmed = false;

  function fieldValue(name) {
    var el = form ? form.elements[name] : null;
    return el ? String(el.value || '').trim() : '';
  }

  function buildAddressSummary() {
    var street = fieldValue('address');
    var unit = fieldValue('address2');
    var streetLine = unit ? street + ', ' + unit : street;
    var regionPostal = (fieldValue('region') + ' ' + fieldValue('postal')).trim();
    return [streetLine, fieldValue('barangay'), fieldValue('city'), regionPostal]
      .filter(function(part) { return part !== ''; })
      .join(', ');
  }

  function openConfirmModal() {
    document.getElementById('confirm-contact-name').textContent =
      (fieldValue('first_name') + ' ' + fieldValue('last_name')).trim();
    document.getElementById('confirm-contact-email').textContent = fieldValue('email');
    document.getElementById('confirm-contact-phone').textContent = fieldValue('phone');
    document.getElementById('confirm-address-text').textContent = buildAddressSummary();
    document.getElementById('confirm-total').innerHTML =
      '&#8369;' + formatPeso(state.subtotal - state.discount);
    modal.classList.remove('hidden');
  }

  function closeConfirmModal() {
    modal.classList.add('hidden');
  }

  if (form) {
    form.addEventListener('submit', function(e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        return;
      }
      if (!confirmed) {
        e.preventDefault();
        openConfirmModal();
        return;
      }
      btn.disabled = true;
      btnText.textContent = 'PROCESSING...';
      btnSpinner.classList.remove('hidden');
    });
  }

  if (modalSubmit) {
    modalSubmit.addEventListener('click', function() {
      confirmed = true;
      closeConfirmModal();
      if (form.requestSubmit) {
        form.requestSubmit();
      } else {
        form.submit();
      }
    });
  }

  if (modalBack) {
    modalBack.addEventListener('click', closeConfirmModal);
  }

  if (modalBackdrop) {
    modalBackdrop.addEventListener('click', closeConfirmModal);
  }

  if (couponApplyBtn) {
    couponApplyBtn.addEventListener('click', function() {
      var code = couponInput.value.trim();
      if (!code) {
        showCouponMessage('Please enter a coupon code.', true);
        return;
      }
      couponApplyBtn.disabled = true;
      fetch('{{ route("checkout.coupon") }}', {
        method: 'POST',
        headers: csrfHeaders,
        body: JSON.stringify({ code: code })
      })
      .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
      .then(function(result) {
        if (!result.ok) {
          showCouponMessage(result.data.message || 'Could not apply that coupon.', true);
          return;
        }
        state.discount = parseFloat(result.data.discount || 0);
        couponInput.value = '';
        couponEntry.classList.add('hidden');
        couponApplied.classList.remove('hidden');
        couponAppliedCode.textContent = result.data.couponCode;
        discountRow.classList.toggle('hidden', state.discount <= 0);
        discountAmount.textContent = formatPeso(state.discount);
        showCouponMessage(result.data.message, false);
        renderTotals();
      })
      .catch(function() {
        showCouponMessage('Could not apply that coupon. Please try again.', true);
      })
      .finally(function() { couponApplyBtn.disabled = false; });
    });
  }

  if (couponRemoveBtn) {
    couponRemoveBtn.addEventListener('click', function() {
      fetch('{{ route("checkout.couponRemove") }}', { method: 'DELETE', headers: csrfHeaders })
      .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
      .then(function(result) {
        if (!result.ok) return;
        state.discount = parseFloat(result.data.discount || 0);
        couponEntry.classList.remove('hidden');
        couponApplied.classList.add('hidden');
        discountRow.classList.add('hidden');
        showCouponMessage('Coupon removed.', false);
        renderTotals();
      })
      .catch(function() {});
    });
  }

  function loadRegions() {
    return fetch('/data/philippines/region.json')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        regionSelect.innerHTML = '<option value="">Select Region</option>';
        data.forEach(function(item) {
          var opt = document.createElement('option');
          opt.value = item.region_code === '13' ? 'Metro Manila' : item.region_name;
          opt.textContent = opt.value;
          regionSelect.appendChild(opt);
        });
        regionSelect.disabled = false;
      });
  }

  loadRegions().then(function() {
    renderTotals();
  });
});
</script>

@endsection
