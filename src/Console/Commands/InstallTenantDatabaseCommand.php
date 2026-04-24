<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\TenantDatabaseServiceProvider;

class InstallTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:install';

    protected $description = 'Install the Tenant SQLite package resources';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--provider' => TenantDatabaseServiceProvider::class,
            '--tag' => 'tenant-database-config',
        ]);

        $this->call('vendor:publish', [
            '--provider' => TenantDatabaseServiceProvider::class,
            '--tag' => 'tenant-database-migrations',
        ]);

        $tenantMigrationPath = database_path('migrations/tenant');

        if (! is_dir($tenantMigrationPath)) {
            mkdir($tenantMigrationPath, 0755, true);
            $this->info("Created tenant migration directory: {$tenantMigrationPath}");
        }

        $this->info('Tenant database package installed successfully.');
        $this->comment('Next steps:');
        $this->line('1. Run "php artisan migrate" to create the central metadata table.');
        $this->line('2. Add your tenant migrations to "database/migrations/tenant".');

        return self::SUCCESS;
    }
}
