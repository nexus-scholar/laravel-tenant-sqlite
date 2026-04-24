<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Schema;
use NexusScholar\LaravelTenantSqlite\Support\DoctorCheck;
use NexusScholar\LaravelTenantSqlite\Support\DoctorResult;
use PDO;
use Throwable;

class TenantDatabaseDoctor
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function diagnose(): DoctorResult
    {
        $checks = [
            $this->sqliteDriverCheck(),
            $this->basePathCheck(),
            $this->migrationPathsCheck(),
            $this->configPublishedCheck(),
            $this->metadataTableCheck(),
        ];

        return new DoctorResult($checks);
    }

    private function sqliteDriverCheck(): DoctorCheck
    {
        try {
            $drivers = class_exists(PDO::class) ? PDO::getAvailableDrivers() : [];

            if (extension_loaded('pdo_sqlite') || in_array('sqlite', $drivers, true)) {
                return DoctorCheck::pass('SQLite driver', 'pdo_sqlite is available.');
            }
        } catch (Throwable) {
            // fall through to failure below
        }

        return DoctorCheck::fail('SQLite driver', 'pdo_sqlite is not available.');
    }

    private function basePathCheck(): DoctorCheck
    {
        $basePath = (string) $this->config->get('tenant-database.base_path', '');

        if ($basePath === '') {
            return DoctorCheck::fail('Base path', 'tenant-database.base_path is not configured.');
        }

        if (! is_dir($basePath)) {
            return DoctorCheck::fail('Base path', $basePath . ' does not exist.');
        }

        if (! is_writable($basePath)) {
            return DoctorCheck::fail('Base path', $basePath . ' is not writable.');
        }

        return DoctorCheck::pass('Base path', $basePath . ' is ready.');
    }

    private function migrationPathsCheck(): DoctorCheck
    {
        $paths = (array) $this->config->get('tenant-database.tenant_migration_paths', []);
        $missing = [];

        foreach ($paths as $path) {
            if (! is_dir($path) && ! is_file($path)) {
                $missing[] = $path;
            }
        }

        if ($missing !== []) {
            return DoctorCheck::fail('Migration paths', implode(', ', $missing) . ' are missing.');
        }

        return DoctorCheck::pass('Migration paths', 'All configured tenant migration paths exist.');
    }

    private function configPublishedCheck(): DoctorCheck
    {
        $published = config_path('tenant-database.php');

        if (! is_file($published)) {
            return DoctorCheck::warn('Config file', 'tenant-database.php has not been published to the host app.');
        }

        return DoctorCheck::pass('Config file', $published . ' exists.');
    }

    private function metadataTableCheck(): DoctorCheck
    {
        try {
            $connection = (string) $this->config->get('database.default');

            if ($connection === '') {
                return DoctorCheck::fail('Metadata table', 'No default database connection is configured.');
            }

            if (Schema::connection($connection)->hasTable('tenant_databases')) {
                return DoctorCheck::pass('Metadata table', 'tenant_databases exists on the app database.');
            }

            return DoctorCheck::fail('Metadata table', 'tenant_databases is missing from the app database.');
        } catch (Throwable $e) {
            return DoctorCheck::fail('Metadata table', $e->getMessage());
        }
    }
}

