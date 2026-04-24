# Public API Specification

The public API should be small, expressive, and tenant-centric. Laravel packages typically expose services through the container and may also expose a facade alias through package discovery, so this package should support both dependency injection and facade usage.

## Design Rules

- Prefer tenant-focused method names over filesystem-focused names.
- Keep one-shot methods for common tasks.
- Provide a scoped execution helper for safe context activation and cleanup.
- Return structured result objects for non-trivial operations.
- Throw package exceptions for fatal workflow failures.

## Facade Surface

```php
TenantDatabase::provision($tenant);
TenantDatabase::activate($tenant);
TenantDatabase::deactivate();
TenantDatabase::migrate($tenant, fresh: false, seed: false);
TenantDatabase::inspect($tenant);
TenantDatabase::backup($tenant);
TenantDatabase::archive($tenant);
TenantDatabase::purge($tenant, force: false);
TenantDatabase::path($tenant);
TenantDatabase::exists($tenant);
TenantDatabase::current();
TenantDatabase::run($tenant, fn () => Project::query()->count());
```

## Fluent API

Support a fluent builder for readability:

```php
TenantDatabase::for($user)->activate();
TenantDatabase::for($user)->provision();
TenantDatabase::for($user)->migrate();
TenantDatabase::for($user)->inspect();
TenantDatabase::for($user)->backup();
TenantDatabase::for($user)->archive();
TenantDatabase::for($user)->purge();
```

## Service Contract

```php
interface TenantDatabaseManager
{
    public function provision(mixed $tenant): ProvisionResult;
    public function activate(mixed $tenant): TenantContext;
    public function deactivate(): void;
    public function migrate(mixed $tenant, bool $fresh = false, bool $seed = false): MigrationResult;
    public function inspect(mixed $tenant): InspectionResult;
    public function backup(mixed $tenant, ?string $name = null): BackupResult;
    public function archive(mixed $tenant): ArchiveResult;
    public function purge(mixed $tenant, bool $force = false): PurgeResult;
    public function path(mixed $tenant): string;
    public function exists(mixed $tenant): bool;
    public function current(): ?TenantContext;
    public function run(mixed $tenant, callable $callback): mixed;
    public function for(mixed $tenant): TenantDatabaseScope;
}
```

## Supported Tenant Inputs

The API should accept:

- authenticatable user model
- tenant metadata model
- tenant key string
- integer owner ID when configured
- `TenantContext`

Normalization should happen early inside the resolver layer.

## Result Objects

Return value objects instead of loose arrays.

### ProvisionResult

```php
final class ProvisionResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly string $path,
        public readonly bool $created,
        public readonly bool $bootstrapped,
        public readonly ?string $schemaVersion,
    ) {}
}
```

### InspectionResult

```php
final class InspectionResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly string $path,
        public readonly bool $exists,
        public readonly bool $writable,
        public readonly int $sizeBytes,
        public readonly array $tables,
        public readonly ?string $schemaVersion,
        public readonly array $pragmas,
    ) {}
}
```

## Trait API

Tenant-bound Eloquent models should use a trait rather than repeating `$connection` overrides manually.

```php
trait UsesTenantConnection
{
    public function getConnectionName(): ?string
    {
        return config('tenant-database.connection_name', 'tenant');
    }
}
```

Laravel models support specifying connection names, so the trait simply standardizes that behavior for package consumers.

## Middleware API

Optional middleware:

```php
ActivateTenantDatabase::class
```

Behavior:

- resolve tenant from authenticated user by default
- optionally auto-provision missing DB for first use
- activate tenant connection for request duration
- clean up context on terminate

## Queue Helpers

Provide one of these patterns:

### Option A: Trait

```php
trait InteractsWithTenantDatabase
{
    public string $tenantKey;
}
```

### Option B: Job Middleware

```php
class ActivateTenantForJob
{
    public function handle($job, $next)
    {
        TenantDatabase::activate($job->tenantKey);

        try {
            $next($job);
        } finally {
            TenantDatabase::deactivate();
        }
    }
}
```

## Exceptions

Use explicit package exceptions:

- `TenantNotResolved`
- `TenantDatabaseNotFound`
- `TenantActivationFailed`
- `TenantProvisioningFailed`
- `TenantMigrationFailed`
- `TenantDatabaseLocked`
- `TenantDatabaseAlreadyArchived`

## Usage Examples

### Provision on signup

```php
User::created(function (User $user) {
    TenantDatabase::provision($user);
});
```

### Activate in a controller

```php
public function index(Request $request)
{
    TenantDatabase::activate($request->user());

    return ProjectResource::collection(Project::query()->latest()->get());
}
```

### Run safely in scope

```php
$total = TenantDatabase::run($user, function () {
    return Invoice::query()->sum('total');
});
```

### Inject the manager directly

```php
final class ProjectImportService
{
    public function __construct(private TenantDatabaseManager $tenants) {}

    public function import(User $user, array $rows): void
    {
        $this->tenants->run($user, function () use ($rows) {
            foreach ($rows as $row) {
                Project::query()->create($row);
            }
        });
    }
}
```

## API Stability Rules

- Facade methods are part of the stable public API.
- Contracts in `Contracts/` are stable extension points.
- Internal service classes may change unless explicitly marked public.
- Result objects may gain fields in minor releases, but existing fields must remain stable.