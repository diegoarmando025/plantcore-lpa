# Hostinger deployment from GitHub Actions — LPA

This is an **opt-in** deployment mechanism, not a configured connection to Hostinger. Nothing will deploy until the repository owner provisions SSH access and explicitly enables it.

## Deployment safeguards
- Trigger: manual `workflow_dispatch` from **main** only. Regular commits/PRs never deploy.
- Tests must pass and Vite is compiled on GitHub Actions (PHP 8.4, Node.js 22).
- Requires typed confirmation `DEPLOY LPA`, typed `BACKUP CONFIRMED`, and repository variable `HOSTINGER_DEPLOY_ENABLED=true`.
- GitHub environment `production` should have required reviewers and (where available) branch restriction to `main`. On plans that do not support required reviewers, preserve the confirmation gate and restrict repository access.
- SSH verifies the Hostinger server key against a **pinned known_hosts** secret; do not use `StrictHostKeyChecking=no`.
- Deploys exactly the tested commit; remote checkout must be clean on `main`, use fast-forward update and remain in the expected private application directory.
- Rejects changes in `database/migrations/`. No seeders, migrations, `migrate:fresh`, environment rewrite or global PHP change.
- Before modifying production, archives both Vite build folders and records the existing commit in a timestamped backup under private `storage/app/deploy-backups/`.
- Attempts to revert code and Vite assets if a deployment fails. **Rollback is not guaranteed** when dependencies/server access fail; check application health and logs manually.
- Site enters short maintenance mode during switch. There is no atomic deploy or automatic database backup.
- Does not modify the main website or the other Laravel application.

## One-time GitHub configuration

1. In Hostinger, provision a dedicated deployment SSH key in the SSH account that owns `apps/lpa`. Store the **private** key as repository Actions secret `HOSTINGER_SSH_PRIVATE_KEY`; add the public key to the server's authorized keys via Hostinger-supported SSH management. Do not commit the private key.
2. Add secret `HOSTINGER_SSH_HOST`: the SSH hostname shown in Hostinger. Add secret `HOSTINGER_SSH_USER`: the Hostinger SSH user.
3. Independently verify the server's host-key fingerprint through a trusted Hostinger channel and create Actions secret `HOSTINGER_SSH_KNOWN_HOSTS` containing the exact valid OpenSSH known_hosts line for the host/port. Do not blindly trust an unverified `ssh-keyscan` output.
4. Add Actions repository variable `HOSTINGER_SSH_PORT` with your assigned SSH port (Hostinger often uses 65002; verify your account). The known_hosts entry must match the hostname and port, e.g. `[host.example]:65002 ssh-ed25519 AAAA...`.
5. Create GitHub Actions environment `production`. Configure approval and protected deployment branches if your plan permits them.
6. Keep repository variable `HOSTINGER_DEPLOY_ENABLED` **unset** initially. After reviewing `scripts/hostinger-deploy.sh`, making and verifying a DB backup, testing the SSH key and reviewing the app health, explicitly set it to `true`.

**Secrets are repository-specific**, unless the owner deliberately uses a protected organizational secret. Configuring one repository does not automatically configure the other.

## Release

1. On Hostinger, export and verify a backup of the **dedicated lpa database**. The workflow cannot verify or create this DB backup automatically; typing the confirmation without one is unsafe.
2. Review GitHub Actions tests and all migrations in the release. This workflow aborts on modified migration files.
3. Run Actions → `Deploy to Hostinger (manual, guarded)` → Run workflow, branch `main`.
4. Enter exactly `DEPLOY LPA` and `BACKUP CONFIRMED`.
5. Approve the `production` environment if configured.
6. Check the Actions log for the deployed commit and backup path.
7. Smoke-test `https://lpa.devalejandre.com/demo`, `/login`, and protected routes; confirm both other sites remain available.

## Recovery

- On deployment failure, the script attempts to restore the old Git commit and the two Vite backups. **Do not assume success** without checking logs, the site and DB.
- The recovery archive and previous commit live under private `apps/lpa/storage/app/deploy-backups/...`.
- This procedure **does not undo database changes**. Any release requiring database migrations needs its own reviewed upgrade and recovery plan.
- If the site stays in maintenance mode, inspect the failure and restore first; then run the project's PHP 8.4 `artisan up` after it is safe.
- Never delete production `.env` or change `APP_ENV` to local to address a failed deployment.

## Important limits

This is a first guarded deployment pipeline for a shared-hosting account, not zero-downtime release infrastructure. It requires manual setup of SSH authentication, a verified database backup, an opt-in variable and host fingerprint; production rollout has **not** been executed or verified.
