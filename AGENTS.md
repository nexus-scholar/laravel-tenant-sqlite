# AGENTS Guide

## Current Repo State
- Implementation is well underway. The core architecture, service layer, and foundational commands are complete.
- Core flow (Resolver -> TenantManager -> ...) is implemented and verified with tests.
- Operational commands (create, migrate, inspect, backup, archive, purge, doctor, install) are available.
- Lifecycle events are dispatched during all major operations.
- HTTP and Queue middleware are provided for easy integration.

## Big Picture Architecture
- Package goal: one isolated SQLite database file per tenant, with metadata stored in the app's main DB.
- Core flow (`docs/01-package-architecture.md`): Resolver -> `TenantManager` -> path builder / provisioner / connection manager / migrator / inspector / backup.
- Runtime model: activate dynamic Laravel connection named `tenant` pointing to the resolved SQLite path.
- Keep layers separate:
  - app DB metadata (`tenant_databases` table, `TenantDatabaseRecord`)
  - tenant runtime DB operations (tenant-scoped models)
  - filesystem operations (create/copy/archive/purge SQLite files)

## Public API Boundaries
- Stable surface is facade + contracts (`docs/02-public-api.md`).
- Implement facade methods exactly as documented (`provision`, `activate`, `run`, `migrate`, `inspect`, etc.).
- Use value objects for non-trivial results (`ProvisionResult`, `InspectionResult`, etc.), not loose arrays.
- `Contracts/` are extension points; internal services can evolve.

## Project-Specific Conventions
- Keep orchestration in `TenantManager`; keep service classes small and single-purpose (`docs/05-implementation-plan.md`).
- Normalize tenant inputs early (user/model/key/id/context) in resolver layer.
- Build DB paths only via a path builder service; do not assemble paths ad hoc.
- Use opaque tenant keys (e.g., ULID) in filesystem paths; enforce traversal safety.
- Apply SQLite pragmas from config (`foreign_keys`, `journal_mode`, `synchronous`, `busy_timeout`).
- Never mix app metadata models and tenant-domain models.

## Operational Workflows
- Commands expected (`docs/04-console-and-operations.md`): `tenant-database:install|create|migrate|inspect|backup|archive|purge|doctor`.
- Batch migrations run sequentially first; fail clearly and summarize partial failures.
- Destructive operations (`purge`) require explicit `--force` safeguards.
- Backup strategy is copy-based file backup per tenant SQLite file.

## Testing Workflow (Pest + Testbench)
- Follow `docs/06-testing-with-pest.md` for baseline test setup.
- Central app DB in tests: in-memory SQLite.
- Tenant DBs in tests: physical temporary `.sqlite` files (required for isolation realism).
- Ensure strict cleanup of generated tenant DB files in `tearDown`.
- Prioritize isolation tests: two tenants must never see each other's data.

## Implementation Order (Recommended)
- Build in phases from `docs/05-implementation-plan.md`.
- First vertical slice: provider + config + metadata migration + resolver + path builder + provisioner + activation + `UsesTenantConnection` + one isolation integration test.

## Integration Points
- Laravel service provider lifecycle: bindings in `register()`, publishing/commands in `boot()`.
- Optional integrations: HTTP middleware (`ActivateTenantDatabase`), queue middleware/trait, and package events (`TenantDatabaseCreated`, `TenantDatabaseMigrated`, etc.).
- Favor Laravel-native features (migrations, multiple connections, schema dumps) over custom infrastructure.

