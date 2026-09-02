# regHub

regHub is a Laravel-based student information and document management system designed to streamline school registration processes and document requests.

## Features

- **Dashboard**: Overview of system activities and statistics.
- **Student Management**: Manage student profiles and information.
- **Document Requests**: Handle requests for various school documents:
    - Diplomas
    - Form 137 (Permanent Record)
    - Good Moral Certificates
- **Grade Management**: Record and track student grades.
- **Activity Logs**: Track system usage and administrative actions.
- **User Management**: Role-based access control for administrators and staff.
- **Settings**: Configure system-wide parameters.

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL/MariaDB

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-username/regHub.git
   cd regHub
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**:
   ```bash
   npm install
   npm run build
   ```

4. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Note: Update the database credentials in your `.env` file.*

5. **Run migrations and seeders**:
   ```bash
   php artisan migrate --seed
   ```

6. **Start RegHub**:

   On Windows, double-click `start-regHub.cmd` in the project folder. The launcher starts RegHub in the background and leaves it untouched when it is already running.

   Or start the complete development stack from a terminal:

   ```bash
   composer run dev
   ```

   RegHub, F137, and F138 all run at `http://127.0.0.1:8000`. The school-forms module is integrated and does not require a second server.

## Secure official document issuance

Form 138 and certification previews are drafts. They contain a visible `DRAFT` watermark and do not create an issuance record, QR, official hash, or PDF signature. An authorized Records Officer or administrator must use **Finalize & Issue**. Finalization reuses the existing Dompdf layout and performs this ordered pipeline:

1. Lock the existing request to prevent double issuance.
2. Assign the unique document/control ID and add its verification QR.
3. Render the final PDF with the existing document generator.
4. Import that PDF with FPDI/TCPDF and embed an X.509/PKCS#7 digital signature.
5. Calculate SHA-256 from the final signed bytes.
6. Store the immutable official PDF under `storage/app/private/issued-documents`.
7. Save the issuance, signed-file hash, certificate fingerprint, issuer, and audit events.
8. Mark the existing request `ready_to_release` and enable the protected official download.

The QR opens `/verify/{document-id}`. Public verification shows issuance/revocation status and supports exact-file SHA-256 comparison. The PDF's embedded signature independently detects post-signing edits in compatible readers such as Adobe Acrobat; uploading the file to RegHub is not required for that local integrity check.

### Signing configuration

Set `APP_URL` to the public HTTPS origin and configure `DOCUMENT_ISSUER`, `DOCUMENT_SIGNING_KEY`, and the `PDF_SIGN_*` variables shown in `.env.example`. Generate a self-signed development certificate with:

```bash
php artisan documents:generate-signing-certificate
```

By default the command writes `document-signing.crt` and `document-signing.key` to `storage/app/private/signing`, which is excluded from Git and is not web-accessible. Set `PDF_SIGN_KEY_PASSWORD` before generation to encrypt the private key. On Windows/XAMPP, set `PDF_SIGN_OPENSSL_CONFIG` only if the automatic `openssl.cnf` location does not match the installation. Restrict the private key to the application/service account, back it up securely, and never put it under `public/`.

A self-signed certificate provides cryptographic document-integrity validation, but Adobe will not automatically trust the signer identity on every computer. Trust requires importing the school's certificate into the reader/OS trust store or using a certificate issued by a trusted CA.

### Blockchain status

No blockchain client, smart contract, or blockchain API exists in this repository. Issuances therefore record `blockchain_status=unavailable`; the UI never claims that a transaction was recorded. `DocumentBlockchainService` is the integration boundary for a future project-specific adapter. Only the document ID, SHA-256 proof, type, issuer, and timestamp should be anchored—never grades, the PDF, or other unnecessary student data.

### Manual security checks

1. Process an existing Form 138 or certification request and open its draft preview.
2. Confirm the watermark appears and no issuance record is created before confirmation.
3. Choose **Finalize & Issue**, confirm, then download the official signed PDF.
4. In Adobe Acrobat, open the Signature panel and confirm the document has not changed since signing. A self-signed identity may still appear untrusted until its certificate is trusted locally.
5. Edit a visible value such as Mathematics `85` to `98`, save a separate copy, and reopen the signature panel. Acrobat should report that the signed content was modified.
6. Scan the QR and confirm the document ID, holder, status, X.509 signature status, and SHA-256 information match.
7. Upload the original PDF on the verification page and confirm an exact match; upload the edited copy and confirm a hash mismatch.
8. Revoke the issuance with a reason. Its original QR must show `REVOKED` and the original history must remain.
9. Correct the source data and finalize again. The replacement must receive a new document ID, signature, SHA-256 hash, and `reissued_from` link.

Back up `DOCUMENT_SIGNING_KEY` and the X.509 private key securely. Changing or losing either key affects validation of previously issued records. Verification attempts and issuance/revocation actions are visible under System Audit Logs.

## License

The regHub system is open-sourced software licensed under the [MIT license](LICENSE).
