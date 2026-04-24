# Laravel Tenant SQLite

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nexus-scholar/laravel-tenant-sqlite.svg?style=flat-square)](https://packagist.org/packages/nexus-scholar/laravel-tenant-sqlite)
[![Total Downloads](https://img.shields.io/packagist/dt/nexus-scholar/laravel-tenant-sqlite.svg?style=flat-square)](https://packagist.org/packages/nexus-scholar/laravel-tenant-sqlite)
[![Tests](https://github.com/nexus-scholar/laravel-tenant-sqlite/actions/workflows/tests.yml/badge.svg)](https://github.com/nexus-scholar/laravel-tenant-sqlite/actions/workflows/tests.yml)
[![PHP Version Require](http://img.shields.io/badge/php-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/packagist/l/nexus-scholar/laravel-tenant-sqlite.svg?style=flat-square)](https://packagist.org/packages/nexus-scholar/laravel-tenant-sqlite)

A robust and secure Laravel package for implementing multi-tenancy using an **isolated SQLite database file for each tenant**. 

Instead of adding a `tenant_id` column to every table, this package gives each user (or organization) their own dedicated SQLite file. This approach guarantees complete data isolation, trivializes single-tenant backups, and simplifies data portability.

---

## Features

- 🔒 **Absolute Isolation:** Tenants physically cannot query each other's data.
- 📦 **Simple Backups:** Back up a tenant's entire dataset by copying a single `.sqlite` file.
- 🚀 **Dynamic Connections:** Seamlessly connects Eloquent models to the correct file at runtime.
- 🛠️ **Full Lifecycle Management:** Commands to create, migrate, inspect, backup, archive, and purge tenant databases.
- 🌐 **HTTP & Queue Support:** Includes middleware to automatically resolve tenants for web requests and background jobs.
- ⚡ **Pest Tested:** Thoroughly tested for complete reliability and data isolation.

## Documentation

For a comprehensive guide on getting started, setting up models, and writing migrations, please read our beginner's guide:

📖 **[Read the Beginner's Guide](docs/07-beginners-guide.md)**

For architectural details, consult the [docs directory](docs/README.md).

## Quick Start

### Installation

```bash
composer require nexus-scholar/laravel-tenant-sqlite
php artisan tenant-database:install
php artisan migrate
```

### Basic Usage

1. Create a tenant migration in `database/migrations/tenant/0001_create_projects_table.php`.
2. Use the `UsesTenantConnection` trait on your models:

```php
use Illuminate\Database\Eloquent\Model;
use NexusScholar\LaravelTenantSqlite\Concerns\UsesTenantConnection;

class Project extends Model
{
    use UsesTenantConnection;
}
```

3. Provision a tenant and run their migrations:

```php
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;

$user = User::find(1);
TenantDatabase::provision($user);
TenantDatabase::migrate($user);
```

4. Use the middleware to automatically route requests to the correct database:

```php
// routes/web.php
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/projects', function () {
        // This automatically queries the authenticated user's SQLite file!
        return App\Models\Project::all();
    });
});
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
