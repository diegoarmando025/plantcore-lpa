# DevAlejandre LPA

A Laravel application for Layered Process Audits (LPA), designed around fictional manufacturing workflows. It demonstrates audit planning, configurable checklists, execution, corrective actions, role-scoped access, and operational reporting.

## Interactive evaluation

[Open DevAlejandre LPA](https://lpa.devalejandre.com/login) and select **Entrar al demo interactivo**.

The live demo supports scheduling and completing audits, recording findings, monitoring corrective actions, browsing the calendar, and exporting reports. Visitors use shared fictional records belonging to a dedicated demo plant. Changes may remain visible to other visitors until the scenario is restored. Do not enter personal, confidential, or customer information.

[Reviewer walkthrough](docs/INTERACTIVE_PORTFOLIO_DEMO.md) · [Architecture](docs/ARCHITECTURE.md)

## Implemented workflows

- Individual and batch audit scheduling, with daily or weekly frequencies and duplicate detection
- Reusable checklists across five audit levels
- Checklist version safety: templates used in scheduled audits cannot have their questions silently rewritten
- Monthly calendar and audit execution with pass, fail, and not-applicable answers
- Automatic corrective actions for failed checks
- Corrective-action ownership, target dates, overdue tracking, and closure
- Compliance indicators, audit trends, level and owner summaries, and CSV exports
- Role-based access for viewers, auditors, and supervisors, scoped to assigned plants
- Public read-only sample scenarios and a separate interactive fictional dataset
- Automated tests for workflow behavior, reporting, permissions, and demo isolation

## Technology

- PHP 8.4 and Laravel 13
- Blade, Bootstrap 5, AdminLTE 4, Vite
- SQLite for local development; MySQL 8 verification in CI
- PHPUnit and GitHub Actions

## Architecture and permissions

Audit records belong to plants. Users access plants through explicit memberships, and each membership defines a role.

| Role | Read audits | Execute assigned audits | Manage templates and actions |
| --- | --- | --- | --- |
| Viewer | Yes | No | No |
| Auditor | Yes | Yes | No |
| Supervisor | Yes | Yes | Yes |

The application uses Laravel controllers, Eloquent models, Blade views, database transactions, and permission checks at the plant boundary. Audit completion uses a locked database record to prevent duplicate execution and revalidates permissions before writing answers and corrective actions.

The current implementation demonstrates plant-scoped audit workflows with role-based access, transaction-safe completion and corrective-action tracking. Organization-specific deployment requirements can be evaluated separately.

## Local setup

Use a disposable development database. Do not copy real operational data or credentials into the project.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=LpaPortfolioSeeder
npm ci
npm run build
php artisan test
php artisan serve
```

For a SQLite environment, configure `DB_CONNECTION=sqlite` and create `database/database.sqlite` before migrating. On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

The local seeder creates fictional templates and audits, including a completed audit with a corrective action and pending work. It is intended for development and test environments; never run destructive migration commands against important databases.

For manual local login, assign a private password to the sample account using Laravel Tinker. Credentials are not committed to Git.

## Key routes

| Area | Route |
| --- | --- |
| Dashboard | `/audits` |
| Batch planning | `/audits/batch` |
| Calendar | `/calendar` |
| Checklists | `/templates` |
| Corrective actions | `/actions` |
| Audit reporting | `/reports` |
| Trends | `/reports/trends` |
| CSV exports | `/exports/audits`, `/exports/actions`, `/exports/summary` |

## Verification

```bash
php artisan test
npm run build
```

GitHub Actions runs the automated test suite and verifies the built assets on pull requests. The test coverage includes multi-plant isolation, role restrictions, audit and corrective-action workflows, reporting, and sample data restoration.

## Related projects

- [DevAlejandre Traceability](https://github.com/diegoarmando025/industrial-traceability-api)
- [DevAlejandre Service Desk](https://github.com/diegoarmando025/plantcore-service-desk)

## Scope

All examples are fictional. The project does not contain customer-specific rules, operational credentials, or proprietary source code. A production implementation would need environment-specific security, backups, monitoring, and operational review.
