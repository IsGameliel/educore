# Platform administration: navigation and phases 1–2

Implemented locally on 18 October 2026. This release covers platform access security, administrator roles and audit history. It does not implement the remaining billing, onboarding, operations or reporting phases.

## Screens and scope

The owner navigation is Overview · Institutions · Plans · Billing · Operations · Reports · Administrators · Audit Log · My Account. Navigation links and direct URLs use the same permission rules.

| Section | Current scope |
| --- | --- |
| Overview | Existing institution metrics and institution summary |
| Institutions | Institution search/filtering and existing institution detail controls |
| Plans | Dedicated plan list, creation and editing; separate from institution listings |
| Billing | Clearly labelled phase-3 landing page linking to institution details |
| Operations | Clearly labelled phase-5 landing page linking to institution details |
| Reports | Clearly labelled phase-6 landing page |
| Administrators | Invitations, role/status changes and password setup/recovery links |
| Audit Log | Searchable platform actions, actors, subjects and date range |
| My Account | Password change, authenticator enrollment/removal, one-use recovery codes and session revocation |

The three landing pages do not expose new payment adjustments, job retries, financial reports or recovery execution.

## Permissions

| Permission | Owner | Finance | Support | Read-only |
| --- | --- | --- | --- | --- |
| Overview and institution details | Yes | Yes | Yes | Yes |
| Plan configuration and feature overrides | Yes | No | No | No |
| Institution suspension, activation, archival and domain replacement | Yes | No | Yes | No |
| Billing and Reports sections | Yes | Yes | No | Yes |
| Operations section | Yes | No | Yes | Yes |
| Administrator management and central audit log | Yes | No | No | No |
| Own password, authenticator and sessions | Yes | Yes | Yes | Yes |

Existing administrators become owners during the initial security migration, preserving their previous authority. New database accounts default to read-only. The operator's existing `tenancy:install --admin-email=...` bootstrap creates an owner explicitly. Re-running initialization preserves assigned roles. Review pre-existing owners after deployment.

Administrator role/status changes require the acting owner's password and a reason. Changes revoke the target's sessions and pending setup/recovery links. No-op changes do not generate duplicate update events. All administrator rows are locked during changes to prevent concurrent removal of the last active owner. Administrators are deactivated, not deleted.

Support's onboarding and school-account recovery interfaces remain phase 4; this release does not give support staff owner privileges to substitute for those workflows.

## Account security

- Passwords require at least twelve characters with uppercase/lowercase letters and a number. Password changes require the current password and revoke other sessions.
- Authenticator enrollment is opt-in. Enter the manual setup key in a TOTP authenticator (six digits, thirty seconds), then confirm a code. Unconfirmed setup expires after ten minutes and does not enable protection.
- Confirmed enrollment issues eight recovery codes displayed once. Stored recovery codes are password-hashed. Authenticator secrets and temporary session displays are encrypted under the existing APP_KEY.
- Authenticator and recovery codes are checked before a platform login session is created. Pending sign-ins expire after five minutes and cannot survive an administrator security-version change. Used recovery codes cannot be reused; authenticator replay prevention reuses Fortify's provider.
- Disabling two-factor protection requires the current password and an authenticator/recovery code. Enrollment and removal revoke other sessions.
- Password recovery does not remove two-factor protection. Recovery links expire after one hour; invitations expire after twenty-four hours. Tokens are hashed, single-use and replaced on resend. Inactive accounts cannot recover access.
- Sensitive writes require password confirmation within ten minutes. Forms that already require the current password can confirm it directly. Other forms redirect to confirmation and return to the appropriate page so the operator can repeat the action.
- Session records identify the platform administrator separately from school and billing identities. A revoked session is denied on its next protected request. Already-running requests are not cancelled. Browser sessions predating this release must sign in again.
- Old browser sessions may remain in the session list until revoked; the list is an access registry, not proof that a browser is still online.
- Sign-in, recovery, confirmation, authenticator and invitation requests have dedicated rate limits. Passwords, recovery codes and reset tokens are excluded from validation old-input and platform audit details.

