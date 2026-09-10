# Fiat Lux Document Request Hub — UAT Checklist

Run ID: __________  Date: __________  Tester: __________  Browser/device: __________

Use only dummy records. Mark every item `PASS`, `FAIL`, or `BLOCKED`, and attach a bug ID for failures.

## Before testing

- [ ] Run `php artisan backup:create --name=before-uat`.
- [ ] Run `php artisan backup:restore-test BACKUP_FILENAME.zip`.
- [ ] Run `php artisan uat:preflight --with-tests`.
- [ ] Run `php artisan uat:prepare` and privately distribute each role's credentials.
- [ ] Confirm the test URL uses HTTPS when testing a hosted environment.

## Student

- [ ] Sign in using the student number and correct password.
- [ ] Confirm admin, registrar, Grade Portal, analytics, settings, and logs are inaccessible.
- [ ] Submit each available document-request type using valid dummy data.
- [ ] Upload a JPG/PNG/PDF receipt below 5 MB and confirm success.
- [ ] Try an invalid/oversized receipt and confirm validation blocks it.
- [ ] View the student's own receipt from My Requests.
- [ ] Paste another student's receipt URL and confirm a 403 response.
- [ ] Track pending, processing, ready-to-release, completed, and rejected statuses.
- [ ] Confirm logout ends the session and protected pages require login.

## Records Officer

- [ ] Sign in and view active requests.
- [ ] Process a request without accessing admin-only user/settings/log pages.
- [ ] Confirm payment receipt access works and is logged.
- [ ] Confirm registrar-only approval controls remain unavailable.
- [ ] Generate supported draft documents and confirm request/student data match.

## Registrar

- [ ] Sign in and view the correct request queue.
- [ ] Review receipts and confirm payments.
- [ ] Update accounting clearance where authorized.
- [ ] Review/approve a processed request for release.
- [ ] Upload and privately view a Grade Portal reference file.
- [ ] Try invalid type, invalid school year, and file above 20 MB.
- [ ] Confirm admin-only analytics/settings/user management are inaccessible.

## Administrator

- [ ] Create/update test accounts without exposing passwords in logs.
- [ ] Review all active requests and role-specific controls.
- [ ] Review File Security audit events for upload/view/denied/missing/delete.
- [ ] Confirm private receipt and grade URLs require authentication.
- [ ] Run `php artisan system:health` and record warnings.
- [ ] Run `php artisan production:check` on the hosted configuration.
- [ ] Confirm unknown pages show the branded 404 without technical details.

## End-to-end request

- [ ] Student submits request and receipt.
- [ ] Authorized staff verifies payment and processes the request.
- [ ] Authorized issuer generates the draft/official document.
- [ ] Registrar completes required review/approval.
- [ ] Student sees the correct final status.
- [ ] Issued document QR/control number verifies correctly.
- [ ] An edited official file fails authenticity verification.
- [ ] Audit logs show the complete workflow without private paths or secrets.

## Sign-off

- [ ] No open Critical or High bugs.
- [ ] Every core workflow has at least one PASS result.
- [ ] Backup and isolated restore verification pass.
- [ ] `production:check` reports zero failures before public hosting.
- [ ] Product owner/school representative approved the UAT result.

Do not run `uat:cleanup` until results and screenshots are saved. Cleanup permanently removes only the specified local UAT run and requires `--confirm`.
