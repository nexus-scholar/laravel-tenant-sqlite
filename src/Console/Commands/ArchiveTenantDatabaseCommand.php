<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;

class ArchiveTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:archive {tenant}';

    protected $description = 'Archive a tenant SQLite database';

    public function handle(TenantDatabaseManager $tenants): int
    {
        $result = $tenants->archive($this->argument('tenant'));

        $this->line('Tenant key: ' . $result->tenant->tenantKey);
        $this->line('Archive path: ' . $result->path);

        return self::SUCCESS;
    }
}

