# Tenant portals and isolation: development steps 4–6

Registered institution domains now use educore_shared. The existing school remains on its original database and its localhost/127.0.0.1 portal in local development. Institution signup, subscription charging and plan feature permissions are later steps.

## Test locally

1. Keep MySQL running in XAMPP.
2. Map these names in C:\Windows\System32\drivers\etc\hosts using an administrator editor:

   ```text
   127.0.0.1 educore.test
   127.0.0.1 foundation-alpha.educore.test
   127.0.0.1 foundation-beta.educore.test
   ```

3. Choose private passwords for the test institution administrators:

   ```powershell
   php artisan tenancy:admin-password foundation-alpha
   php artisan tenancy:admin-password foundation-beta
   ```

   Both fixture owner emails are admin@foundation.example. Passwords are privately prompted. The command revokes only that administrator's sessions and API tokens.

4. Run php artisan serve --host=127.0.0.1 --port=8000, then open:

   - http://foundation-alpha.educore.test:8000/login
   - http://foundation-beta.educore.test:8000/login

   Each school has its own login and dashboard. The dashboard route is /home. Institution Settings is in the school administrator sidebar at /institution/settings. Change the school name, timezone, logo, brand color and contact details there.

5. Optionally create a platform administrator using php artisan tenancy:install --admin-email=owner@example.com --admin-name="Platform Owner", then use http://educore.test:8000/dashboard/login. School credentials cannot sign in as platform administrators.

The hosts file was not changed by implementation. The original school portal remains at http://127.0.0.1:8000. For another school, provision its registered domain and add the matching local host mapping.

## Routing and authentication

ResolveInstitution runs before session/authentication middleware and record binding. The domain registry selects the school; request-provided institution IDs do not select a tenant. Unknown domains return 404; suspended/incomplete schools return 403. Central hosts cannot serve school login or protected school routes. Domain-specific route middleware also protects the platform and institution endpoints.

School login, registration, OTP, password reset, two-factor flows, Jetstream teams, Livewire and APIs use the current institution context. Host-only cookies have institution-specific names. institution_sessions uses a composite tenant_id/id primary key, so copied session IDs cannot retrieve another school's payload. Platform sessions use a separate table and guard. Reset tokens use tenant_id/email. Sanctum tokens are owned/scoped; login/two-factor limits include tenant identity.

Local/testing HTTP can use non-secure cookies; production preserves the configured secure-cookie policy. Configure HTTPS when deploying. Legacy-host defaults are empty outside local/testing environments; configure any intentional legacy host explicitly.

## Academic and administrative data

All institutional model classes use ownership scopes and write guards. The tenant connection also has a guarded query builder/grammar, so raw table queries, validation presence checks, joins, nested/OR conditions, bulk writes and queries removing Eloquent global scopes remain tenant-filtered.

- Reads, updates and deletes require the current tenant_id. OR clauses are grouped before the mandatory ownership condition.
- Inserts receive the tenant ID automatically; ownership changes and cross-school IDs are rejected.
- Foreign-key and known relationship fields are checked within the current school before writes. Polymorphic payment/token links are checked too.
- Business unique indexes include tenant_id, allowing repeated emails, matric numbers, faculty codes and academic names across schools.
- Platform tables cannot be queried through the tenant connection; central code explicitly uses its pinned connection.
- Account merging scans only tenant-owned tables and uses the school's session/reset tables.
- Tenant truncation and unsafe insert-from-query operations are blocked. Native SQL/DDL remain trusted operator tools.

School backup routes and the backup service deny whole-database operations in a tenant context. Tenant-filtered export/restore management remains Step 12; school restores must never drop shared tables.

## Files, cache and branding

The local/public disks use storage/app/private/tenants/{UUID}/private and /public, outside the shared public/storage symlink. Public-disk URLs go through /tenant-assets/{path}, requiring authentication and reading only that school's disk. Private documents retain record-authorized download routes. The public logo route exposes only the current institution's configured branding image. Traversal is rejected. Cloud disk keys have institution prefixes.

Cache keys and file directories are institution-specific. Database cache access, locks and flushes are tenant-filtered; unsupported backends with global flush behavior use the scoped database store. Flushing one school does not clear another's cache.

Only the school's administrator can update its name, timezone, contact details, logo and color. Context entry applies these to login/dashboard, mail sender names and account emails, receipts, transcripts, registration PDFs and student IDs. Context exit restores configuration, managers, timezone and URL generation. IDs use institution branding or a generic school mark instead of another school's crest; legacy documents preserve their old branding.

## Queued work

Institution jobs carry a trusted institution_id payload. Worker hooks open context before queued models are deserialized and close it after the entire attempt, including errors. Tenant queues are named institution_{UUID}; the institutions connection reads those queues centrally. Jobs for unknown/unavailable schools do not fall back to legacy records.

```powershell
php artisan queue:work institutions --queue=institution_INSTITUTION_UUID --tries=3
```

Replace the placeholder with the institution ID. Multiple comma-separated queue names can share a worker. Automated scheduler iteration, queue supervision and deployment rollout remain Step 11. Existing legacy workers keep their original connection.

## Verification and migration

```powershell
php artisan test --compact tests/Feature/TenantFoundationTest.php tests/Feature/DatabaseBackupsTest.php
```

Coverage includes shared-email login, session IDs, reset tokens, bearer tokens, raw queries/joins/OR conditions, foreign links, reused codes, model/bulk writes, independent files/cache/locks, queued model restoration, retries and domain guards. Real HTTP checks against MariaDB covered CSRF login, /home, settings updates, central login rendering, cross-domain rejection, own-file delivery, other-school file rejection and protected backups.

A verified SQL backup was created before the upgrade in storage/app/backups/before-tenant-isolation. The shared migrations were applied using tenancy:install. The existing school was not migrated or assigned tenant IDs.

Full regression verification: 490 passed, 2 skipped, 3,368 assertions. Additional document/mail checks are run after branding changes. Whole-database backups remain operator-only; existing-school migration is still a separate development step.
