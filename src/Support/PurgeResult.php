<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class PurgeResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly string $path,
        public readonly bool $purged,
    ) {
    }
}

