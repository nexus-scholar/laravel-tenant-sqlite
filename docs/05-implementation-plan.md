# Implementation Plan for Codex CLI

This document converts the design into a build plan with concrete milestones, task lists, and acceptance criteria.

## Phase 1: Package Skeleton

### Tasks

- create Composer package structure
- add package discovery entries
- create `TenantDatabaseServiceProvider`
- add config file and config publishing
- add metadata migration publishing
- add facade and root manager binding

### Acceptance Criteria

- package installs into a Laravel 13 app
- config publishes successfully
- metadata migration publishes successfully
- service provider auto-discovers successfully
- facade resolves successfully

Laravel documents package discovery, package development, and service-provider structure, so this phase should follow standard framework patterns.

## Phase 2: Metadata and Resolution

### Tasks

- create `tenant_databases` migration
- create `TenantDatabaseRecord` model
- implement `TenantContext`
- implement `ResolvesTenant` contract
- ship `UserTenantResolver`
- implement `DefaultPathBuilder`

### Acceptance Criteria

- a user can be resolved into a `TenantContext`
- a stable tenant path is generated
- metadata records can be created and loaded
- path builder prevents invalid traversal input

## Phase 3: Provisioning and Activation

### Tasks

- implement `TenantDatabaseProvisioner`
- create directory and SQLite file
- apply PRAGMAs
- implement `TenantConnectionManager`
- support `activate`, `deactivate`, and `run`
- emit creation and activation events

### Acceptance Criteria

- `TenantDatabase::provision($user)` creates the expected file
- `TenantDatabase::activate($user)` points Laravel's tenant connection to that file
- tenant models can read and write through the tenant connection
- `run()` restores context after callback success or failure

Laravel supports multiple connections and SQLite path-based configuration, so activation should be implemented as dynamic connection configuration rather than a custom DB abstraction.

## Phase 4: Tenant Migrations

### Tasks

- implement `TenantDatabaseMigrator`
- add tenant migration path support
- support fresh and seed options
- record migration status in metadata
- emit migration event

### Acceptance Criteria

- tenant migrations run only against the tenant connection
- app migrations do not accidentally run against tenant DBs
- migration failures are surfaced clearly
- metadata updates after successful migration

Laravel's migration system should remain the engine for this phase.

## Phase 5: Public API and Middleware

### Tasks

- finalize facade methods
- finalize result objects
- add `UsesTenantConnection` trait
- add optional `ActivateTenantDatabase` middleware
- add queue job middleware helper

### Acceptance Criteria

- controller usage is ergonomic
- facade and DI usage both work
- tenant models need only one trait to target the tenant DB
- middleware activates and cleans up correctly

## Phase 6: Commands and Operations

### Tasks

- implement install, create, migrate, inspect, backup, archive, purge, and doctor commands
- add helpful command output
- add archive and purge safeguards
- implement backup manager

### Acceptance Criteria

- all commands run end-to-end in a test app
- batch migrate handles multiple tenants
- purge requires explicit confirmation or force flag
- backup produces a real artifact

## Phase 7: Tests

### Test Categories

#### Unit tests

- resolver behavior
- path builder safety
- metadata transitions
- result object construction

#### Integration tests

- provision creates actual SQLite file
- activate switches tenant connection correctly
- tenant model reads and writes to tenant DB
- migration command migrates target tenant(s)
- backup and restore flows work

#### Failure tests

- invalid tenant input
- missing path permissions
- migration failure
- archive then write attempt
- purge without force

## Suggested Initial Test Matrix

| Scenario | Expected Result |
|---|---|
| New user provision | DB file created, metadata active |
| Activation | `tenant` connection points to correct file |
| Tenant model insert | Row written to tenant DB, not app DB |
| Two users | Each reads only its own tenant data |
| Batch migrate | All target tenants migrated, failures reported |
| Archive | State changes, writes blocked |
| Purge | File deleted only with confirmation |

## Coding Rules for Codex CLI

- treat `Contracts/` as public extension points
- keep orchestration in `TenantManager`
- keep services small and focused
- avoid static global state beyond the facade accessor
- prefer value objects over untyped arrays
- prefer events over hidden side effects
- avoid embedding path logic in multiple classes
- do not mix app metadata models with tenant models

## First Slice Recommendation

Implement this vertical slice first:

1. service provider
2. config publishing
3. metadata migration
4. user resolver
5. path builder
6. provisioner
7. connection activation
8. `UsesTenantConnection` trait
9. one integration test proving isolation across two users

That slice proves the package's core promise before adding operational features.