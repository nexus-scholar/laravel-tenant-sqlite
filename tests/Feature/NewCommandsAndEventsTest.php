<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseActivated;
use NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseCreated;
use NexusScholar\LaravelTenantSqlite\Events\TenantDatabaseMigrated;
use NexusScholar\LaravelTenantSqlite\Facades\TenantDatabase;
use NexusScholar\LaravelTenantSqlite\Http\Middleware\ActivateTenantDatabase;
use NexusScholar\LaravelTenantSqlite\Models\TenantDatabaseRecord;
use NexusScholar\LaravelTenantSqlite\Tests\Fixtures\Models\TestUser;

it('dispatches events during lifecycle', function (): void {
    Event::fake();

    $user = TestUser::query()->create(['name' => 'Event User']);

    TenantDatabase::provision($user);
    Event::assertDispatched(TenantDatabaseCreated::class);

    TenantDatabase::activate($user);
    Event::assertDispatched(TenantDatabaseActivated::class);

    TenantDatabase::migrate($user);
    Event::assertDispatched(TenantDatabaseMigrated::class);
});

it('runs tenant-database:create command', function (): void {
    $user = TestUser::query()->create(['name' => 'Cmd User']);

    $exitCode = Artisan::call('tenant-database:create', ['tenant' => $user->id]);

    expect($exitCode)->toBe(0);
    expect(TenantDatabaseRecord::query()->where('tenant_id', (string) $user->id)->exists())->toBeTrue();
});

it('runs tenant-database:migrate command', function (): void {
    $user = TestUser::query()->create(['name' => 'Migrate Cmd User']);
    TenantDatabase::provision($user);

    $exitCode = Artisan::call('tenant-database:migrate', ['--tenant' => [(string) $user->id]]);

    expect($exitCode)->toBe(0);
    $record = TenantDatabaseRecord::query()->where('tenant_id', (string) $user->id)->first();
    expect($record->last_migrated_at)->not->toBeNull();
});

it('activates tenant via middleware', function (): void {
    $user = TestUser::query()->create(['name' => 'Middleware User']);
    TenantDatabase::provision($user);

    $request = new \Illuminate\Http\Request();
    $request->setUserResolver(fn() => $user);

    $middleware = new ActivateTenantDatabase();

    $middleware->handle($request, function() {
        expect(TenantDatabase::current())->not->toBeNull();
        return new \Illuminate\Http\Response();
    });

    expect(TenantDatabase::current())->toBeNull();
});
