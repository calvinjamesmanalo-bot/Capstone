<?php

return [
    'issuer' => env('DOCUMENT_ISSUER', env('APP_NAME', 'RegHub')),
    'signing_key' => env('DOCUMENT_SIGNING_KEY', env('APP_KEY')),
    'logo' => env('DOCUMENT_ISSUER_LOGO', 'certificates/fla-seal.jpg'),
    'blockchain_driver' => env('DOCUMENT_BLOCKCHAIN_DRIVER'),
];
