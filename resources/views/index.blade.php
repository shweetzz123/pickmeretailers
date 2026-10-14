@php
    $seo = [
        'title' => 'Pick Me Retailers | Shop Retail & Bulk Online',
        'description' => 'Pick Me Retailers is an online shopping platform where you can order everyday products at retail prices or buy in bulk at resale prices. Browse the shop and get fast, reliable delivery.',
        'type' => 'website',
    ];
@endphp

@extends('maindesign')
@section('index')

  <script>
    window.heroSlides = @json($slides);
  </script>

  <section
    x-data="{
      current: 0,
      slides: window.heroSlides,
      init() {
        setInterval(() => {
          this.current = (this.current + 1) % this.slides.length
        }, 5000)
      },
    }"
    class="relative h-[360px] w-full overflow-hidden md:h-[440px] lg:h-[520px]"
  >
    <template x-for="(slide, i) in slides" :key="i">
      <div
        class="absolute inset-0 transition-opacity duration-700 ease-in-out"
        :class="current === i ? 'opacity-100 z-10' : 'opacity-0 z-0'"
      >
        <div class="absolute inset-0">
          <img
            :src="slide.img"
            :alt="slide.heading"
            class="hidden md:block absolute inset-0 h-full w-full object-cover"
            loading="lazy"
            decoding="async"
          />
          <img
            :src="slide.mobileImg || slide.img"
            :alt="slide.heading"
            class="md:hidden absolute inset-0 h-full w-full object-cover"
            loading="lazy"
            decoding="async"
          />
        </div>
        <div class="absolute inset-0 bg-white/30"></div>
        <div class="absolute inset-0 z-10 flex items-center px-4 py-12 md:px-16 md:py-20">
          <div class="max-w-xl text-on-surface">
            <span
              class="mb-4 inline-block rounded bg-primary/10 px-3 py-1 font-label-caps text-label-caps text-primary"
              x-text="slide.tag"
            ></span>
            <h2
              class="mb-4 text-balance font-display-lg text-[clamp(1.75rem,4.2vw+1rem,3rem)] leading-tight md:mb-6"
              x-text="slide.heading"
            ></h2>
            <p
              class="mb-6 max-w-lg text-pretty font-body-lg text-body-md text-on-surface-variant md:mb-10 md:text-body-lg"
              x-text="slide.sub"
            ></p>
            <div class="flex gap-4">
              <a
                :href="slide.ctaLink"
                class="rounded-lg bg-primary px-6 py-3 font-bold text-white transition-all hover:opacity-90 active:scale-95 md:px-8 md:py-4"
                x-text="slide.ctaText"
              ></a>
            </div>
          </div>
        </div>
      </div>
    </template>

    <button
      @click="current = (current - 1 + slides.length) % slides.length"
      class="absolute left-2 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-on-surface/10 text-on-surface backdrop-blur-sm transition-all hover:bg-on-surface/20 md:left-4"
    >
      <span class="material-symbols-outlined">chevron_left</span>
    </button>
    <button
      @click="current = (current + 1) % slides.length"
      class="absolute right-2 top-1/2 z-20 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-on-surface/10 text-on-surface backdrop-blur-sm transition-all hover:bg-on-surface/20 md:right-4"
    >
      <span class="material-symbols-outlined">chevron_right</span>
    </button>

    <div class="absolute bottom-6 left-1/2 z-20 -m-3 flex -translate-x-1/2 gap-2 p-3">
      <template x-for="(slide, i) in slides" :key="'dot-'+i">
        <button
          @click="current = i"
          :aria-label="'Go to slide ' + (i + 1)"
          class="h-4 w-4 rounded-full transition-all md:h-3 md:w-3"
          :class="current === i ? 'bg-on-surface scale-110' : 'bg-on-surface/30 hover:bg-on-surface/50'"
        ></button>
      </template>
    </div>
  </section>

  <section class="mx-auto max-w-container-max px-4 py-section-gap md:px-margin-desktop">
    <div
      class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end md:mb-10"
    >
      <div>
        <h2 class="mb-2 font-headline-md text-headline-md">Shop by Category</h2>
        <p class="font-body-md text-body-md text-on-surface-variant">
          Choose from our wide selection of everyday products, electronics, home essentials, and more.
        </p>
      </div>
      <a
        href="{{ route('shop') }}"
        class="whitespace-nowrap font-bold text-primary underline-offset-4 transition-all hover:underline"
      >
        View All Collections
      </a>
    </div>

    <div x-data="carousel()" class="group/carousel relative">
      <button
        x-show="canScrollLeft"
        @click="scroll('prev')"
        class="absolute -left-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-surface text-on-surface shadow-md transition-all hover:bg-surface-container-high active:scale-90 md:-left-4 md:h-10 md:w-10"
        aria-label="Previous categories"
        x-cloak
      >
        <span class="material-symbols-outlined text-lg md:text-xl">chevron_left</span>
      </button>

      <div
        x-ref="track"
        class="hide-scrollbar flex snap-x snap-mandatory gap-gutter overflow-x-auto scroll-smooth"
      >
        @forelse ($categoryCards as $card)
          <a
            href="{{ $card['route'] }}"
            class="carousel-card group w-40 flex-shrink-0 snap-start sm:w-44 md:w-48"
          >
            <div
              class="group-hover:product-card-shadow relative aspect-square overflow-hidden rounded-lg border border-outline-variant bg-surface-container-high transition-all duration-300"
            >
              @if ($card['image'])
                <img
                  alt="{{ $card['name'] }}"
                  class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  src="{{ asset('products/' . $card['image']) }}"
                  loading="lazy"
                />
                <div
                  class="pointer-events-none absolute inset-0 rounded-lg bg-[linear-gradient(180deg,rgba(0,0,0,0)_55%,rgba(0,0,0,0.55)_100%)]"
                ></div>
              @else
                <div
                  class="flex h-full w-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-tertiary-container/40 to-secondary-container/40 p-4 text-center"
                >
                  <span class="material-symbols-outlined text-5xl text-primary">restaurant</span>
                </div>
              @endif
            </div>
            <div class="mt-3 md:mt-4">
              <p class="line-clamp-2 text-center font-headline-sm text-headline-sm text-on-surface">
                {{ $card['name'] }}
              </p>
            </div>
          </a>
        @empty
          <p class="py-8 font-body-md text-body-md text-on-surface-variant">
            No categories available.
          </p>
        @endforelse
      </div>

      <button
        x-show="canScrollRight"
        @click="scroll('next')"
        class="absolute -right-3 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-surface text-on-surface shadow-md transition-all hover:bg-surface-container-high active:scale-90 md:-right-4 md:h-10 md:w-10"
        aria-label="Next categories"
        x-cloak
      >
        <span class="material-symbols-outlined text-lg md:text-xl">chevron_right</span>
      </button>
    </div>
  </section>

  <section class="bg-surface-container-low py-section-gap">
    <div class="mx-auto max-w-container-max px-4 md:px-margin-desktop">
      <h2 class="mb-8 text-center font-headline-md text-headline-md md:mb-10">
        Top Picks For You
      </h2>
      <div class="grid grid-cols-1 gap-gutter md:grid-cols-2 lg:grid-cols-4">
        @foreach ($products as $product)
          <div
            class="hover:product-card-shadow group flex flex-col overflow-hidden rounded-lg border border-outline-variant bg-surface-container-lowest transition-all duration-300"
          >
            <a
              href="{{ route('product_details', $product->id) }}"
              class="relative block aspect-[4/3] overflow-hidden"
            >
              @if($product->typeLabel() !== '')
                <span
                  class="absolute left-4 top-4 z-10 rounded bg-tertiary-container px-2 py-1 font-label-caps text-label-caps text-on-tertiary-container"
                >
                  {{ $product->typeLabel() }}
                </span>
              @endif
              <img
                alt="{{ $product->product_title }}"
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                src="{{ asset('products/' . $product->product_image) }}"
              />
            </a>
            <div class="flex flex-grow flex-col justify-between p-6">
              <div>
                <p class="mb-1 font-body-md italic text-on-secondary-container">Featured</p>
                <h3 class="mb-1 font-headline-sm text-headline-sm">
                  {{ $product->product_title }}
                </h3>
                <p class="mb-4 line-clamp-2 font-body-md text-body-md text-on-surface-variant">
                  {{ Str::limit($product->product_description ?? 'Quality product, packed for reliable delivery.', 80) }}
                </p>
              </div>
              <div class="mt-auto flex items-center justify-between">
                <div>
                  <span class="font-price-display text-price-display text-primary">
                    &#8369;{{ number_format($product->product_price, 2) }}
                  </span>
                  @if($product->hasWholesalePrice())
                    <p class="text-xs font-bold text-green-600 mt-1">Buy {{ $product->bulkPriceThreshold() }}+ &#8369;{{ number_format($product->wholesale_price, 2) }} ea.</p>
                  @endif
                </div>
                <form action="{{ route('cart.add', $product->id) }}" method="POST" data-submit-once>
                  @csrf
                  <button
                    type="submit"
                    class="rounded-lg bg-primary p-2 text-white transition-all active:scale-95"
                    aria-label="Add {{ $product->product_title }} to cart"
                  >
                    <span class="material-symbols-outlined">add_shopping_cart</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
      <div class="mt-10 text-center">
        <a
          href="{{ route('shop') }}"
          class="inline-block rounded-lg bg-primary px-6 py-3 font-bold text-white transition-all hover:opacity-90 active:scale-95 md:px-8 md:py-4"
        >
          View All Products
        </a>
      </div>
    </div>
  </section>

  <section class="mx-auto max-w-container-max px-4 py-section-gap text-center md:px-margin-desktop">
    <h2 class="mb-8 font-headline-md text-headline-md md:mb-12">What Our Customers Say</h2>
    @if ($reviews->isEmpty())
      <p class="font-body-lg text-on-surface-variant">No reviews yet. Be the first to leave one!</p>
    @else
      <div class="grid grid-cols-1 gap-8 md:grid-cols-3 md:gap-12">
        @foreach ($reviews as $review)
          <div class="flex flex-col items-center">
            <div class="mb-6">
              @for ($i = 1; $i <= 5; $i++)
                <span
                  class="material-symbols-outlined {{ $i <= $review->rating ? 'text-tertiary-container' : 'text-outline-variant' }}"
                  style="font-variation-settings: 'FILL' {{ $i <= $review->rating ? 1 : 0 }}"
                >
                  star
                </span>
              @endfor
            </div>
            @if ($review->title)
              <p class="mb-2 font-headline-sm text-headline-sm">
                &ldquo;{{ $review->title }}&rdquo;
              </p>
            @endif

            <p class="mb-4 font-body-lg text-body-lg italic md:mb-6">
              &ldquo;{{ $review->body }}&rdquo;
            </p>
            <h4 class="font-headline-sm text-headline-sm">
              &mdash;
              {{ ($name = $review->user->name ?? 'Anonymous') ? strtok($name, ' ') . ' ' . strtoupper(substr(strrchr($name, ' ') ?: ' X', 1, 1)) . '.' : 'Anonymous' }}
            </h4>
          </div>
        @endforeach
      </div>
    @endif
  </section>

  <section class="bg-primary px-4 py-12 text-white md:px-margin-desktop md:py-20">
    <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-10 md:flex-row">
      <div class="text-left">
        <h2 class="mb-2 font-headline-md text-headline-md">Join the Inner Circle</h2>
        <p class="font-body-md text-body-md opacity-80">
          Subscribe for early access to new arrivals, exclusive deals, and bulk offers.
        </p>
      </div>
      <form class="flex w-full gap-2 md:w-auto">
        <input
          class="w-full rounded-lg border border-white/30 bg-white/10 px-6 py-4 text-white placeholder:text-white/60 focus:bg-white/20 focus:outline-none md:w-80"
          placeholder="Email Address"
          type="email"
        />
        <button
          class="rounded-lg bg-white px-8 py-4 font-bold text-primary transition-all hover:bg-surface active:scale-95"
          type="submit"
        >
          Subscribe
        </button>
      </form>
    </div>
  </section>
@endsection
