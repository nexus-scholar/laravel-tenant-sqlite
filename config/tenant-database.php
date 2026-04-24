<?php

return [
    'connection_name' => 'tenant',
    'base_path' => storage_path('app/tenant-databases'),
    'database_filename' => 'tenant.sqlite',

    'resolver' => NexusScholar\LaravelTenantSqlite\Resolvers\UserTenantResolver::class,
    'path_builder' => NexusScholar\LaravelTenantSqlite\Support\DefaultPathBuilder::class,

    'tenant_migration_paths' => [
        database_path('migrations/tenant'),
    ],

    'auto_provision' => true,

    'backup' => [
        'directory' => storage_path('app/tenant-backups'),
    ],

    'archive' => [
        'directory' => storage_path('app/tenant-archives'),
    ],

    'pragmas' => [
        'foreign_keys' => true,
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'busy_timeout' => 5000,
    ],
];

