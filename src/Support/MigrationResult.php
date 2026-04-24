<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class MigrationResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly bool $ran,
        public readonly bool $fresh,
        public readonly bool $seed,
    ) {
    }
}

