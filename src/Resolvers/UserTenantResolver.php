<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NexusScholar\LaravelTenantSqlite\Contracts\BuildsTenantDatabasePath;
use NexusScholar\LaravelTenantSqlite\Contracts\ResolvesTenant;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;

class UserTenantResolver implements ResolvesTenant
{
    public function __construct(private readonly BuildsTenantDatabasePath $pathBuilder)
    {
    }

    public function resolve(mixed $tenant): TenantContext
    {
        if ($tenant instanceof TenantContext) {
            return $tenant;
        }

        [$ownerType, $ownerId, $candidateKey, $searchByTenantIdOnly] = $this->normalize($tenant);

        $record = $this->findRecord($ownerType, $ownerId, $candidateKey, $searchByTenantIdOnly);
        if ($record !== null) {
            $ownerType = (string) $record->tenant_type;
            $ownerId = (string) $record->tenant_id;
        }

        $tenantKey = $record?->tenant_key ?? $candidateKey ?? (string) Str::ulid();
        $path = $record?->database_path ?? $this->pathBuilder->build($tenantKey);

        return new TenantContext(
            tenantKey: $tenantKey,
            ownerType: $ownerType,
            ownerId: $ownerId,
            databasePath: $path,
            connectionName: (string) config('tenant-database.connection_name', 'tenant'),
        );
    }

    private function findRecord(string $ownerType, string|int $ownerId, ?string $candidateKey, bool $searchByTenantIdOnly = false): ?TenantDatabaseRecord
    {
        if ($candidateKey !== null) {
            $record = TenantDatabaseRecord::query()->where('tenant_key', $candidateKey)->first();
            if ($record !== null) {
                return $record;
            }
        }

        if ($searchByTenantIdOnly) {
            return TenantDatabaseRecord::query()->where('tenant_id', (string) $ownerId)->first();
        }

        return TenantDatabaseRecord::query()
            ->where('tenant_type', $ownerType)
            ->where('tenant_id', (string) $ownerId)
            ->first();
    }

    private function normalize(mixed $tenant): array
    {
        if (is_string($tenant)) {
            if (ctype_digit($tenant)) {
                return ['int', (int) $tenant, null, true];
            }

            return ['key', $tenant, $tenant, false];
        }

        if (is_int($tenant)) {
            return ['int', $tenant, null, true];
        }

        if ($tenant instanceof Model) {
            return [
                $tenant::class,
                (string) $tenant->getKey(),
                null,
                false,
            ];
        }

        throw new \InvalidArgumentException('Unsupported tenant input.');
    }
}

