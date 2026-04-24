<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Services;

use Illuminate\Filesystem\Filesystem;
use NexusScholar\LaravelTenantSqlite\Contracts\InspectsTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Support\InspectionResult;
use NexusScholar\LaravelTenantSqlite\Support\TenantContext;
use PDO;

class TenantDatabaseInspector implements InspectsTenantDatabase
{
    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function inspect(TenantContext $context): InspectionResult
    {
        $path = $context->databasePath;
        $exists = $this->filesystem->exists($path);
        $writable = $exists ? $this->filesystem->isWritable($path) : $this->filesystem->isWritable(dirname($path));
        $sizeBytes = $exists ? (int) ($this->filesystem->size($path) ?: 0) : 0;
        $tables = $exists ? $this->tableNames($path) : [];
        $pragmas = $exists ? $this->pragmas($path) : [];
        $schemaVersion = TenantDatabaseRecord::query()->where('tenant_key', $context->tenantKey)->value('schema_version');

        return new InspectionResult(
            tenant: $context,
            path: $path,
            exists: $exists,
            writable: $writable,
            sizeBytes: $sizeBytes,
            tables: $tables,
            schemaVersion: $schemaVersion !== null ? (string) $schemaVersion : null,
            pragmas: $pragmas,
        );
    }

    private function tableNames(string $path): array
    {
        $pdo = new PDO('sqlite:' . $path);
        $statement = $pdo->prepare('SELECT name FROM sqlite_master WHERE type = ? AND name NOT LIKE ? ORDER BY name');

        if ($statement === false || ! $statement->execute(['table', 'sqlite_%'])) {
            return [];
        }

        return array_values(array_map(
            static fn (array $row): string => (string) ($row['name'] ?? ''),
            array_filter($statement->fetchAll(PDO::FETCH_ASSOC), static fn (array $row): bool => isset($row['name']) && $row['name'] !== '')
        ));
    }

    private function pragmas(string $path): array
    {
        $pdo = new PDO('sqlite:' . $path);
        $result = [];

        foreach (array_keys((array) config('tenant-database.pragmas', [])) as $pragma) {
            $statement = $pdo->query('PRAGMA ' . $pragma);
            $row = $statement?->fetch(PDO::FETCH_NUM);
            $result[$pragma] = $row[0] ?? null;
        }

        return $result;
    }
}

