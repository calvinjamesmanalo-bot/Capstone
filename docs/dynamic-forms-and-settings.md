# Dynamic request forms and editable system content

## Availability and custom archive verification

Manage Request Forms retains the unified built-in/custom price list. Its **Current forms** view includes all built-ins and nonarchived custom forms, including disabled ones. The **Archived custom forms** view is a filter within the same page and displays archived custom definitions without edit, availability, or archive actions. No sidebar/navigation changes were made.

Archiving is custom-only: built-ins live in the fixed catalog and have no archive/delete endpoint. Custom archive now atomically sets `request_types.is_active` to false, increments its version, and soft-deletes the definition by setting `deleted_at`. Related requests, their original names, saved prices, snapshots, and status histories are retained. Archived forms cannot be re-enabled through the ordinary availability route.

Availability storage remains unchanged: built-ins use `settings.request_enabled_*` values (`1`/`0`, enabled by default if absent); custom forms use `request_types.is_active`, with a non-null `deleted_at` additionally excluding archived forms. Backend submission validation enforces these flags. Prices still use only the existing `settings.price_*` entries for built-ins and `request_types.fee` for customs.

No new migration, data deletion, pricing duplication, generator, or student-field configuration was introduced. The already removed Student Fields builder and Settings pricing section remain absent. Built-in workflows retain their existing generators; custom requests retain registrar manual preparation.

Verification: **61 tests passed (732 assertions)**, including all built-in listing/actions, archive/delete rejection for built-ins, name/price-only custom creation, editing, enabling/disabling, custom archive and preserved history, forged submissions, pricing, request search/status behavior, and built-in signed-document generation. Blade compilation and PHP formatting checks passed. The sidebar SHA-256 remained `34B86BA8DEBB87048246768007F544D91399EB5673C6F659E33C0CF009E95729`.

Files modified in this archive/availability follow-up:

- `app/Http/Controllers/RequestTypeController.php`
- `resources/views/request-types/index.blade.php`
- `tests/Feature/RequestPricingManagementTest.php`
- `docs/dynamic-forms-and-settings.md`

## Current pricing location: Manage Request Forms

Document pricing has been removed from the Settings page and its save handler. Manage Request Forms is now the single management interface for built-in and custom request prices and availability. It displays Name, Price, Type, Status, and Actions in one list. Built-in prices are edited inline; their names remain fixed and they have no archive/delete controls. Custom requests retain their name/price editor, availability toggle, and archive action.

Existing storage is reused: built-in fees remain in the existing `settings` rows (`price_form_137`, `price_form_138`, `price_certificate_enrollment`, `price_certificate_completion`, `price_good_moral`, `price_certificate_recognition`, and `price_diploma`). Custom fees remain in `request_types.fee`. No prices were copied to another table, and displaying the list does not create database records. Built-in availability uses `settings` keys prefixed with `request_enabled_`; missing entries mean enabled. Existing submitted requests keep their original `request_documents.document_price` snapshot.

Student choices and submission validation use the same catalog and saved prices. Disabled types cannot receive new submissions, including from previously opened pages. Existing requests and document generators remain available for processing. No migration was required and no saved prices were deleted. The sidebar/navigation file was left untouched (SHA-256 `34B86BA8DEBB87048246768007F544D91399EB5673C6F659E33C0CF009E95729`).

Verification for this pricing change: **47 tests passed (530 assertions)**, covering all seven built-in prices, custom prices, student display/submission, registrar authorization, disabled requests, reserved names, historical price snapshots, Settings saves without pricing inputs, and existing built-in signing and request behavior.

Files changed for this pricing move:

- Settings: `app/Http/Controllers/SettingController.php`, `resources/views/settings/index.blade.php`.
- Manage Request Forms: `app/Http/Controllers/RequestTypeController.php`, `resources/views/request-types/index.blade.php`, `app/Support/RequestCatalog.php`, `routes/web.php`.
- Student submission/catalog integration: `app/Http/Controllers/RequestController.php`.
- New test file: `tests/Feature/RequestPricingManagementTest.php`.
- Documentation: `docs/dynamic-forms-and-settings.md`.

