# Certification Maker Sandbox

Standalone Laravel sandbox for generating certificates and good moral PDFs without login, student search, or database integration.

## Included Templates

- Certificate of Enrollment
- Certificate of Completion
- Certificate of Good Moral Character
- Certificate of Recognition

## Workflow

Enter the student details manually, select a certificate type, review the preview, then open/print or download the generated PDF.

## Run

```bash
php artisan serve
```

Open `http://127.0.0.1:8000` or `http://127.0.0.1:8000/certifications`.

## Test

```bash
php artisan test
```

## Main Files

- `routes/web.php`
- `app/Http/Controllers/CertificationController.php`
- `resources/views/certifications/index.blade.php`
- `resources/views/certifications/preview.blade.php`
- `resources/views/certifications/pdf.blade.php`
- `resources/views/certifications/partials/certificate.blade.php`
- `resources/views/certifications/partials/styles.blade.php`