Mail delivery uses the configured operator mail transport. Use a deliverable mailbox when inviting a colleague or testing recovery. The local tester's `.test` email is suitable for password login, not external email delivery. Automated verification fakes mail; real SMTP delivery remains a separate environment check. No external messages were sent during this implementation.

## Audit history

The owner can filter events by action, administrator, exact subject ID and date range. Events record the actor, request IP, time and selected details. Plan edits capture prices/features before and after. Institution status/domain changes capture before/after values and their reason. Feature overrides, administrator changes, sign-ins/failures, access denials, password changes/recovery and authenticator changes are recorded.

Events start with this release; earlier actions are not reconstructed. There are no application routes to edit or delete audit events. This is application-level audit history, not an externally immutable or cryptographically tamper-proof log. Protect database/operator access and backups accordingly.

## Migration and rehearsal

The security migration adds columns to `platform_administrators` and creates three central-only tables: `platform_account_tokens`, `platform_login_sessions` and `platform_audit_events`. The fresh-install institution-table classifier explicitly excludes them. The legacy school database is not migrated.

Rehearse locally before updating a working shared database:

```powershell
php tests/Manual/PlatformSecurityAcceptance.php
php artisan test --compact tests/Feature/PlatformAdministrationTest.php tests/Feature/TenantFoundationTest.php
```

The MySQL rehearsal generates an exact disposable database name, verifies fresh installation, repeat installation, least-privilege defaults and preservation of existing administrator credentials/authority, then drops only that database. It performs no external mail or gateway calls.

Use the established supervised upgrade procedure in [the operations runbook](multi-tenancy-operations.md). Pause shared writers first; then run `php artisan tenancy:upgrade --writers-paused`. Do not run an ordinary shared-database migration or change APP_KEY. Retain the verified pre-upgrade SQL backup and compare record counts and credential fingerprints before/after.

The local rollout succeeded with ID `83c30844-38c5-4f46-adde-21c124eccc9a`. A verified shared SQL backup was created under `storage/app/backups/shared-upgrades/83c30844-38c5-4f46-adde-21c124eccc9a/`. Institution, administrator, user, subscription invoice and payment counts and existing administrator credential fingerprints were preserved. No production deployment occurred.

Validation: 12 platform-security tests passed with 153 assertions; 56 tenancy regression cases passed with 445 assertions. The disposable MySQL rehearsal passed ten checks. Actual local HTTP login and nine authenticated pages passed, including navigation rendering and no-store headers; an incorrect current password displayed the expected validation error. Blade compilation and whitespace checks passed. No browser automation surface was available, so screenshot-based layout review was not performed. Real SMTP delivery and production deployment remain unverified.

## Review checklist

1. Open `http://educore.test:8000/dashboard/login` and sign in with the existing local tester account supplied in chat. Check all nine navigation items, including the dedicated Plans page.
2. Open My Account. Change your own password if desired, then sign in from a second browser/private window. Revoke that second session and confirm its next request returns to sign-in.
3. Enroll an authenticator, save the recovery codes and sign out. Confirm password-only sign-in leads to the two-factor challenge. Test one recovery code once, then verify reuse fails.
4. From Administrators, invite an address you control as Finance. Use its emailed setup link. Confirm that account can view Billing/Reports and institution details, but cannot open Administrators, plan configuration or Audit Log, including by direct URL.
5. Repeat role checks for Support and Read-only. Support may use existing lifecycle/domain controls; Read-only cannot change institutions or plans. Every role can manage its own account security.
6. Deactivate a test colleague and confirm their existing session stops working. Reactivate them and resend a recovery link if necessary.
7. Attempt to demote/deactivate the last active owner. The application must refuse. Never use real school accounts for lifecycle tests.
8. Edit a test plan or change a test school's lifecycle status, then inspect Audit Log. Filter by action, administrator and date. Verify the reason and relevant before/after values appear without secrets.
9. Test password recovery using a deliverable email. After reset, old sessions must fail and existing authenticator protection must remain enabled.

Whole-platform restores remain supervised operator procedures. Automated refunds, permanent institution deletion and school impersonation remain outside this release and require separate designs.
