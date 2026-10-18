# Euvion website, platform dashboard and school domains

## Address structure

| Address | Purpose |
| --- | --- |
| `https://euvion.com.ng/` | Public Euvion landing page |
| `https://euvion.com.ng/dashboard` | Platform administrator overview |
| `https://euvion.com.ng/dashboard/login` | Platform administrator sign-in |
| `https://euvion.com.ng/dashboard/institutions` | Institution monitoring and domain assignment |
| `https://euvion.com.ng/dashboard/plans` | Plan management |
| `https://euvion.com.ng/subscribe/login` | Institution subscription/billing account |
| `https://academics.mudiameuniversity.edu.ng/` | Example institution portal entry |
| `https://academics.mudiameuniversity.edu.ng/home` | That institution's authenticated school dashboard |

The same application serves all these hosts. The registered hostname selects the school; there is no separate installation/database for each domain. The school root directs an unauthenticated visitor to its school login and an authenticated visitor to its dashboard. The central landing page stays public.

Platform routes now live under `/dashboard`; their existing internal route names remain `platform.*`. Old `/platform` links redirect to `/dashboard`, preserving the request method and query string. A school hostname cannot serve platform administration. Existing school `/dashboard/widgets/*` actions remain school routes and are not made available on the central hostname.

## Every institution uses its own domain

Public signup requires an institution-owned hostname, such as `academics.mudiameuniversity.edu.ng` or `portal.rivers-college.gov.ng`. It no longer generates an Euvion subdomain. The account ID is only for subscription sign-in. Duplicate registered hostnames and Euvion subdomains are rejected during signup.

New signup addresses remain pending until an authorized operator verifies school authorization, DNS and HTTPS using the workflow below. Verified payment starts branding immediately. Branding is saved while domain connection is pending; dashboard entry waits for address approval. Existing registrations remain unchanged and can be moved through the same workflow.

## Connect a school-owned hostname

This is a platform operator workflow. A school cannot claim an arbitrary third-party hostname through public signup.

1. Obtain the school's authorization to use its hostname. For example, a school-controlled DNS zone `mudiameuniversity.edu.ng` can supply `academics.mudiameuniversity.edu.ng`.
2. The school's DNS administrator points the `academics` hostname to the public Euvion ingress. With direct server hosting, an A record uses that server's real public IPv4 address. An operator-approved ingress CNAME is another deployment option; the target must be supported by the hosting provider.
3. Configure the web server/reverse proxy to serve this exact hostname from the same reviewed Euvion release's `public` directory. Start with `deploy/euvion/nginx-institution.conf.example`; replace paths, PHP socket and example hostname as appropriate.
4. Obtain and install a certificate covering the school hostname and configure renewal. The certificate for `*.euvion.com.ng` does not cover `academics.mudiameuniversity.edu.ng`. Finish certificate validation/issuance before enabling an HTTPS block referencing its certificate files.
5. Independently verify that the hostname resolves to the intended ingress and HTTPS validates for the correct name. Before institution registration, the application should reject the unknown host; that rejection is expected.
6. In the platform dashboard, open Institutions, select the school and find Primary portal domain.
7. Enter the hostname only: `academics.mudiameuniversity.edu.ng`. Do not include `https://`, a path, a port or a wildcard.
8. Enter the reason. Confirm that authorization, DNS and HTTPS were verified, then save. Password confirmation may be required.
9. The change is audited and the operator verification time is recorded. Existing school sessions, tokens and pending onboarding handoffs are revoked. The old hostname is removed from the registry.
10. Update the school's payment callback/webhook addresses, school links and any existing external integrations. Open the school from its new hostname and verify representative school records and its package permissions.

The readiness checkbox is the operator's attestation. The application validates hostname syntax, reserved central names and registry collisions; it does not perform an automated DNS-ownership challenge or provision DNS/TLS.

If DNS/TLS is not ready, leave the existing registered address in place. Public signup records the requested school-owned hostname as pending; the operator enables it after verification. IP addresses, URL paths, wildcard input, central hosts and names already assigned to another school are rejected. Do not assign a school-owned hostname without its owner's authorization.

## Production configuration

Use the production template and actual credentials, preserving APP_KEY and separate legacy/shared databases. Relevant values are:

```dotenv
APP_URL=https://euvion.com.ng
PLATFORM_URL=https://euvion.com.ng
CENTRAL_DOMAINS=euvion.com.ng,www.euvion.com.ng
INSTITUTION_BASE_DOMAIN=euvion.com.ng
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
```

`PLATFORM_URL` is the central origin, not a URL ending in `/dashboard`. Subscription callbacks still use `/subscribe/callback`. Do not add a school domain to `CENTRAL_DOMAINS` or `TENANCY_LEGACY_HOSTS`: doing so routes it to the wrong application context. Keep cookies host-only; cross-domain school entry uses the existing short-lived, one-use handoff rather than a shared authentication cookie.

The central Nginx template redirects `www.euvion.com.ng` to the canonical `euvion.com.ng` origin. Use that canonical origin for billing, payment callbacks and onboarding. Each independently owned school hostname needs its own virtual-host/certificate configuration; do not turn unknown-host routing into unrestricted school access.

## Local verification

The current development platform remains at `http://educore.test:8000/dashboard`, with sign-in at `/dashboard/login`. This avoids changing local credentials or claiming production domains are already deployed. Add any desired development hostname to the Windows hosts file with administrator privileges when testing through a browser.

Tests simulate HTTPS requests to `euvion.com.ng` and `academics.mudiameuniversity.edu.ng` without altering public DNS. They cover separate landing/login/dashboard routing, custom-host assignment and collision rejection, school-only authentication handoff, school dashboard widget access and central-platform denial on school hosts. Platform security tests exercise the new `/dashboard` paths.

Validation: six final domain/onboarding/lifecycle cases passed with 89 assertions; all twelve platform security cases passed with 153 assertions. Live local HTTP checks confirmed the new platform login, sidebar, overview and Plans page, the old-path redirect to `/dashboard`, and the public landing page's pricing entry point. PHP syntax, Blade compilation and targeted whitespace checks passed. Nginx configuration and public certificates still require validation on the actual hosting server.

No public DNS records, certificates or production web-server configuration were installed by this change. Existing local school registrations are not silently switched to an unprepared public hostname. Use the reviewed deployment process to activate real domains.

## Hosting references

- [Nginx server-name routing](https://nginx.org/en/docs/http/server_names.html).
- [Nginx HTTPS configuration and certificate-name coverage](https://nginx.org/en/docs/http/configuring_https_servers.html).
- [Let's Encrypt domain-control validation](https://letsencrypt.org/docs/challenge-types/).
