<?php

declare(strict_types=1);

namespace NexusScholar\LaravelTenantSqlite\Support;

final class DoctorCheck
{
    private function __construct(
        public readonly string $label,
        public readonly string $status,
        public readonly string $message,
        public readonly bool $required,
    ) {
    }

    public static function pass(string $label, string $message = ''): self
    {
        return new self($label, 'ok', $message, true);
    }

    public static function warn(string $label, string $message): self
    {
        return new self($label, 'warn', $message, false);
    }

    public static function fail(string $label, string $message): self
    {
        return new self($label, 'fail', $message, true);
    }

    public function isFailure(): bool
    {
        return $this->status === 'fail';
    }
}

