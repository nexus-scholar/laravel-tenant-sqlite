<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;

class CreateTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:create {tenant}';

    protected $description = 'Provision a new tenant SQLite database';

    public function handle(TenantDatabaseManager $tenants): int
    {
        $tenant = $this->argument('tenant');

        $this->info("Provisioning tenant: {$tenant}");

        $result = $tenants->provision($tenant);

        $this->line('Tenant key: ' . $result->tenant->tenantKey);
        $this->line('Database path: ' . $result->path);
        $this->line('Status: ' . ($result->created ? 'Created' : 'Already exists'));

        return self::SUCCESS;
    }
}