Manual check: open Settings and verify that no pricing inputs remain; edit a built-in price in Manage Request Forms and verify the student option and submitted fee; disable/re-enable it and verify availability; edit/archive a custom form; confirm built-in rows offer only Edit Price and Enable/Disable. Verify an earlier request still shows its original fee.

This pricing section supersedes earlier references below to managing fees in Settings.

## Current behavior: simplified Manage Request Forms

The custom request feature now asks the registrar for **Request/Form Name and Price only**. The student field builder and all field-definition validation have been removed. New types are enabled automatically; the list provides edit, enable/disable, and archive actions. Creating a type needs only `name` and `fee`. Editing retains an internal version token to prevent stale saves.

Custom types appear in the existing student request screen without additional dynamic inputs. Submitted custom requests appear immediately in the registrar's existing request list, and the registrar can use the existing status sequence for manual preparation and release. There is no custom document generator, prepared-PDF upload, or signing prerequisite. Built-in document generation and signing requirements are unchanged.

Existing saved answers, private attachments, and database columns are retained for historical requests, but are not used to configure or validate new submissions. No database migration or data deletion is needed. The existing receipt requirement and duplicate-request protections remain in place.

The sidebar/navigation file `resources/views/layouts/app.blade.php` was not edited. Its SHA-256 before and after this simplification was `34B86BA8DEBB87048246768007F544D91399EB5673C6F659E33C0CF009E95729`.

Verification: 42 focused tests passed (441 assertions), including creation using only name/price, no required legacy fields, registrar receipt and manual completion, access control, disabled/archived types, historical attachment access, built-in request search/status/receipt behavior, and real signed built-in document generation.

Files modified for this simplification:

- `app/Http/Controllers/RequestTypeController.php`
- `app/Http/Controllers/RequestController.php`
- `app/Support/RequestStatusTransitions.php`
- `routes/web.php`
- `resources/views/request-types/edit.blade.php`
- `resources/views/request-types/index.blade.php`
- `resources/views/requests/student.blade.php`
- `resources/views/requests/index.blade.php`
- `resources/views/requests/partials/records-officer-card.blade.php`
- `resources/views/requests/partials/dynamic-answers.blade.php`
- `tests/Feature/DynamicRequestFormsTest.php`
- `tests/Feature/OfficialDocumentIssuanceTest.php`
- `docs/dynamic-forms-and-settings.md`

Removed obsolete files:

- `app/Support/DynamicRequestForm.php`
- `resources/views/requests/partials/dynamic-fields.blade.php`
- `resources/views/requests/partials/custom-preparation.blade.php`

Manual check: as registrar, add a name and price, edit them, and disable/enable the type; as student, select and submit it through the existing workflow; as registrar, confirm it appears immediately and process it through release without generating a document. Confirm built-in documents still offer their existing generator tools.

The following sections record the earlier implementation; the simplified behavior above supersedes their field-builder and custom-signing instructions.

The existing Laravel application was extended in place. The seven built-in document types, student three-step request screen, accounting receipt requirement, duplicate-request rule, payment/clearance controls, status history, notifications, document signing, and registrar review remain in use.

## What changed

- **Registrar → Request Forms** provides create/edit, enable/disable, and archive actions. Forms support a name, description, instructions, nonnegative fee, and ordered text, textarea, number, date, select, checkbox, and upload fields. Add/remove fields and use Move up/Move down to reorder. Select options are entered one per line.
- Custom types appear alongside built-in types on the student request screen. Only the selected type's inputs are submitted. The server validates against the saved definition and calculates the fee itself. Changed definitions require the student to reload before submitting.
- Each request saves its type ID, definition snapshot, fee, and answers. Renaming, changing, disabling, or archiving a form does not change earlier submissions. Archive retains the definition and prevents new submissions; it does not delete requests. Duplicate protection uses the type ID even after a rename.
- Staff see submitted answers in request management and history. Students can see their answers on the existing request receipt. Uploaded answers are stored on the private local disk; only the owner and authorized request-management staff may download them.
- Custom documents have no built-in document template. During Processing, an admin or records officer uploads a completed PDF using **Sign prepared PDF**. The existing signing service protects it, and the existing status controls forward it for registrar review and release. Existing signing credentials must be configured by the system operator. No credential settings were added to the web UI.
- **System Settings** now supports school address, issuer display name, request instructions, announcement, and an uploaded logo/seal. Existing school details, contact details, office hours, and fee settings are reused. Form 137 and Form 138 fees are now exposed. Existing certificate/diploma fee limits remain unchanged; custom form fees are configured in Request Forms.
- School names, addresses, and logos are loaded in the application shell, authentication pages, request receipts, and relevant generated document templates. The school profile name takes precedence over the institution-name fallback. Contact details, office hours, instructions, and announcements appear on the student request page. Issuer changes affect newly created document identities; previously signed identities retain their original issuer.
- The settings update handler persists only validated, explicitly supported keys. Forged environment/credential keys and arbitrary logo paths are ignored. SVG and executable uploads are rejected. No `.env` changes were made.

