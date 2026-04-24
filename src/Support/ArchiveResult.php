<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class ArchiveResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly string $path,
        public readonly bool $archived,
    ) {
    }
}

