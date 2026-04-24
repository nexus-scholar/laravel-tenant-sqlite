<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class TenantContext
{
    public function __construct(
        public readonly string $tenantKey,
        public readonly string $ownerType,
        public readonly string|int $ownerId,
        public readonly string $databasePath,
        public readonly string $connectionName = 'tenant',
    ) {
    }
}

