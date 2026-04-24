<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

use Composer\InstalledVersions;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use Throwable;

class TenantDatabaseAbout
{
    /**
     * @return array<string, string>
     */
    public function data(): array
    {
        return [
            'Version' => $this->packageVersion(),
            'Connection' => (string) config('tenant-database.connection_name', 'tenant'),
            'Base path' => (string) config('tenant-database.base_path', 'n/a'),
            'Resolver' => (string) config('tenant-database.resolver', 'n/a'),
            'Bootstrap strategy' => (string) config('tenant-database.bootstrap.strategy', 'n/a'),
            'Metadata records' => $this->metadataRecords(),
        ];
    }

    private function packageVersion(): string
    {
        try {
            if (class_exists(InstalledVersions::class)
                && InstalledVersions::isInstalled('nexus-scholar/laravel-tenant-sqlite')) {
                return InstalledVersions::getPrettyVersion('nexus-scholar/laravel-tenant-sqlite')
                    ?? InstalledVersions::getVersion('nexus-scholar/laravel-tenant-sqlite')
                    ?? 'unknown';
            }
        } catch (Throwable) {
            // Fall back to unknown if Composer runtime metadata is unavailable.
        }

        return 'unknown';
    }

    private function metadataRecords(): string
    {
        try {
            return (string) TenantDatabaseRecord::query()->count();
        } catch (Throwable) {
            return 'n/a';
        }
    }
}

