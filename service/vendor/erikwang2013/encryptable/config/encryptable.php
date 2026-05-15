<?php

/**
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Encryption key
    |--------------------------------------------------------------------------
    |
    | The key used to encrypt data.
    | Once defined, never change it or encrypted data cannot be correctly decrypted.
    |
    */

    'key' => env('ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Encryption cipher
    |--------------------------------------------------------------------------
    |
    | The cipher used to encrypt data.
    | Once defined, never change it or encrypted data cannot be correctly decrypted.
    |
    | Default is aes-128-ecb (matching MySQL's AES_DECRYPT default). ECB produces
    | deterministic ciphertext — identical plaintext always encrypts to identical
    | ciphertext. This enables encrypted-column querying (UniqueEncrypted /
    | ExistsEncrypted rules) but leaks equality patterns. Use a CBC/GCM cipher
    | (e.g. aes-256-cbc) if pattern concealment matters more than searchability.
    | All previous keys must use the same cipher as the primary key.
    |
    | IMPORTANT: ECB mode provides NO integrity authentication. Ciphertext can be
    | modified without detection. Do NOT rely on encryption alone for tamper-proofing.
    |
    */

    'cipher' => env('ENCRYPTION_CIPHER', 'aes-128-ecb'),

    /*
    |--------------------------------------------------------------------------
    | Previous encryption keys (rotation)
    |--------------------------------------------------------------------------
    |
    | Retired keys still accepted for decrypting existing ciphertext. Use the
    | same cipher as the primary key. Env ENCRYPTION_PREVIOUS_KEYS: comma list
    | or JSON array, e.g. "old16bytekeyaaa,older16bytekey" or ["k1","k2"].
    |
    */

    'previous_keys' => \Maize\Encryptable\Support\PreviousKeysParser::parse(env('ENCRYPTION_PREVIOUS_KEYS')),
];
