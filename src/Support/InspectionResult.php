<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class InspectionResult
{
    public function __construct(
        public readonly TenantContext $tenant,
        public readonly string $path,
        public readonly bool $exists,
        public readonly bool $writable,
        public readonly int $sizeBytes,
        public readonly array $tables,
        public readonly ?string $schemaVersion,
        public readonly array $pragmas,
    ) {
    }
}

