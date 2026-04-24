<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('archives a tenant database and updates metadata', function (): void {
    $user = TestUser::query()->create(['name' => 'Archive User']);

    TenantDatabase::provision($user);
    TenantDatabase::migrate($user);

    $result = TenantDatabase::archive($user);
    $record = TenantDatabaseRecord::query()->where('tenant_id', (string) $user->id)->first();

    expect($result->archived)->toBeTrue()
        ->and($result->path)->toEndWith('tenant.sqlite')
        ->and(File::exists($result->path))->toBeTrue()
        ->and(File::exists(TenantDatabase::path($user)))->toBeFalse()
        ->and($record?->status)->toBe('archived')
        ->and($record?->archived_at)->not->toBeNull();
});

