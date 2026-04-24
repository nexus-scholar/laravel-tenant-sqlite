# Config and Data Model

The package should publish a small, explicit config file and a central metadata migration. Laravel package development supports publishing config and migrations, which fits this package well.

## Published Config

Suggested file:

```php
// config/tenant-database.php
return [
    'connection_name' => 'tenant',
    'base_path' => storage_path('app/tenant-databases'),
    'database_filename' => 'tenant.sqlite',

    'resolver' => Vendor\\Package\\Resolvers\\UserTenantResolver::class,
    'path_builder' => Vendor\\Package\\Support\\DefaultPathBuilder::class,

    'tenant_migration_paths' => [
        database_path('migrations/tenant'),
    ],

    'auto_provision' => true,
    'auto_activate_middleware' => false,

    'bootstrap' => [
        'strategy' => 'migrate', // migrate|schema-dump|template-copy
        'schema_dump_path' => database_path('schema/tenant-schema.sqlite'),
        'template_database' => null,
    ],

    'pragmas' => [
        'foreign_keys' => true,
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'busy_timeout' => 5000,
    ],

    'backup' => [
        'disk' => null,
        'directory' => 'tenant-backups',
        'compress' => false,
    ],
];
```

## Why These Config Options Exist

- `connection_name`: keeps the package consistent across traits, services, and commands.
- `base_path`: avoids public storage exposure.
- `resolver`: makes tenant source pluggable.
- `tenant_migration_paths`: keeps tenant schema separate from the main app schema.
- `bootstrap.strategy`: allows fast tenant creation using migrations, schema dumps, or a template file.
- `pragmas`: centralizes safe SQLite defaults.

## Metadata Table

Suggested table name:

```text
tenant_databases
```

Suggested columns:

| Column | Type | Notes |
|---|---|---|
| id | bigint / ulid | Primary key |
| tenant_type | string nullable | For future polymorphic ownership |
| tenant_id | string | Owner ID or key |
| tenant_key | string unique | Filesystem-safe key such as ULID |
| driver | string | Default `sqlite` |
| database_path | string | Absolute or app-relative path |
| status | string | `pending`, `active`, `locked`, `archived`, `purged`, `failed` |
| schema_version | string nullable | Optional app-level schema version |
| size_bytes | unsigned bigint nullable | Cached for quick inspection |
| last_migrated_at | timestamp nullable | Last successful tenant migration |
| archived_at | timestamp nullable | Archive marker |
| purged_at | timestamp nullable | Purge marker |
| meta | json nullable | Extra package-specific info |
| created_at | timestamp | Standard |
| updated_at | timestamp | Standard |

## Metadata Model

Suggested Eloquent model:

```php
TenantDatabaseRecord
```

Properties:

- stays on the app's default connection
- never uses the tenant connection
- is used by resolver, provisioner, and operational commands

## TenantContext Value Object

```php
final class TenantContext
{
    public function __construct(
        public readonly string $tenantKey,
        public readonly string $ownerType,
        public readonly string|int $ownerId,
        public readonly string $databasePath,
        public readonly string $connectionName = 'tenant',
    ) {}
}
```

The `TenantContext` should be the internal currency of the package. Services should prefer receiving a `TenantContext` over raw models or IDs after the initial resolution step.

## Filesystem Layout

Default layout:

```text
storage/
  app/
    tenant-databases/
      01JTK7F1G7M9M4Z0Q8M3JH4Y6R/
        tenant.sqlite
```

Optional layout with sidecar metadata:

```text
storage/
  app/
    tenant-databases/
      01JTK7F1G7M9M4Z0Q8M3JH4Y6R/
        tenant.sqlite
        meta.json
        backups/
```

The database path should always be derived by a path builder service rather than assembled ad hoc in multiple places.

## SQLite PRAGMA Defaults

Recommended defaults for tenant databases:

- `foreign_keys = ON`
- `journal_mode = WAL`
- `synchronous = NORMAL`
- `busy_timeout = 5000`

These should be applied immediately after database creation and optionally re-applied at activation time if needed.

## Bootstrap Strategies

### Strategy 1: migrate

- create file
- activate tenant connection
- run all tenant migrations

Pros: simplest and fully Laravel-native.

### Strategy 2: schema-dump

- copy or load a prepared SQLite schema dump
- run only newer incremental migrations

Laravel supports schema dumping, so this is the best optimization path once migration replay becomes expensive.

### Strategy 3: template-copy

- copy a known-good template SQLite file
- patch metadata if needed
- run outstanding migrations

Fastest in many cases, but requires extra discipline around template freshness.

## Model Placement Rules

### App database models

Keep these on the main app connection:

- `User`
- `Subscription`
- `Plan`
- `Role`
- `Permission`
- `TenantDatabaseRecord`
- billing and audit models

### Tenant database models

Use `UsesTenantConnection` for these:

- `Project`
- `Note`
- `Invoice`
- `Order`
- `Document`
- tenant-scoped domain models

## Security Rules

- Never expose raw DB paths in public API responses.
- Never store tenant DBs in public web root.
- Prefer opaque tenant keys over raw numeric IDs in filesystem paths.
- Validate path traversal safety in the path builder.
- Require explicit `--force` for destructive purge operations.