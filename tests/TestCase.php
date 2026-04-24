<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use NexusScholar\LaravelTenantSqlite\TenantDatabaseServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TenantDatabaseServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set(
            'tenant-database.base_path',
            __DIR__ . '/temp/tenant-databases'
        );

        $app['config']->set('tenant-database.backup.directory', __DIR__ . '/temp/tenant-backups');
        $app['config']->set('tenant-database.archive.directory', __DIR__ . '/temp/tenant-archives');

        $app['config']->set('tenant-database.tenant_migration_paths', [
            __DIR__ . '/Fixtures/migrations/tenant',
        ]);

        File::ensureDirectoryExists(__DIR__ . '/temp/tenant-databases');
        File::ensureDirectoryExists(__DIR__ . '/temp/tenant-backups');
        File::ensureDirectoryExists(__DIR__ . '/temp/tenant-archives');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(__DIR__ . '/temp');

        parent::tearDown();
    }
}

