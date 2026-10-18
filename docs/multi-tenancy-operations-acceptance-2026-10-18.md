# Steps 10–12 acceptance — 18 October 2026

Scope: institution payment integrations, tenant background processing, exports/restores, lifecycle controls and supervised shared-schema upgrades. See [operations runbook](multi-tenancy-operations.md) for policies and target-environment requirements.

## Evidence

- Full automated regression suite: **524 passed, 2 skipped, 3,641 assertions**, zero failures/errors (1,010.48 seconds). Later focused checks also passed for pending-migration maintenance refusal, Firebase denial, reserved domain rejection and worker cleanup ordering.

- 59 assertions/checks passed in tests/Manual/TenancyAcceptance.php against one freshly created disposable MySQL database with three institutions. The run verified encrypted institution keys, shared queue infrastructure and task-slot idempotency, institution archival/domain replacement, and replacement of one school's rows/files without changing another school's rows/files. Existing pricing, onboarding, API ownership, renewal and expiry checks also passed. The exact disposable database and storage directory were removed afterward.
- Focused feature checks passed for gateway HMAC ownership, duplicate settlement, overlapping gateway IDs between schools, suspended financial settlement, encrypted key rotation/context cleanup, disabled tenant Firebase resolution, queue identity and cancelled suspended work, scheduled task slots, scoped restore, current-password preservation, token revocation, archive tampering, financial rewind refusal, domain restrictions and audit history.
- Admin forms passed authenticated HTTP-kernel checks: secrets are neither displayed nor flashed; cross-institution archive download/restore IDs return 404. Authentication persists across subsequent requests after rebuilding the cached StartSession middleware when institution session managers change.
- Failure checks passed for maintenance retained after failed upgrade backup, refusal to clear maintenance while shared migrations remain pending, and recovery of an interrupted restore's staged files before restoring school availability.
- PHP syntax passed for 16 operational implementation files. Scheduler registration was inspected with tenancy:dispatch, tenancy:prune-exports and central shared backup entries present alongside existing legacy schedules.
- Eight native HTTP smoke checks passed after the development migration: platform, both school and legacy login pages returned 200; unknown host returned 404; institutional shared backup access returned 403; unauthenticated integration/export pages redirected to login.

## Development schema rollout

The shared development schema was upgraded using tenancy:upgrade --writers-paused. The command created and inspected a complete compatible SQL backup before migration, recorded successful rollout f9957cf0-8ece-47f5-8a68-f42ead7d2850 and cleared maintenance. Its backup is under storage/app/backups/shared-upgrades/f9957cf0-8ece-47f5-8a68-f42ead7d2850/manual/. The existing legacy school's database was not migrated.

## Validation boundaries

Provider responses were simulated, not live charges or settlements. Real Paystack checkout/webhook delivery requires each school and platform account's configured key and reachable HTTPS routes. No external mail was sent. Firebase is deliberately unavailable to institution contexts until a separate ownership design is implemented. No independent penetration test or high-volume concurrency/load test was performed.

Production DNS, certificates, runtime and operator database accounts, durable storage, scheduler and supervised workers are supplied as templates; they were not installed on a production host. Archive-first retention is implemented; permanent institution deletion is unavailable. Self-service restoration refuses changed financial data and mismatched schema, and excludes S3/cloud files. These limits are described in the runbook. Existing-school cutover remains step 14.
