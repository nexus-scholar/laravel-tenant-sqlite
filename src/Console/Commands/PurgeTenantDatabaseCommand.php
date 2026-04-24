<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantPurgeFailed;

class PurgeTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:purge {tenant} {--force}';

    protected $description = 'Purge a tenant SQLite database';

    public function handle(TenantDatabaseManager $tenants): int
    {
        if (! $this->option('force')) {
            $this->error('Purge requires --force.');
            return self::FAILURE;
        }

        try {
            $result = $tenants->purge($this->argument('tenant'), true);
        } catch (TenantPurgeFailed $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->line('Tenant key: ' . $result->tenant->tenantKey);
        $this->line('Purged path: ' . $result->path);

        return self::SUCCESS;
    }
}

