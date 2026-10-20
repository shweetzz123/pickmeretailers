# Pick Me Retailers

A Shopee-style online store built with Laravel. Customers browse everyday
products and order them at retail pricing, or in bulk at resale (wholesale)
pricing. Orders are paid for manually via **GCash** and verified by an admin —
there is no third-party payment gateway to integrate.

## Features

- **Product catalog** with pack-size variants and dual pricing — a regular
  `product_price` plus an optional bulk `wholesale_price`.
- **Quantity-based bulk pricing** — any cart line at or above the bulk
  threshold (default **10 units**) is charged the wholesale/resale price for
  *all* units in that line; smaller lines pay the retail price. The threshold
  is configurable (see [Bulk pricing](#bulk-pricing)).
- **Cart & checkout** with a delivery-details confirmation step before the
  order is placed.
- **Manual GCash payments** — the customer scans a QR code, submits a
  13-digit reference (and an optional screenshot), and an admin confirms it
  from the payment queue. Confirmation issues an invoice and writes a
  transaction-ledger entry.
- **Coupons**, including percentage, fixed-amount, minimum-spend, and free
  shipping.
- **Flat shipping fee** — a single delivery fee configured by an admin under
  Settings and added to every order; set it to `0` for free shipping, and a
  free-shipping coupon waives it per order.
- **Order tracking**, invoices, reviews, wishlist, and newsletter sign-up.
- **Store locator** and a bulk-order inquiry form.
- **Admin panel** for products, inventory (with low-stock and history),
  orders, payments, accounts, coupons, hero slides, sale banners, store
  locations, and sales reports/CSV export.
- **Transactional email** (order placed, shipped, delivered, bulk inquiry)
  dispatched through the queue.
- Single user type for customers; admins are the only privileged accounts.

## Tech stack

- **PHP 8.2+ / Laravel 12**
- **MySQL** (models use Eloquent; `doctrine/dbal` is available for column
  changes)
- **Blade** templating with a **Tailwind CSS** storefront and a Bootstrap
  admin panel
- **Vite** for asset bundling
- **Pest** for tests, **Laravel Socialite** for Google sign-in,
  **Resend** for mail delivery

## Requirements

- PHP 8.2 or newer with the usual Laravel extensions (`mbstring`, `openssl`,
  `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd` or `imagick`,
  `fileinfo`)
- Composer
- Node.js & npm
- MySQL (or MariaDB)

## Getting started

```bash
# 1. Install PHP dependencies
composer install

# 2. Create the environment file and generate an app key
cp .env.example .env
php artisan key:generate

# 3. Point .env at your database, then create the schema
php artisan migrate

# 4. Install front-end dependencies and build assets
npm install
npm run build

# 5. Link public storage (product images, sale banners, payment QR)
php artisan storage:link
```

Then start the app:

```bash
php artisan serve
```

Or run the server, queue worker, and Vite dev server together:

```bash
composer dev
```

## Bulk pricing

A cart line qualifies for wholesale/resale pricing once its quantity reaches
the bulk threshold. When it qualifies, **every** unit in that line is charged
the wholesale price; below the threshold, the retail price applies. Products
without a `wholesale_price` always use the retail price, and pack-size
variants keep their own price.

The threshold defaults to **10** and can be changed in `.env`:

```env
SHOP_BULK_PRICE_THRESHOLD=10
```

Related settings live in `config/shop.php` (`bulk_pricing`,
`dispatch_window`, `store`).

## Admin access

New accounts created through registration or Google sign-in are plain
customers. To grant an account admin access, set its `user_type` to `admin`:

```sql
UPDATE users SET user_type = 'admin' WHERE email = 'you@example.com';
```

Admins can then reach the admin panel (products, orders, payments,
inventory, reports, settings, and more).

## Testing

The test suite uses Pest:

```bash
php artisan test
```

or

```bash
composer test
```

## Deployment

Production deployment notes (DigitalOcean VPS, Cloudflare, supervisor,
nginx, and the manual GCash payment setup) are in
[`deploy/README.md`](deploy/README.md). A shorter go-live checklist lives in
[`PRODUCTION_DEPLOYMENT_CHECKLIST.md`](PRODUCTION_DEPLOYMENT_CHECKLIST.md).

## License

Built on the [Laravel](https://laravel.com) framework, released under the
[MIT license](https://opensource.org/licenses/MIT).
