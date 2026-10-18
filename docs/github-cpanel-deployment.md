# GitHub to cPanel: first install and automatic updates

Repository: https://github.com/IsGameliel/educore. Production branch: main. After initial server setup, a push to main triggers the GitHub Actions workflow, which connects over SSH, pulls the exact commit on cPanel and builds a new release. `.env`, uploads, logs and sessions remain server-local. The current release changes only after dependency installation, frontend build and schema-status checks succeed. A failed build leaves the previous release selected. No production deployment has occurred just by adding these files.

## 1. Hosting requirements

Use [the cPanel installation guide](cpanel-deployment.md) sections 1, 3 and 4 for PHP/extensions, private paths, two empty databases, runtime/migration privileges, SMTP and Paystack TEST configuration. The automatic release build needs Composer, Node/npm, Bash, Git, tar, flock and symlinks. Confirm these with the provider. All web requests must target the public directory, as required by [Laravel deployment](https://laravel.com/docs/12.x/deployment).

For the Git workflow, use this layout instead of the single upload directory:

```text
/home/CPANELUSER/repos/educore/           private Git control checkout
/home/CPANELUSER/euvion/shared/.env      private production settings
/home/CPANELUSER/euvion/shared/storage/  durable uploads, sessions and logs
/home/CPANELUSER/euvion/releases/SHA/    built releases
/home/CPANELUSER/euvion/current         symlink to the active release
```

The document root of euvion.com.ng and every approved institution hostname is `/home/CPANELUSER/euvion/current/public`. Ask the host to allow this root, especially if euvion.com.ng is the cPanel primary domain. Do not put the control repository or shared directory in public_html. If the host forbids symlinked roots or external domain roots, resolve that with the provider before enabling this workflow.

## 2. Give cPanel read access to GitHub

In cPanel Terminal, generate a dedicated repository-read key:

```sh
mkdir -p ~/.ssh
chmod 700 ~/.ssh
ssh-keygen -t ed25519 -f ~/.ssh/euvion_github_read -C euvion-cpanel-readonly
```

For unattended use choose no passphrase for this dedicated key. Keep its private half on hosting only. Add the public `.pub` file in GitHub repository Settings > Deploy keys, leaving write access disabled. Add an SSH config entry:

```text
Host github-euvion
    HostName github.com
    User git
    IdentityFile ~/.ssh/euvion_github_read
    IdentitiesOnly yes
```

Verify GitHub's server fingerprint against [GitHub's published fingerprints](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/githubs-ssh-key-fingerprints) before accepting the host. Then clone:

```sh
mkdir -p ~/repos
git clone --branch main git@github-euvion:IsGameliel/educore.git ~/repos/educore
```

You can instead create this clone using cPanel Git Version Control with the same private repository access. Keep the control checkout clean: no edits, .env or generated assets there. [GitHub deploy keys](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/managing-deploy-keys) describe repository-scoped access.

## 3. Prepare the first release

From the cloned repository:

```sh
mkdir -p ~/.config/euvion ~/euvion/shared
cp deploy/cpanel/deploy.env.example ~/.config/euvion/deploy.env
cp deploy/cpanel/.env.example ~/euvion/shared/.env
chmod 600 ~/.config/euvion/deploy.env ~/euvion/shared/.env
```

Privately edit both files with actual paths and credentials. Set APP_ROOT to your absolute account path; set PHP_BIN, COMPOSER_BIN and NPM_BIN to working executables. Composer must run under the same compatible PHP CLI version. For hosts where the composer launcher uses a different PHP, create a private executable wrapper that runs the verified PHP binary with the actual composer.phar. The private deploy configuration contains paths only; do not store migration passwords there.

The shared .env uses production mode, euvion.com.ng URLs, two distinct runtime databases and the test payment/SMTP settings described in the installation guide. Leave APP_KEY empty initially. Then:

```sh
cd ~/repos/educore
SHA=$(git rev-parse HEAD)
bash deploy/cpanel/deploy.sh "$SHA" --prepare
cd ~/euvion/releases/"$SHA"
```

Use your verified PHP binary for all commands that follow. Generate a new APP_KEY only for this fresh installation; it is written through the release's .env link to shared/.env:

```sh
php artisan key:generate --force
php artisan config:clear
```

