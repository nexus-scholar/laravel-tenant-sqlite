<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Console\Commands;

use Illuminate\Console\Command;
use NexusScholar\LaravelTenantSqlite\Services\TenantDatabaseDoctor;

class DoctorTenantDatabaseCommand extends Command
{
    protected $signature = 'tenant-database:doctor';

    protected $description = 'Run environment and package diagnostics';

    public function handle(TenantDatabaseDoctor $doctor): int
    {
        $result = $doctor->diagnose();

        foreach ($result->checks as $check) {
            $line = sprintf('%s: %s', $check->label, $check->status);

            if ($check->message !== '') {
                $line .= ' (' . $check->message . ')';
            }

            $this->line($line);
        }

        return $result->hasFailures() ? self::FAILURE : self::SUCCESS;
    }
}

