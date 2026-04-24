# Package Architecture

This package should follow Laravel's package-development and service-provider patterns, with bindings registered in the container and package bootstrapping done through the package service provider.

## High-Level Architecture

```text
App Request / Job / Command
        |
        v
Tenant Resolver
        |
        v
Tenant Manager
  |        |         |         |
  v        v         v         v
Path   Connection  Provision  Migrator
Build   Manager     er        / Inspector / Backup
        |
        v
Laravel DB connection: tenant -> sqlite file path
```

## Main Layers

### 1. Central Metadata Layer

Use the application's main database for package metadata:

- tenant owner mapping
- tenant key
- tenant database path
- status
- schema version
- size bytes
- last migration time
- archival state

This layer is authoritative for discovery and operations.

### 2. Runtime Tenant Layer

At runtime, the package resolves a tenant context, computes the SQLite path, injects a `tenant` database connection into Laravel's database configuration, and reconnects the connection so queries use the correct file.

### 3. Filesystem Layer

The filesystem layer creates directories, creates SQLite files, checks writability, performs copy-based backups, and archives or purges database files.

### 4. Migration Layer

Tenant migrations live in a dedicated path, for example:

```text
database/migrations/tenant
```

Laravel supports connection-aware migrations and package migration publishing, so tenant schema operations should stay close to standard Laravel behavior.

## Package Tree

```text
src/
  Contracts/
    ResolvesTenant.php
    ActivatesTenantConnection.php
    ProvisionsTenantDatabase.php
    BuildsTenantDatabasePath.php
    InspectsTenantDatabase.php
    BacksUpTenantDatabase.php
  Support/
    TenantContext.php
    TenantDatabaseConfig.php
    ProvisionResult.php
    InspectionResult.php
  Services/
    TenantManager.php
    TenantResolverManager.php
    TenantConnectionManager.php
    TenantDatabaseProvisioner.php
    TenantDatabaseMigrator.php
    TenantDatabaseInspector.php
    TenantDatabaseBackupManager.php
    TenantPragmaConfigurator.php
  Resolvers/
    UserTenantResolver.php
  Concerns/
    UsesTenantConnection.php
  Http/Middleware/
    ActivateTenantDatabase.php
  Console/Commands/
    InstallCommand.php
    CreateTenantDatabaseCommand.php
    MigrateTenantDatabasesCommand.php
    InspectTenantDatabaseCommand.php
    BackupTenantDatabaseCommand.php
    ArchiveTenantDatabaseCommand.php
    PurgeTenantDatabaseCommand.php
    DoctorCommand.php
  Events/
    TenantDatabaseCreated.php
    TenantDatabaseActivated.php
    TenantDatabaseMigrated.php
    TenantDatabaseArchived.php
    TenantDatabasePurged.php
  Exceptions/
    TenantDatabaseException.php
    TenantNotResolved.php
    TenantActivationFailed.php
    TenantProvisioningFailed.php
    TenantMigrationFailed.php
  Facades/
    TenantDatabase.php
  TenantDatabaseServiceProvider.php
config/
  tenant-database.php
database/
  migrations/
```

## Core Services

### TenantManager

This is the orchestration service and the main public entry point behind the facade.

Responsibilities:

- resolve tenant identity
- get metadata record
- provision tenant DB if needed
- activate connection
- expose current tenant context
- run scoped callbacks
- delegate migrate, inspect, backup, archive, and purge operations

### TenantConnectionManager

Responsibilities:

- build runtime DB config for the tenant SQLite file
- write config to `database.connections.tenant`
- purge and reconnect the named connection
- optionally restore previous connection state after scoped execution

Laravel supports multiple database connections, so this service should only adapt standard connection behavior, not invent a parallel ORM layer.

### TenantDatabaseProvisioner

Responsibilities:

- create tenant directory
- create empty SQLite file
- apply initial PRAGMA configuration
- bootstrap schema from schema dump or migrations
- update central metadata
- emit `TenantDatabaseCreated`

### TenantDatabaseMigrator

Responsibilities:

- run tenant migration paths against the active tenant connection
- support `--fresh`, `--seed`, and targeted tenant execution
- record migration status in metadata
- emit `TenantDatabaseMigrated`

Laravel documents migrations and schema dumping for database bootstrapping, so the package should use those capabilities directly.

### TenantDatabaseInspector

Responsibilities:

- inspect file size and modified time
- list tables
- check migration status
- report writability and path existence
- optionally report SQLite PRAGMA values

### TenantDatabaseBackupManager

Responsibilities:

- create copy-based backup artifacts
- restore from backup
- verify backup exists and is readable
- archive old files

## Request Lifecycle

### HTTP Flow

1. Request enters app.
2. Optional middleware resolves tenant from authenticated user.
3. Middleware activates tenant connection.
4. Tenant-bound models use the `tenant` connection.
5. Response completes.
6. Middleware clears active tenant context.

### Queue Flow

1. Job carries tenant key or owner ID.
2. Job middleware or job handle method activates tenant explicitly.
3. Job runs domain logic.
4. Job clears tenant context.

### CLI Flow

1. Command resolves target tenant(s).
2. Command activates each tenant in sequence.
3. Command performs action.
4. Command records results and failures.

## Service Provider Design

Laravel recommends keeping service bindings in `register()` and runtime bootstrapping such as publishing config, commands, or routes in `boot()`, so the package service provider should follow that structure.

### register()

- merge package config
- bind contracts to implementations
- register singleton `TenantManager`
- register facade accessor target

### boot()

- publish config
- publish central metadata migration(s)
- load or publish package migrations
- register commands
- optionally register middleware alias
- optionally contribute package info to the `about` command

## State Model

The metadata record should support states such as:

- `pending`
- `active`
- `locked`
- `archived`
- `purged`
- `failed`

State transitions should be explicit and evented.

## Events

Emit events for extension points:

- `TenantDatabaseCreated`
- `TenantDatabaseActivated`
- `TenantDatabaseMigrated`
- `TenantDatabaseBackedUp`
- `TenantDatabaseArchived`
- `TenantDatabasePurged`
- `TenantDatabaseFailed`

These events let app developers hook provisioning, billing, notifications, or auditing into the package without patching core services.