Follow cpanel-deployment.md section 5 to create the support schema and run `tenancy:bootstrap --empty-database` with temporary migration credentials and your own owner email/password. This is the one-time fresh install; do not import local school data. Migration/owner passwords are entered privately in Terminal, never in GitHub or chat. After bootstrap, while still in this release, clear any config cache created manually before the initial support migration. Return to the control checkout and activate:

```sh
cd ~/repos/educore
bash deploy/cpanel/deploy.sh "$SHA"
```

Point the web document roots at current/public, configure DNS and HTTPS, then sign in at https://euvion.com.ng/dashboard/login. Follow installation-guide sections 6–8 to connect two school-owned hostnames, enable test plans, verify Paystack test checkout, complete branding, approve each hostname and test isolated portals. For cron.example use `/home/CPANELUSER/euvion/current` as the application directory. Never run cron/workers from the control checkout.

## 4. Give GitHub Actions SSH access to hosting

Use a SECOND dedicated SSH key for Actions-to-cPanel. It is separate from the cPanel-to-GitHub read key. Authorize its public half through cPanel SSH Access (or the host-approved authorized_keys workflow). Store its private half only in GitHub Actions secrets, never commit it or send it in chat. Confirm login works in batch mode and the host allows GitHub-hosted runners to reach its SSH port.

In repository Settings > Secrets and variables > Actions, create:

| Type | Name | Value |
| --- | --- | --- |
| Variable | CPANEL_HOST | Actual SSH server hostname or IP |
| Variable | CPANEL_USER | Hosting username |
| Variable | CPANEL_PORT | Actual SSH port, normally 22 |
| Variable | CPANEL_REPO | `/home/CPANELUSER/repos/educore` |
| Variable | CPANEL_AUTO_DEPLOY | `true`, only after the initial installation and SSH test succeed |
| Secret | CPANEL_SSH_PRIVATE_KEY | Entire dedicated Actions-to-hosting private key |
| Secret | CPANEL_SSH_KNOWN_HOSTS | Verified OpenSSH known_hosts line for this hosting server and port |

Obtain the SSH host fingerprint through the provider or another trusted channel. A blind ssh-keyscan is insufficient verification; match its result to the trusted fingerprint before saving the known_hosts line. Nonstandard ports use the `[hostname]:port` known_hosts form. The workflow enforces host checking and does not use password login.

Once enabled, use Actions > Deploy to cPanel > Run workflow on main for the first automatic test. Subsequent pushes/merges to main deploy automatically. Protect main so only reviewed changes can deploy. No cPanel password is needed by the workflow. See [GitHub Actions secrets](https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets).

The optional `.cpanel.yml` runs the same release script for cPanel's manual Update from Remote/Deploy HEAD workflow. cPanel's pull interface alone does not automatically watch GitHub; the Actions SSH workflow supplies that trigger. Use one automatic trigger. [cPanel Git deployment](https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/) explains its native modes.

## 5. Updates, database changes and recovery

Normal code-only updates: push to main, watch the Actions log, then check the main website, platform login, both school portals and cron logs. No FTP upload is needed. Changes on feature branches do not deploy until merged into main. Keep local/GitHub secrets out of commits. Existing school records and files remain in the shared database/storage.

Pending migrations deliberately stop activation. Rehearse the new migrations on a disposable copy, back up shared SQL and files, pause web writers and cron/workers, and follow the existing tenancy:upgrade operator process with dedicated CLI credentials. Update the support schema separately if it also has pending migrations. Use the prepared new release's code and clear its config cache before temporary default-database credential overrides. Restore runtime config afterward, rerun deploy.sh for the same SHA, and resume writers only after verification. Never use migrate:fresh or drop a database to fix a deployment.

Failed builds leave the old current symlink selected. Incomplete release directories are retained for inspection and must be reviewed before retrying; the script refuses to overwrite them. Prior releases are retained and need a planned retention policy. A code rollback is only safe with a compatible schema; do not automatically rewind databases or payments. After any new writes, preserve data and use the reviewed recovery procedure.

Do not edit code under current or releases; make changes locally and push to GitHub. Back up shared/.env, shared/storage and both databases securely. The Git repository is source control, not a school-data backup. Hosting must validate PHP OPcache behaviour across release switches and support the required worker runtimes before real users are invited.
