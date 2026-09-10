<?php

return [

    // New and verified passwords use Argon2id. HASH_VERIFY prevents silently
    // accepting hashes produced by a different algorithm.
    'driver' => env('HASH_DRIVER', 'argon2id'),
    'verify' => env('HASH_VERIFY', true),
    'rehash_on_login' => true,

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => env('HASH_VERIFY', true),
        'limit' => env('BCRYPT_LIMIT', null),
    ],

    'argon' => [
        'memory' => env('ARGON_MEMORY', 19456),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 2),
        'verify' => env('HASH_VERIFY', true),
    ],

];
