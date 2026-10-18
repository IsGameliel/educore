# Fresh Euvion test installation on cPanel

For automatic deployments from GitHub, use [GitHub to cPanel](github-cpanel-deployment.md). It replaces the ZIP upload with a private Git checkout and separate releases; the database, domain and acceptance steps below still apply.

This installs an empty platform with new test institutions. No local database, school files, credentials or existing institutions are transferred. Public hosting has not been configured by preparing this kit. Use Paystack test mode during this online trial.

## 1. Confirm hosting and paths

Confirm with the host: PHP 8.2+ compatible with composer.lock, Composer 2, MySQL/MariaDB, Terminal/SSH, HTTPS, outbound HTTPS to Paystack and SMTP, cron every minute, and permission to add external school hostnames pointing to the same document root. Workers need CLI execution for up to 300 seconds and suitable memory. Verify required extensions with `composer check-platform-reqs --no-dev`; this app also needs GD/ZIP for image and spreadsheet work. Backups need executable MySQL client tools and permitted PHP process functions. A restrictive shared plan may prevent full operational testing.

Examples below use `/home/CPANELUSER/euvion` for the app. Replace CPANELUSER and all paths with the actual account details. Select PHP 8.3 (or a compatible installed version) in MultiPHP Manager, then identify the matching CLI PHP binary. Do not assume terminal `php` uses the web version. Example: `/opt/cpanel/ea-php83/root/usr/bin/php`.

The web root must be `/home/CPANELUSER/euvion/public`, serving Laravel's existing public/index.php. Keep the application, .env, vendor and storage outside public access. In cPanel Domains configure this root for euvion.com.ng. If the primary domain root is fixed at public_html, ask the hosting provider to configure the correct root before proceeding. Do not upload the complete repository into public_html or use a rewrite that exposes the application root.

