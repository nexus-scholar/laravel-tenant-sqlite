<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use NexusScholar\LaravelTenantSqlite\Contracts\ActivatesTenantConnection;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantActivationFailed;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use Throwable;

class TenantConnectionManager implements ActivatesTenantConnection
{
    public function __construct(private readonly Repository $config)
    {
    }

    public function activate(TenantContext $context): void
    {
        $connectionName = $context->connectionName;

        $settings = [
            'driver' => 'sqlite',
            'database' => $context->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        $this->config->set("database.connections.{$connectionName}", $settings);

        try {
            DB::purge($connectionName);
            DB::reconnect($connectionName);

            event(new \NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseActivated($context));
        } catch (Throwable $e) {
            throw new TenantActivationFailed('Failed to activate tenant connection.', previous: $e);
        }
    }

    public function deactivate(?string $connectionName = null): void
    {
        $name = $connectionName ?? (string) $this->config->get('tenant-database.connection_name', 'tenant');

        DB::purge($name);
    }
}

