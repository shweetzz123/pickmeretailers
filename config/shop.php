<?php

return [
    // Same-day delivery is only available inside this dispatch window.
    // The window is half-open: dispatch runs from start_hour up to end_hour.
    'dispatch_window' => [
        'start_hour' => (int) env('SHOP_DISPATCH_START_HOUR', 7),
        'end_hour' => (int) env('SHOP_DISPATCH_END_HOUR', 15),
    ],

    // Quantity-based (bulk) pricing. A product line priced at or above this
    // quantity is charged the product's wholesale_price (the "resale" price)
    // when one is set; below it the regular product_price applies.
    'bulk_pricing' => [
        'threshold' => (int) env('SHOP_BULK_PRICE_THRESHOLD', 10),
    ],

    'store' => [
        'name' => env('SHOP_NAME', 'Pick Me Retailers'),
        'branch_phone' => env('SHOP_BRANCH_PHONE', '+639396152389'),
        // Fixed warehouse/pickup location used as the origin for every delivery.
        'warehouse' => [
            'name' => env('SHOP_WAREHOUSE_NAME', 'Pick Me Retailers'),
            'phone' => env('SHOP_WAREHOUSE_PHONE', '+639396152389'),
            'address' => env('SHOP_WAREHOUSE_ADDRESS', 'Rosewood Homes, Sta. Rosa, Philippines, 4026'),
            'lat' => (float) env('SHOP_WAREHOUSE_LAT', 14.3000),
            'lng' => (float) env('SHOP_WAREHOUSE_LNG', 121.1000),
        ],
        'branches' => [
            [
                'name' => 'Main Branch',
                'address' => 'Rosewood Homes, Sta. Rosa, Philippines, 4026',
                'lat' => 14.3000,
                'lng' => 121.1000,
            ],
        ],
    ],
];
