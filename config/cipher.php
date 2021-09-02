<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    | 256-bit, hex-encoded master encryption key.
    */
    'key' => env('CIPHER_KEY'),

    /*
    |--------------------------------------------------------------------------
    | CipherSweet Backend
    |--------------------------------------------------------------------------
    | One of "boring", "modern" or "fips"
    | See also https://ciphersweet.paragonie.com/security#informal-security-analysis
    */
    'backend' => 'boring',

    /*
    |--------------------------------------------------------------------------
    | Model Locations
    |--------------------------------------------------------------------------
    | Only used for encrypting existing, not-yet encrypted data
    */
    'model_locations' => [
        'app/Models',
    ]
];
