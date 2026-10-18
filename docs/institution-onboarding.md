# Paid institution onboarding and school dashboard entry

After a subscription is verified, the institution owner is sent to `/subscribe/onboarding` immediately, including when the invoiced semester starts in the future. The owner confirms the institution name, uploads a PNG/JPG/WebP logo up to 2 MB and selects a brand colour. Saving applies these settings to the institution profile and isolated branding storage, then opens the school's own dashboard.

The billing account remains available for invoice history and renewal. Its **Open school dashboard** button resumes setup if unfinished or opens the school once complete. Verified callbacks and **Check payment status** both start this journey. An interrupted journey resumes when the billing owner next opens the subscription page. Repeated completed-setup submissions do not overwrite branding or duplicate the completion event; later edits use Institution Settings.

School entry uses an automatically submitted POST containing a two-minute one-use handoff token. The database stores only the token hash. Entry checks the registered school context, central origin, current access, active original administrator and credential fingerprint. Expired, replayed, cross-school or credential-invalidated tokens are refused. Existing school two-factor authentication still requires its challenge before authentication. No platform administrator session or school impersonation feature is created.

The existing billing-owner session grants entry only to that institution's original active administrator. A separate school password reset does not change billing credentials, although pending handoffs become invalid when school security details change. School staff/students continue to use the school login as before.

The school dashboard and menus continue to enforce subscription feature permissions: student management and core administration follow the base plan; Gold includes results/transcripts; Platinum includes CBT, attendance and lecture materials. Completing branding does not grant higher-tier or unimplemented modules.

Owner-confirmed policy update: verified payment opens the dashboard immediately. Invoice dates/enrollment/prices are not changed. If an existing current paid package is still active, it keeps its feature snapshot until the next semester; otherwise the next paid package grants immediate access. Suspension/archival and expiry still block portal entry. Manually provisioned payment-exempt institutions keep their existing workflow. Existing self-registered paid schools without a completion marker receive onboarding on their next billing visit or authenticated browser dashboard request.

Platform navigation is now rendered only on platform routes, even when a browser also has a platform owner session. Institution billing and onboarding pages do not display the platform administrator sidebar.

## Local hostname

The development hostname must resolve on the computer running the browser. The existing paid school requires this Windows hosts-file entry:

```text
127.0.0.1 mudiame-university.educore.test
```

Open `C:\Windows\System32\drivers\etc\hosts` in an administrator editor and add the line if missing. Production uses configured institution DNS and HTTPS instead. If the address cannot resolve, the handoff cannot reach the school; return to the billing account and open the dashboard again after fixing the hostname.

## Validation and rollout

Focused tests exercise verified callback routing, billing/platform interface separation, required branding fields, scoped logo storage, correct school-dashboard authentication, token replay/cross-school/origin/expiry rejection, deactivated-account denial, school two-factor preservation, setup gating and idempotent completion. The MySQL rehearsal verifies the handoff table is central-only.

Final affected checks passed: 13 tests, 149 assertions, including immediate dashboard access for a future-dated paid subscription. The broader run had 70 other passing cases and exposed one test fixture inheriting a newly configured real payment key; the fixture now explicitly clears that key, and its affected case passes in the final checks. This is not a claim of a second full-suite run. The disposable MySQL rehearsal passed 11 checks. Actual local HTTP checks confirmed the paid school's login now returns 200 and institution billing pages exclude platform navigation even with a platform owner session present.

The development schema rollout `d1bf20cc-45e4-4af1-a2d0-cfe96d7f78bf` created and inspected a verified shared SQL backup before migration. Existing institution/user/invoice/payment counts, branding settings and school credential fingerprints were preserved. The legacy school database was not migrated. Real provider charges were not initiated during this change.
