<?php

return [

    'name' => env('APP_NAME', 'WMS Enterprise'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    // WMS Custom Settings
    'wms' => [
        'company_name' => env('WMS_COMPANY_NAME', 'Global Logistics'),
        'low_stock_threshold' => (int) env('WMS_LOW_STOCK_THRESHOLD', 10),
        'currency' => env('WMS_DEFAULT_CURRENCY', 'USD'),
        'weight_unit' => env('WMS_DEFAULT_WEIGHT_UNIT', 'kg'),
        'volume_unit' => env('WMS_DEFAULT_VOLUME_UNIT', 'cbm'),
    ],

];
