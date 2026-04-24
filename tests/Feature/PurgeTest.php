<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('purges an archived tenant database with force and removes the archive file', function (): void {
    $user = TestUser::query()->create(['name' => 'Purge User']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $archive = TenantDatabase::archive($user);
    $result = TenantDatabase::purge($user, true);
    $record = TenantDatabaseRecord::query()->where('tenant_id', (string) $user->id)->first();

    expect($archive->path)->toBe($result->path)
        ->and(File::exists($result->path))->toBeFalse()
        ->and($record?->status)->toBe('purged')
        ->and($record?->purged_at)->not->toBeNull();
});

it('refuses to purge without force', function (): void {
    $user = TestUser::query()->create(['name' => 'Purge Force User']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $this->artisan('tenant-database:purge', [
        'tenant' => (string) $user->getKey(),
    ])
        ->assertExitCode(1)
        ->expectsOutputToContain('Purge requires --force.');
});

