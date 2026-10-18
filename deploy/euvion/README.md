# Euvion deployment kit

For cPanel with automatic GitHub updates, start with docs/github-cpanel-deployment.md and deploy/cpanel. The Nginx/Supervisor examples below are for independently managed servers.

Target: euvion.com.ng for the landing page, euvion.com.ng/dashboard for platform administration, and registered institution hostnames such as academics.mudiameuniversity.edu.ng for school portals. Hosting is not provisioned.

See docs/domain-routing.md. The additional nginx-institution.conf.example template serves one independently authorized school-owned hostname from the same application release, with a certificate covering that hostname. School domains are not CENTRAL_DOMAINS. Configure DNS/TLS before assigning the school's portal address through Institutions. The application does not create public DNS records or issue certificates automatically.

This directory and its ZIP kit contain configuration examples and runbooks only. Deploy a reviewed application release separately; do not expose the repository, environment file, backups or school files as the web root. Replace all credentials and validate paths/socket/runtime requirements on the target server.

Read docs/euvion-launch-and-cutover.md, docs/multi-tenancy-operations.md and docs/multi-tenancy-launch-acceptance-2026-10-18.md. The first-school terms are in launch-plan.json. Source correction and unavailable-reference review are complete; carry the retained private evidence into migration. The missing original files have not been recovered. Public signup remains closed until first-school acceptance and real gateway tests pass.
