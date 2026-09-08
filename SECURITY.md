# Authentication security deployment

The login flow uses Cloudflare Turnstile, independent account and IP limits,
progressive account lockouts, Argon2id password hashing, generic public errors,
and database plus rotating-file audit logs.

## Production checklist

1. Create a Turnstile widget in Cloudflare and set the real
   `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY`. Set
   `TURNSTILE_ALLOWED_HOSTNAMES` to the production hostnames. The official test
   keys in the local `.env` must never be deployed.
2. Use `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure session cookies,
   and correctly configure Laravel trusted proxies so `Request::ip()` contains
   the real client address rather than an untrusted forwarded value.
3. Run Redis and set both `CACHE_STORE=redis` and
   `AUTH_SECURITY_CACHE_STORE=redis`. This shares lockouts between application
   servers; the database cache in the local `.env` is for development only.
4. Configure a real mail transport (`MAIL_MAILER=smtp` or another supported
   production mailer), a valid sender address, and `APP_URL` with the public
   HTTPS origin. The local `log` mailer only writes activation and reset links
   to `storage/logs/laravel.log`; it does not deliver email.
5. Run `php artisan migrate --force` during deployment. Also run Laravel's
   scheduler every minute so old login audits and expired student activation
   tokens are pruned automatically.
6. Route anomaly warnings to an actively monitored destination. For example,
   configure Laravel's existing Slack log channel and set
   `AUTH_ALERT_LOG_CHANNEL=slack`. Restrict access to `storage/logs/security-*`.
7. Run `composer audit` in CI and use reviewed, tested dependency updates.

## Student account registration

While no official roster is available,
`STUDENT_REGISTRATION_REQUIRE_ROSTER=false` permits registration with a unique
student number, name, and Gmail address. No `students` or `users` row is created
until the Gmail code is verified. Set this option to `true` as soon as the
official roster has been imported; roster mode requires the number, name, and
official Gmail to all match. Public responses remain generic for ineligible or
already-active records. Codes expire after 10 minutes and lock after five
incorrect attempts.

The pending password is Argon2id-hashed and the code is stored only as a keyed
SHA-256 digest. The server creates or completes the `student` user only after
the correct code is submitted, at which point the Gmail is marked verified and
the account becomes active. Active accounts cannot register again and should
use Forgot Password instead.

## Password migration

`HASH_DRIVER=argon2id` and `HASH_VERIFY=true` intentionally reject old bcrypt or
other non-Argon2id hashes. Existing accounts with legacy hashes must use the
Forgot Password flow once; the newly saved password will be Argon2id. Do not
disable hash verification to work around this migration.

Laravel's Argon2id driver uses PHP's `password_hash` and `password_verify` APIs.
The application never compares raw passwords and never logs passwords or
Turnstile tokens.

## Database least privilege

Use separate database credentials for deployment migrations and the running web
application. The web account should have only the required `SELECT`, `INSERT`,
`UPDATE`, and `DELETE` permissions on this application's schema. It should not
have schema-change, account-management, file, superuser, or access to unrelated
databases. SQLite is suitable for local development but does not provide
separate database accounts.

## Audit data

Each failed or blocked sign-in stores the attempted identifier, selected account
type, client IP, User-Agent, failure reason, anomaly flag, and timestamps in
`login_attempt_logs`; a matching event goes to the rotating `security` log.
An anomaly warning is raised when an account is attempted from at least three
distinct IPs, or an IP attempts at least ten accounts, within 15 minutes. These
thresholds are configurable through the `AUTH_*` environment values.

Audit data contains personal information. Limit access, back it up according to
school policy, and adjust both database and file retention to applicable privacy
requirements.
