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
    | One of "brng", "nacl" or "fips"
    | See also https://ciphersweet.paragonie.com/security#informal-security-analysis
    */
    'backend' => 'brng',

    /*
    |--------------------------------------------------------------------------
    | Model Locations
    |--------------------------------------------------------------------------
    | Define where you encryptable models are located. Used for encrypting
    | existing data and scanning for indexes.
    */
    'model_locations' => [
        'app/Models',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blind Index Model
    |--------------------------------------------------------------------------
    | Define which blind index model implementation should be used.
    */
    'index_model' => Chiiya\LaravelCipher\Models\BlindIndex::class,
];
