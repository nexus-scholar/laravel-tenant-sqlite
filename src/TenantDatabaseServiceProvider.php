<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use NexusScholar\LaravelTenantSqlite\Contracts\ActivatesTenantConnection;
use NexusScholar\LaravelTenantSqlite\Contracts\ArchivesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\BacksUpTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\BuildsTenantDatabasePath;
use NexusScholar\LaravelTenantSqlite\Contracts\InspectsTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\MigratesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\PurgesTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Console\Commands\ArchiveTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\BackupTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\CreateTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\DoctorTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\InspectTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\InstallTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\MigrateTenantDatabasesCommand;
use NexusScholar\LaravelTenantSqlite\Console\Commands\PurgeTenantDatabaseCommand;
use NexusScholar\LaravelTenantSqlite\Contracts\ProvisionsTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\ResolvesTenant;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;
use NexusScholar\LaravelTenantSqlite\Resolvers\UserTenantResolver;
use NexusScholar\LaravelTenantSqlite\Services\TenantConnectionManager;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseArchiver;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseDoctor;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseBackupManager;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseMigrator;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseInspector;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabasePurger;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseProvisioner;
use NexusScholar\LaravelTenantSqlite\Services\TenantManager;
use NexusScholar\LaravelTenantSqlite\Support\DefaultPathBuilder;
use NexusScholar\LaravelTenantSqlite\Support\TenantDatabaseAbout;

class TenantDatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tenant-database.php', 'tenant-database');

        $this->app->bind(BuildsTenantDatabasePath::class, DefaultPathBuilder::class);
        $this->app->bind(ResolvesTenant::class, UserTenantResolver::class);
        $this->app->bind(ActivatesTenantConnection::class, TenantConnectionManager::class);
        $this->app->bind(ArchivesTenantDatabase::class, TenantDatabaseArchiver::class);
        $this->app->bind(BacksUpTenantDatabase::class, TenantDatabaseBackupManager::class);
        $this->app->singleton(TenantDatabaseDoctor::class);
        $this->app->bind(InspectsTenantDatabase::class, TenantDatabaseInspector::class);
        $this->app->bind(MigratesTenantDatabase::class, TenantDatabaseMigrator::class);
        $this->app->bind(PurgesTenantDatabase::class, TenantDatabasePurger::class);
        $this->app->singleton(TenantDatabaseAbout::class);

        $this->app->bind(ProvisionsTenantDatabase::class, static function ($app) {
            return new TenantDatabaseProvisioner($app->make(Filesystem::class));
        });

        $this->app->singleton(TenantDatabaseManager::class, TenantManager::class);
        $this->app->alias(TenantDatabaseManager::class, 'tenant-database.manager');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/tenant-database.php' => config_path('tenant-database.php'),
        ], 'tenant-database-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'tenant-database-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->commands([
            ArchiveTenantDatabaseCommand::class,
            BackupTenantDatabaseCommand::class,
            CreateTenantDatabaseCommand::class,
            DoctorTenantDatabaseCommand::class,
            InspectTenantDatabaseCommand::class,
            InstallTenantDatabaseCommand::class,
            MigrateTenantDatabasesCommand::class,
            PurgeTenantDatabaseCommand::class,
        ]);

        if ($this->app->runningInConsole() && class_exists(AboutCommand::class) && method_exists(AboutCommand::class, 'add')) {
            AboutCommand::add('Tenant SQLite', fn (): array => $this->app->make(TenantDatabaseAbout::class)->data());
        }
    }
}

