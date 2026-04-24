<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;

class InspectTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:inspect {tenant}';

    protected $description = 'Inspect a tenant SQLite database';

    public function handle(TenantDatabaseManager $tenants): int
    {
        $result = $tenants->inspect($this->argument('tenant'));
        $record = TenantDatabaseRecord::query()->where('tenant_key', $result->tenant->tenantKey)->first();

        $this->line('Tenant key: ' . $result->tenant->tenantKey);
        $this->line('Status: ' . ($record?->status ?? 'unknown'));
        $this->line('Path: ' . $result->path);
        $this->line('Exists: ' . ($result->exists ? 'yes' : 'no'));
        $this->line('Writable: ' . ($result->writable ? 'yes' : 'no'));
        $this->line('Size bytes: ' . $result->sizeBytes);
        $this->line('Schema version: ' . ($result->schemaVersion ?? 'n/a'));

        if ($result->tables !== []) {
            $this->newLine();
            $this->table(['Tables'], array_map(static fn (string $table): array => [$table], $result->tables));
        }

        return self::SUCCESS;
    }
}