Limits: 50 fields per form; 1–100 select options; answer uploads are PDF/JPG/PNG up to 5 MB each; prepared PDFs up to 10 MB; logos are JPG/PNG up to 2 MB and 3000×3000 pixels. PHP/web-server aggregate upload limits still apply. Required checkboxes must be checked. The existing Accounting clearance receipt is still required, including when a custom fee is zero.

## Database and verification

The SQLite migration creates `request_types` (including ordered JSON field definitions, a version, and soft deletion), and adds nullable `request_type_id`, `form_snapshot`, and `dynamic_values` columns to `request_documents`. Settings continue using the existing `settings` table. No existing request records are converted or removed.

Migration applied successfully as batch 2. The local database had 0 request rows when checked. A consistent SQLite backup was created at:

`storage/app/dynamic-forms-before-migration-20260926-150923.sqlite`

Verification performed:

- Feature tests cover all dynamic input types, configuration validation, admin authorization, private attachment access, stale/disabled/archived forms, snapshots, duplicate protection after renaming, settings allowlisting, logo validation/reset, and preservation of previously signed issuer identities.
- A real-signature integration test covers custom PDF upload, cryptographic verification, staff preview, forwarding, registrar approval, and completion. Signing failures leave requests unprepared.
- Final focused run for the new features, real signing, and affected standalone templates: **31 passed (263 assertions)**.
- Existing receipt security, request search, status transitions, and settings checks were included in the focused regression run: **63 passed, 1 existing sidebar-label mismatch**.
- Full suite run: **233 passed, 8 failed**. Remaining failures are an existing sidebar-label expectation, three grade-portal tests requiring the missing `generator/database/database.sqlite`, and four legacy school-form integration cases without signing credentials. The later focused run also covers the added issuer-preservation test.
- Blade compilation, migration status, PHP formatting checks, and JavaScript syntax checks were run. Browser click-through testing was not available in this session.

The signing test harness was made portable to the running PHP/XAMPP installation and now cleans up its own signature verification files. It does not modify application signing configuration.

## Manual checklist

**Registrar / staff**

1. Open Request Forms. Create a type with description, instructions, fee, and each supported field type. Set required/optional fields and dropdown options; reorder, save, and reopen it.
2. Submit a request as a student, then open Active Requests. Expand Submitted form details and download its attachment. Confirm the displayed fee is the configured amount.
3. Edit/rename the form and change its fields/fee. Confirm the earlier request still displays its original answers, labels, document name, and fee.
4. Disable the form, then archive it. Confirm it disappears from new student requests while earlier submissions remain in management/history.
5. For a custom request, start Processing, upload a valid prepared PDF, preview it, forward to the registrar, approve release, and complete. Repeat with an invalid PDF/signing setup and confirm forwarding stays blocked.
6. In Settings, update the school profile, contact information, address, issuer display name, request instructions, announcement, logo, and fees. Check the student page, login branding, receipt, and a newly generated document. Restore the default logo and verify it returns.
7. Confirm an admin, records officer, or student cannot access form administration or change Settings. Only the registrar manages these pages.

**Student**

