# Beginner's Guide to Laravel Tenant SQLite

Welcome! This guide is designed for developers who are new to multi-tenancy or new to this package. By the end of this guide, you will have a working Laravel application that securely isolates user data using one SQLite file per user (tenant).

## What is Multi-Tenancy?
In a standard application, all users share the same database tables. To know which data belongs to which user, you typically add a `user_id` column to every table (e.g., `projects.user_id`). 

In a **multi-tenant** application, each user (or "tenant") gets their own isolated environment. This package implements multi-tenancy by giving **each tenant their own separate SQLite database file**.

**Benefits:**
- **Security:** It is impossible for User A to accidentally query User B's data.
- **Backups:** You can back up or restore a single user's data just by copying a single file.
- **Portability:** Moving a user's data to a different server or region is as easy as moving a file.

---

## 1. Installation

First, install the package via Composer:

```bash
composer require nexus-scholar/laravel-tenant-sqlite
```

Next, run the installation command. This will publish the configuration file and the migration needed for the central metadata table.

```bash
php artisan tenant-database:install
```

This command does three things:
1. Creates `config/tenant-database.php`.
2. Creates a migration for the `tenant_databases` table.
3. Creates a new directory at `database/migrations/tenant` where your tenant-specific migrations will live.

Run your main application migrations to create the `tenant_databases` table:

```bash
php artisan migrate
```

---

## 2. Configuration

Open `config/tenant-database.php`. By default, the package is configured to use the authenticated user as the tenant. 

```php
'base_path' => storage_path('app/tenant-databases'),
```
All tenant SQLite files will be stored safely inside `storage/app/tenant-databases/`. Because they are in the `storage/app` directory, they are protected from public web access.

---

## 3. Creating Tenant Migrations

We need some tables to exist *inside* the tenant's database. Let's create a `projects` table.

**Important:** Do NOT use the standard `php artisan make:migration` command if you want the migration to go to the tenant directory. Instead, you can specify the path, or just manually create a file inside `database/migrations/tenant/`.

Create a file named `database/migrations/tenant/0001_create_projects_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

---

## 4. Setting up a Tenant Model

Now, let's create the Eloquent Model for the `Project`.

```bash
php artisan make:model Project
```

Open `app/Models/Project.php` and add the `UsesTenantConnection` trait. This trait is the magic that tells Laravel: *"Whenever you query this model, look inside the currently active tenant's SQLite file, not the main application database."*

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use NexusScholar\LaravelTenantSqlite\Concerns\UsesTenantConnection;

class Project extends Model
{
    use UsesTenantConnection; // <--- Add this!

    protected $guarded = [];
}
```

---

## 5. Provisioning a Tenant

When a user signs up for your application, you need to create their personal SQLite database. You can do this in your registration controller or via a Model Observer.

Let's do it right after a User is created:

```php
use App\Models\User;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;

$user = User::create([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'password' => bcrypt('password'),
]);

// This creates the SQLite file and runs the tenant migrations!
TenantDatabase::provision($user);
TenantDatabase::migrate($user);
```

You can also do this from the command line for testing:
```bash
php artisan tenant-database:create 1 
php artisan tenant-database:migrate --tenant=1
```
*(Where `1` is the User's ID)*

---

## 6. Accessing Tenant Data in web requests

When Alice logs in, how do we make sure she sees her projects? We use the provided Middleware.

Open `bootstrap/app.php` (or `app/Http/Kernel.php` in older Laravel versions) and register the middleware for your tenant routes.

```php
use NexusScholar\LaravelTenantSqlite\Http\Middleware\ActivateTenantDatabase;

// In Laravel 11+:
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'tenant' => ActivateTenantDatabase::class,
    ]);
})
```

Now, apply this middleware to your routes. The middleware automatically looks at the currently authenticated user (`Auth::user()`) and connects Laravel to their specific database file.

```php
// routes/web.php

Route::middleware(['auth', 'tenant'])->group(function () {
    
    Route::get('/projects', function () {
        // Because of the middleware and the `UsesTenantConnection` trait,
        // this will ONLY query Alice's personal SQLite file!
        return App\Models\Project::all();
    });

});
```

---

## 7. Accessing Tenant Data in Background Jobs

If you are dispatching queued jobs (like sending a weekly report), the job needs to know which database to connect to, since there is no logged-in user in the background queue.

Add the `InteractsWithTenantDatabase` trait to your job, and set the `$tenantKey` before dispatching.

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NexusScholar\LaravelTenantSqlite\Concerns\InteractsWithTenantDatabase;

class GenerateWeeklyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use InteractsWithTenantDatabase; // <--- Add this!

    public function __construct(public int $userId)
    {
        // Tell the job which tenant database to activate
        $this->tenantKey = $userId;
    }

    public function handle(): void
    {
        // The middleware will automatically activate the database before this runs.
        $projects = \App\Models\Project::count();
        
        // ... send email ...
    }
}
```

To make sure the Queue worker actually activates the database, you must register the Job Middleware in your Job class:

```php
use NexusScholar\LaravelTenantSqlite\Queue\Middleware\ActivateTenantForJob;

public function middleware(): array
{
    return [new ActivateTenantForJob];
}
```

---

## 8. Useful Console Commands

The package comes with several powerful tools to help you manage your tenants from the command line:

- **Check health:** `php artisan tenant-database:doctor`
- **Inspect a tenant:** `php artisan tenant-database:inspect 1` (Shows file size, tables, and status)
- **Backup a tenant:** `php artisan tenant-database:backup 1 --compress` (Creates a zipped backup of their DB)
- **Archive a tenant:** `php artisan tenant-database:archive 1` (Moves their DB to an archive folder, locking it)

## Summary
You have now set up a highly secure, deeply isolated multi-tenant application. Enjoy building with Laravel Tenant SQLite!
