<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class DoctorResult
{
    /**
     * @param array<int, DoctorCheck> $checks
     */
    public function __construct(public readonly array $checks)
    {
    }

    public function hasFailures(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->isFailure()) {
                return true;
            }
        }

        return false;
    }
}