1. Open Request a Document. Select a custom type; verify its description, instructions, fee, and ordered fields. Switch back to a built-in type and confirm its existing school-year/level controls still work.
2. Leave required fields empty; try an invalid date/number/dropdown option or unsupported/oversized upload. Confirm validation errors, retained text answers, and the need to reselect uploaded files after an error.
3. Complete a valid request, review the dynamic answers in step 3, and submit with the existing accounting receipt. Check the ticket, status, and request receipt.
4. Try another active request for the same type; confirm the duplicate rule remains in effect.
5. Try an attachment URL while signed in as another student; access must be denied.
6. Leave a form open while a registrar edits or disables it, then submit; confirm the server rejects the outdated/unavailable form.

## Registrar ownership correction

At the user's request, Request Forms and the existing System Settings page are now registrar-only. Routes and Settings controller guards enforce this; the sidebar and registrar dashboard expose both links, and the admin dashboard no longer links to Settings. Existing saved forms/settings and document-processing roles are unchanged. No additional migration is needed. The existing session-timeout and maintenance fields move with the Settings page.

Files changed for this correction: `routes/web.php`, `app/Http/Controllers/SettingController.php`, `resources/views/layouts/app.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/dashboard-admin.blade.php`, `tests/Feature/DynamicRequestFormsTest.php`, `tests/Feature/SystemContentSettingsTest.php`, `tests/Feature/SettingsTest.php`, `tests/Feature/AdminStudentSessionSettingsTest.php`, `tests/Feature/DashboardQuickActionsTest.php`, `tests/Feature/RoleAuthorizationTest.php`, and this document. Earlier test counts below describe the initial implementation.

## Exact source file manifest

Created:

- `app/Http/Controllers/RequestTypeController.php`
- `app/Models/RequestType.php`
- `app/Support/DynamicRequestForm.php`
- `app/Support/RequestCatalog.php`
- `app/Support/SystemContent.php`
- `database/migrations/2026_09_26_000000_add_dynamic_request_forms.php`
- `resources/views/request-types/index.blade.php`
- `resources/views/request-types/edit.blade.php`
- `resources/views/requests/partials/dynamic-fields.blade.php`
- `resources/views/requests/partials/dynamic-answers.blade.php`
- `resources/views/requests/partials/custom-preparation.blade.php`
- `resources/views/requests/partials/school-content.blade.php`
- `tests/Feature/DynamicRequestFormsTest.php`
- `tests/Feature/SystemContentSettingsTest.php`
- `docs/dynamic-forms-and-settings.md`

Modified:

- `app/Http/Controllers/RequestController.php`
- `app/Http/Controllers/SettingController.php`
- `app/Http/Controllers/CertificationController.php`
- `app/Models/RequestDocument.php`
- `app/Support/DocumentQrCode.php`
- `app/Support/DocumentWorkbookVerification.php`
- `routes/web.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/settings/index.blade.php`
- `resources/views/requests/student.blade.php`
- `resources/views/requests/index.blade.php`
- `resources/views/requests/history.blade.php`
- `resources/views/requests/receipt.blade.php`
- `resources/views/requests/partials/records-officer-card.blade.php`
- `resources/views/partials/loading-overlay.blade.php`
- `resources/views/certifications/index.blade.php`
- `resources/views/certifications/partials/certificate.blade.php`
- `resources/views/diploma/pdf-template.blade.php`
- `resources/views/good-moral/pdf-template.blade.php`
- `resources/views/form-137/index.blade.php`
- `resources/views/form-138/pdf-template.blade.php`
- `resources/views/school-forms/records.blade.php`
- `resources/views/school-forms/pdf/f138-template.blade.php`
- `resources/views/school-forms/pdf/f138-three-term-template.blade.php`
- `resources/views/school-forms/pdf/f138-shs-semester-template.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/create-student-account.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/auth/verify-student-code.blade.php`
- `tests/Feature/OfficialDocumentIssuanceTest.php`

Runtime changes: `database/database.sqlite` was migrated; the backup above and test logs under `storage/logs/` were created, and normal Laravel view/test caches were regenerated. Temporary verification scripts were removed. No repository metadata was available in this workspace, so this is the explicit implementation manifest rather than a Git diff.
