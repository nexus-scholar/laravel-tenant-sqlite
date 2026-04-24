# Laravel Isolated SQLite Docs

This folder contains implementation-oriented Markdown documentation for a Laravel 13 package that creates and manages isolated SQLite databases per tenant. The design is based on Laravel package development, service providers, multiple database connections, SQLite path-based configuration, and migration support documented by Laravel.

## Documents

- `00-overview.md` — product definition, scope, principles, and success criteria
- `01-package-architecture.md` — internal architecture and service layout
- `02-public-api.md` — facade, contracts, result objects, traits, and usage examples
- `03-config-and-data-model.md` — config file, metadata schema, filesystem layout, and model boundaries
- `04-console-and-operations.md` — Artisan commands, backups, archive, purge, and ops guidance
- `05-implementation-plan.md` — phased build plan and acceptance criteria for Codex CLI

## Suggested Codex CLI Prompt

```text
Implement the package described in these Markdown files for Laravel 13.
Start with Phase 1 through Phase 4 only.
Prefer clean contracts, focused services, and integration tests.
Treat Contracts as stable public extension points.
Do not build a UI.
```

## Implementation Notes

- Start with one database per user as the default resolver.
- Keep tenant business data in SQLite files and package metadata in the app database.
- Use a dedicated `tenant` connection name.
- Keep tenant migrations separate from the app's main migrations.