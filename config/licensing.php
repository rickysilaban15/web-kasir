<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asymmetric Ed25519 License Token Signing Keys
    |--------------------------------------------------------------------------
    |
    | Private key (64-byte Ed25519 secret key dalam Base64) HANYA disimpan di
    | server dan digunakan untuk menandatangani token lisensi.
    | Public key (32-byte Ed25519 public key dalam Base64) digunakan oleh
    | aplikasi Flutter untuk memverifikasi keaslian token.
    |
    */
    'signing_private_key' => env('LARIS_LICENSE_PRIVATE_KEY', ''),
    'signing_public_key' => env('LARIS_LICENSE_PUBLIC_KEY', ''),

    'default_offline_grace_days' => (int) env('LARIS_DEFAULT_OFFLINE_GRACE_DAYS', 7),
];

