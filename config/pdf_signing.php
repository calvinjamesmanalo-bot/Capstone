<?php

return [
    'certificate_path' => env('PDF_SIGN_CERT_PATH') ?: storage_path('app/private/signing/document-signing.crt'),
    'private_key_path' => env('PDF_SIGN_KEY_PATH') ?: storage_path('app/private/signing/document-signing.key'),
    'private_key_password' => env('PDF_SIGN_KEY_PASSWORD', ''),
    'signer_name' => env('PDF_SIGNER_NAME', env('DOCUMENT_ISSUER', env('APP_NAME', 'RegHub')).' Document Issuing System'),
    'reason' => env('PDF_SIGN_REASON', 'Official school document issuance'),
    'location' => env('PDF_SIGN_LOCATION', 'Office of the Registrar'),
    'openssl_config_path' => env('PDF_SIGN_OPENSSL_CONFIG')
        ?: (is_file('C:\\xampp\\php\\extras\\ssl\\openssl.cnf') ? 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf' : null),
];
