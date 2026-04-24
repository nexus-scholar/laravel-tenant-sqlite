<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use NexusScholar\LaravelTenantSqlite\Exceptions\TenantActivationFailed;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('creates a zip backup when --compress is used', function (): void {
    if (! class_exists(\ZipArchive::class)) {
        test()->markTestSkipped('ZipArchive extension is not available in this environment.');
    }

    $user = TestUser::query()->create(['name' => 'Compressed Backup']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $this->artisan('tenant-database:backup', [
        'tenant' => (string) $user->getKey(),
        '--name' => 'compressed-snapshot',
        '--compress' => true,
    ])
        ->assertExitCode(0)
        ->expectsOutputToContain('Backup path:');

    $backupDir = (string) config('tenant-database.backup.directory');
    // The command deactivates tenant context; fetch the key through inspection.
    $tenantKey = TenantDatabase::inspect($user)->tenant->tenantKey;
    $tenantBackupDir = $backupDir . DIRECTORY_SEPARATOR . $tenantKey;

    expect(File::exists($tenantBackupDir))->toBeTrue();
    expect(collect(File::files($tenantBackupDir))->contains(fn ($file) => str_ends_with($file->getFilename(), '.zip')))->toBeTrue();
});

it('fails backup command for archived tenants', function (): void {
    $user = TestUser::query()->create(['name' => 'Archived Backup']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);
    TenantDatabase::archive($user);

    expect(fn () => $this->artisan('tenant-database:backup', [
        'tenant' => (string) $user->getKey(),
    ]))->toThrow(TenantActivationFailed::class, 'archived or purged');
});