Official references: [cPanel domain roots](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/) and [Laravel deployment](https://laravel.com/docs/12.x/deployment).

## 2. Upload the source bundle and install dependencies

On the development machine, `php scripts/package-cpanel.php` creates an allowlisted source ZIP and SHA-256 file in storage/app/deployment. It excludes vendor, node_modules, Vite build output, local .env files, school uploads, database dumps and runtime caches. Extract the ZIP into the private app directory with cPanel File Manager or SSH. Do not extract it directly into a public root. Verify the checksum against the accompanying file before extracting.

From the app directory in Terminal, using the verified PHP/Composer executable:

```sh
cd /home/CPANELUSER/euvion
php -v
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
npm ci
npm run build
test -f public/build/manifest.json
```

If Node/npm is unavailable on the hosting account, run the exact locked npm build on a trusted build machine and upload public/build. Do not enable the site with missing build assets. If `php` is the wrong version, invoke Composer through the matching PHP binary and its actual composer.phar path; use that PHP path for every Artisan and cron command too. Do not run Composer update, npm update or a Vite development server on hosting.

Ensure storage and bootstrap/cache are writable by the account's PHP user (typically directories 0755 or 0775 depending on the host). Do not use 0777. Retain public/.htaccess and enable Apache rewrite support. Do not carry over public/hot, public/storage links or cached local config.

## 3. Create two empty databases and database users

Use cPanel's MySQL Databases/Database Wizard. Record the actual prefixed names:

| Example | Purpose |
| --- | --- |
| CPANELUSER_euvion_support | Default connection used by unscoped operational services; separate from shared school records |
| CPANELUSER_euvion_shared | Central registry, platform accounts and institution-scoped school tables |
| CPANELUSER_runtime | SELECT, INSERT, UPDATE and DELETE on both databases; no schema changes |
| CPANELUSER_migrator | Installation/upgrade privileges on both databases; CLI only |

Use strong distinct passwords. Assign users to the databases with the appropriate privileges. The migrator can have all privileges on these two new databases only; it does not need global database creation privileges. Never point the shared and default connections at the same database. Do not import a local SQL backup for this fresh installation.

## 4. Configure the environment

```sh
cp deploy/cpanel/.env.example .env
chmod 600 .env
```

Edit .env privately with cPanel File Manager or an SSH editor. Set the exact prefixed database names, runtime credentials, SMTP credentials and a Paystack TEST secret. Keep APP_ENV=production, APP_DEBUG=false and the secure host-only cookie settings. CENTRAL_DOMAINS contains only euvion.com.ng and www.euvion.com.ng, never school hosts. Set APP_URL and PLATFORM_URL to https://euvion.com.ng without /dashboard. INSTITUTION_BASE_DOMAIN reserves the platform namespace; it no longer generates signup portal addresses.

For this fresh installation only:

```sh
php artisan key:generate --force
php artisan config:clear
```

Retain the generated APP_KEY securely with backups. Never regenerate it after accounts, encrypted settings or two-factor secrets are in use.

## 5. Install the support schema and shared platform

Leave cron/workers off and public ingress unavailable while installing. From a Bash terminal, read temporary migration credentials without placing passwords in shell history:

```sh
read -r -p 'Migration username: ' TENANCY_MIGRATION_USERNAME
read -r -s -p 'Migration password: ' TENANCY_MIGRATION_PASSWORD
printf '\n'
export TENANCY_MIGRATION_USERNAME TENANCY_MIGRATION_PASSWORD
DB_USERNAME="$TENANCY_MIGRATION_USERNAME" DB_PASSWORD="$TENANCY_MIGRATION_PASSWORD" php artisan migrate --database=mysql --force
php artisan tenancy:bootstrap --empty-database --admin-email=YOUR_OWNER_EMAIL --admin-name="Platform Owner"
unset TENANCY_MIGRATION_USERNAME TENANCY_MIGRATION_PASSWORD
```

Replace YOUR_OWNER_EMAIL before running. Bootstrap securely prompts for an owner password of at least twelve characters. It refuses a nonempty shared database and creates no demo schools/students. If installation fails partway, inspect the private logs and database state; do not drop or overwrite a database to make a command succeed. The installer refuses reruns on a partially populated shared database. Review recovery before continuing. After a successful first installation, schema changes use tenancy:upgrade with writers paused and dedicated migration credentials; never use bootstrap to reset data.

Finish from the app directory:

```sh
php artisan storage:link
php artisan config:cache
php artisan view:cache
```

Do not use route:cache blindly; this repository includes route closures. Do not run seeders or migrate:fresh. First owner login: https://euvion.com.ng/dashboard/login. Enable two-factor authentication in My Account and save recovery codes. Plans start unavailable for signup; enable the chosen test plans under Plans after configuring payments and mail.

## 6. Connect the main site and each school's own address

Point euvion.com.ng at the hosting server's actual public IP. Configure www appropriately and prefer a canonical redirect to https://euvion.com.ng. Add HTTPS using cPanel SSL/TLS Status/AutoSSL or the host's supported certificate workflow. Verify the certificate before login.

For each test institution, obtain permission to use its own hostname, for example academics.mudiameuniversity.edu.ng or portal.rivers-college.gov.ng. Its DNS administrator points that exact hostname to the Euvion server. Add the exact hostname in cPanel Domains and serve the same `/home/CPANELUSER/euvion/public` directory. Do not configure a redirect to euvion.com.ng; the original hostname must reach Laravel so it selects the institution. The institution's mail and other website records stay under its DNS administrator's control. No wildcard Euvion DNS is needed for these school-owned addresses.

Issue a certificate covering each school hostname and verify DNS/HTTPS. Adding DNS alone does not add the server's virtual host. A CNAME alone does not issue a certificate. Your hosting provider may need to enable external subdomain entries or adjust document-root restrictions. See [cPanel custom document roots](https://support.cpanel.net/hc/en-us/articles/360052432394-How-to-specify-a-custom-Document-Root-for-a-new-domain).

Register the school through https://euvion.com.ng/pricing using its own hostname, complete Paystack test payment, then branding. New addresses are pending: an administrator opens Institutions, selects the school and saves Primary portal domain after confirming authorization, DNS and HTTPS readiness. Use the same requested hostname and an audit reason. This operator action enables the address; the application does not provision public DNS/certificates. Return to the institution billing/setup page and continue into its school portal. Repeat with a second independently authorized hostname to test isolation. If school domains are unavailable, use two hostnames on another domain you control for the trial; do not claim example school domains you do not control.

## 7. Test payments, mail and workers

In the Paystack TEST integration configure callback `https://euvion.com.ng/subscribe/callback` and webhook `https://euvion.com.ng/subscribe/webhook`. Keep institution subscription keys separate from any school tuition integration. Verify checkout, successful callback, signed webhook, duplicate delivery and interrupted-checkout recovery. Never manually mark an invoice paid to simulate provider verification. Check SMTP delivery using an administrator invitation/reset and a school account email to addresses you control.

Use deploy/cpanel/cron.example for the every-minute scheduler, central worker and institution worker. Replace every path and confirm `flock` is installed. Set PHP_BIN to the same compatible PHP CLI binary used above. Test each script manually before enabling cron. Institution workers are tenant-aware and must use tenancy:work. The two worker locks keep repeated cron invocations from overlapping. Confirm the host permits these worker runtimes; if it kills them, use a host-managed worker service or change hosting rather than silently losing jobs.

The scheduler includes support-database operations plus shared institution dispatch and backups, so both schemas must be installed. Do not add the old separate weekly backup cron in addition to schedule:run. Verify actual SQL/file backups and an isolated restore; hosting backup retention is separate. Watch storage/logs/laravel.log and the cPanel worker/scheduler logs, and rotate them as part of hosting operations. Cron reference: [cPanel Cron Jobs](https://docs.cpanel.net/cpanel/advanced/cron-jobs/).

## 8. Acceptance checklist for the online trial

- Main landing page and pricing load at euvion.com.ng; /dashboard requires platform login.
- Owner/Finance/Support/Read-only permissions and unauthorized direct URLs behave correctly; audit events record changes.
- Each school signs up with its own hostname; duplicates and platform-hostname claims are rejected.
- Unverified school addresses cannot open the dashboard or receive a sign-in handoff.
- Provider-confirmed test payment starts branding; ready, verified addresses then open the school dashboard immediately, including before semester start.
- Logo, colour, school name, students and staff persist on the correct hostname; plan-restricted exams/results follow package permissions.
- School A credentials, records, files and sessions cannot access School B; neither school can open platform administration.
- Password reset, invitations, two-factor recovery, expired subscriptions, suspended access, retries and renewal snapshots work.
- Cron completes, queued emails/jobs run, backup/isolated restore works, and logs have no unresolved failures.
- Requests for /.env, /composer.json and private /storage files return no secrets; errors show no debug traces; HTTPS validates on every hostname.

Do not invite real students or enable live payments until this trial passes and the hosting limitations have been resolved.
