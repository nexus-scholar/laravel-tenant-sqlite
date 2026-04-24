# Testing with Pest

This package should be thoroughly tested using [Pest PHP](https://pestphp.com/) and [Orchestra Testbench](https://packages.laravel.com/). Because the package interacts with the filesystem and database connections, tests must ensure strict isolation and proper cleanup.

## Test Suite Setup

### Dependencies
Require the necessary testing tools in `composer.json`:
```json
"require-dev": {
    "orchestra/testbench": "^10.0",
    "pestphp/pest": "^3.0",
    "pestphp/pest-plugin-laravel": "^3.0"
}
```

### Base TestCase
Create a base `TestCase.php` that boots the package within Orchestra Testbench.

```php
// tests/TestCase.php
namespace Vendor\Package\Tests;

use Vendor\Package\TenantDatabaseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            TenantDatabaseServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite in memory for the central app DB
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Point tenant databases to a temporary testing directory
        $app['config']->set(
            'tenant-database.base_path', 
            __DIR__ . '/temp/tenant-databases'
        );
    }

    protected function tearDown(): void
    {
        // Clean up generated tenant sqlite files after each test
        $files = glob(__DIR__ . '/temp/tenant-databases/*/*.sqlite');
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir(__DIR__ . '/temp/tenant-databases');
        
        parent::tearDown();
    }
}
```

### Pest Configuration
Configure Pest to use this base test case for all integration tests.

```php
// tests/Pest.php
use Vendor\Package\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
```

## Recommended Test Files

### 1. Architecture Tests (`tests/ArchTest.php`)
Use Pest's architecture testing to enforce clean dependency rules.

```php
test('contracts do not depend on concretions')
    ->expect('Vendor\Package\Contracts')
    ->toUseNothing();

test('strict types are used')
    ->expect('Vendor\Package')
    ->toUseStrictTypes();

test('facade only depends on contracts or manager')
    ->expect('Vendor\Package\Facades\TenantDatabase')
    ->toOnlyUse([
        'Illuminate\Support\Facades\Facade',
        'Vendor\Package\Contracts\TenantDatabaseManager'
    ]);
```

### 2. Provisioning Tests (`tests/Feature/ProvisioningTest.php`)
Verify that the package creates the filesystem artifacts and metadata records.

```php
use Vendor\Package\Facades\TenantDatabase;
use Illuminate\Support\Facades\File;

test('it provisions a new sqlite database for a user', function () {
    $user = User::factory()->create(); // Assuming a standard Testbench User model

    $result = TenantDatabase::provision($user);

    expect($result->created)->toBeTrue()
        ->and($result->path)->toEndWith('tenant.sqlite')
        ->and(File::exists($result->path))->toBeTrue();

    // Verify metadata record was created
    $this->assertDatabaseHas('tenant_databases', [
        'tenant_id' => $user->id,
        'status' => 'active'
    ]);
});
```

### 3. Connection and Isolation Tests (`tests/Feature/IsolationTest.php`)
Verify that connection switching works and tenants cannot see each other's data.

```php
use Vendor\Package\Facades\TenantDatabase;

test('tenants have isolated data', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    TenantDatabase::provision($userA);
    TenantDatabase::provision($userB);

    // Run in User A's context
    TenantDatabase::run($userA, function () {
        TenantModel::create(['name' => 'Data for A']);
        expect(TenantModel::count())->toBe(1);
    });

    // Run in User B's context
    TenantDatabase::run($userB, function () {
        TenantModel::create(['name' => 'Data for B']);
        expect(TenantModel::count())->toBe(1); // Should only see B's data
        expect(TenantModel::first()->name)->toBe('Data for B');
    });

    // Verify main connection is restored
    expect(config('database.default'))->toBe('testing');
});
```

### 4. Migration Tests (`tests/Feature/MigrationTest.php`)
Verify that `TenantDatabaseMigrator` correctly runs schemas on the `tenant` connection.

```php
use Vendor\Package\Facades\TenantDatabase;
use Illuminate\Support\Facades\Schema;

test('it runs tenant migrations exclusively on the tenant connection', function () {
    $user = User::factory()->create();
    TenantDatabase::provision($user); // Provisioning should run migrations if strategy = 'migrate'

    TenantDatabase::run($user, function () {
        // Assert the tenant-specific table exists in the tenant connection
        expect(Schema::connection('tenant')->hasTable('projects'))->toBeTrue();
    });

    // Assert the app connection DOES NOT have the tenant table
    expect(Schema::connection('testing')->hasTable('projects'))->toBeFalse();
});
```

## Testing Rules for Codex CLI

- **Write tests first** (TDD) for the core Resolver, PathBuilder, and Provisioner.
- Always use memory SQLite for the central app database.
- Always use physical temporary files for the tenant SQLite databases during testing (memory SQLite cannot simulate file-per-tenant isolation properly).
- Ensure the `tearDown` method strictly cleans up all generated `.sqlite` files to prevent cross-test pollution.
- Avoid using `dd()` or `dump()` in committed test code. Use Pest's expectation API (`expect()`).