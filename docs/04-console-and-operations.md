# Console Commands and Operations

Laravel packages can register Artisan commands through the package service provider, so operational tooling should ship with the package rather than being left to the host application.

## Commands

### Install

```bash
php artisan tenant-database:install
```

Responsibilities:

- publish config
- publish metadata migration
- create `database/migrations/tenant` if missing
- optionally publish a tenant migration stub
- print next steps

### Create

```bash
php artisan tenant-database:create {tenant}
```

Responsibilities:

- resolve tenant
- create metadata if missing
- provision SQLite file
- bootstrap schema
- print resulting path and status

### Migrate

```bash
php artisan tenant-database:migrate {--tenant=*} {--fresh} {--seed}
```

Responsibilities:

- resolve one or many tenants
- activate each tenant in sequence
- run tenant migrations on the configured tenant connection
- report success and failures

Tenant migrations should use Laravel migration mechanisms rather than custom SQL runners.

### Inspect

```bash
php artisan tenant-database:inspect {tenant}
```

Responsibilities:

- print tenant key
- print status
- print file path
- print size
- print writability
- print tables
- print schema version

### Backup

```bash
php artisan tenant-database:backup {tenant} {--name=} {--compress}
```

Responsibilities:

- create backup artifact
- store path or disk key
- optionally compress
- return location and checksum if implemented

### Archive

```bash
php artisan tenant-database:archive {tenant}
```

Responsibilities:

- move DB file to archive location or mark as archived
- update metadata state
- prevent accidental reactivation unless explicitly restored

### Purge

```bash
php artisan tenant-database:purge {tenant} {--force}
```

Responsibilities:

- require explicit confirmation
- delete DB file and optional archive
- mark metadata as purged or delete record depending on config

### Doctor

```bash
php artisan tenant-database:doctor
```

Responsibilities:

- verify SQLite driver availability
- verify base path exists and is writable
- verify migration paths exist
- verify published config is valid
- verify package tables exist
- optionally verify current bootstrap strategy assets exist

## Command Output Conventions

- Use clear status lines.
- Support JSON output later if desired.
- Return non-zero exit codes on failure.
- Summarize partial failures in batch operations.

## Backup Strategy

For v1, use simple file-copy backups because each tenant database is already isolated as one SQLite file. That makes backups, archive, and restore much easier than row-level export pipelines.

### Default backup location

```text
storage/app/tenant-backups/{tenant_key}/{timestamp}-tenant.sqlite
```

### Restore flow

1. verify target tenant exists
2. verify backup file exists
3. deactivate tenant if active
4. replace current file with backup copy
5. reactivate and optionally inspect

## Operational Concerns

### Migration fan-out

If many tenants exist, full fan-out migrations may be slow. Start with sequential execution and add chunking or queued orchestration later.

### Locked or archived tenants

Commands that write should fail fast for locked or archived tenant states unless an override is explicitly supported.

### Error logging

Every failed create, migrate, backup, archive, or purge action should:

- log the tenant key
- log the action name
- log the exception class and message
- update metadata status if relevant

## Suggested About Output

Contribute to Laravel's `about` command with:

- package version
- connection name
- base path
- resolver class
- bootstrap strategy
- number of tenant metadata records

Laravel packages can integrate with framework conventions like service providers and boot-time registration, so this kind of visibility fits the ecosystem well.