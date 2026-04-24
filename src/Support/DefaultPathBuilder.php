<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

use Illuminate\Contracts\Config\Repository;
use NexusScholar\LaravelTenantSqlite\Contracts\BuildsTenantDatabasePath;
use InvalidArgumentException;

class DefaultPathBuilder implements BuildsTenantDatabasePath
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function build(string $tenantKey): string
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $tenantKey)) {
            throw new InvalidArgumentException('Invalid tenant key format.');
        }

        $basePath = (string) $this->config->get('tenant-database.base_path');
        $filename = (string) $this->config->get('tenant-database.database_filename', 'tenant.sqlite');

        return rtrim($basePath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $tenantKey
            . DIRECTORY_SEPARATOR
            . $filename;
    }
}

