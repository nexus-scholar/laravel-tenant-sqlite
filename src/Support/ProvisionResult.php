<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class ProvisionResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly string $path,
        public readonly bool $created,
        public readonly bool $bootstrapped,
        public readonly ?string $schemaVersion,
    ) {
    }
}

