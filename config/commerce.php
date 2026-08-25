<?php

return [

    'currency' => 'SAR',

    // Flat shipping charged once per seller group in a cart/order.
    'shipping_flat_minor' => (int) env('COMMERCE_SHIPPING_FLAT_MINOR', 4_000),

    'gateway' => env('COMMERCE_GATEWAY', 'fake'),

    // The fake gateway is only allowed to settle outside production.
    'allow_fake_in_production' => false,
];
