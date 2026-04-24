<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Contracts\TenantDatabaseManager;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use Throwable;

class MigrateTenantDatabasesCommand extends Command
{
    protected $signature = 'tenant-database:migrate {--tenant=* : Specific tenant(s) to migrate} {--fresh : Wipe the database and re-run all migrations} {--seed : Seed the database after migrating}';

    protected $description = 'Run migrations for tenant databases';

    public function handle(TenantDatabaseManager $tenants): int
    {
        $tenantInputs = (array) $this->option('tenant');
        $fresh = (bool) $this->option('fresh');
        $seed = (bool) $this->option('seed');

        if (empty($tenantInputs)) {
            $tenantKeys = TenantDatabaseRecord::query()
                ->where('status', 'active')
                ->pluck('tenant_key');
        } else {
            $tenantKeys = collect($tenantInputs);
        }

        if ($tenantKeys->isEmpty()) {
            $this->warn('No tenants found to migrate.');

            return self::SUCCESS;
        }

        $this->info('Starting tenant migrations...');
        $bar = $this->output->createProgressBar($tenantKeys->count());
        $bar->start();

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($tenantKeys as $tenantKey) {
            try {
                $tenants->migrate($tenantKey, $fresh, $seed);
                $success++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "Tenant [{$tenantKey}]: " . $e->getMessage();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migration completed. Success: {$success}, Failed: {$failed}");

        if (! empty($errors)) {
            $this->error('Failures:');
            foreach ($errors as $error) {
                $this->line("- {$error}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
