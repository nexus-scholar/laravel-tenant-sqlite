<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Contracts\BacksUpTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;

class BackupTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:backup {tenant} {--name=} {--compress}';

    protected $description = 'Create a backup for a tenant SQLite database';

    public function handle(TenantDatabaseManager $tenants, BacksUpTenantDatabase $backups): int
    {
        $context = $tenants->activate($this->argument('tenant'));

        try {
            $result = $backups->backup($context, $this->option('name'), (bool) $this->option('compress'));
        } finally {
            $tenants->deactivate();
        }

        $this->line('Tenant key: ' . $result->tenant->tenantKey);
        $this->line('Backup path: ' . $result->path);

        return self::SUCCESS;
    }
}

