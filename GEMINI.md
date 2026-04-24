# Gemini CLI - Project Context: laravel-tenant-sqlite

## Project Overview
`laravel-tenant-sqlite` is a Laravel package designed to provide isolated SQLite databases for each tenant in a multi-tenant application. It manages the full lifecycle of tenant databases, from provisioning and migration to backup, archival, and purging.

### Core Technologies
- **PHP:** 8.2+
- **Laravel:** 12.0+ (Illuminate Support/Database)
- **Database:** SQLite for tenants, any Laravel-supported DB for central metadata.
- **Testing:** Pest PHP with Orchestra Testbench.

### Architecture
The project follows a clean, contract-based architecture:
- **TenantManager:** The primary entry point, bound to the `TenantDatabase` facade. It orchestrates all tenant-related operations.
- **Connection Isolation:** Dynamically injects a `tenant` database connection at runtime based on the resolved tenant.
- **Service Layers:** Dedicated services for Provisioning, Migration, Inspection, Backup, Archival, and Purging, each behind a specific contract.
- **Metadata:** A `tenant_databases` table in the central database tracks the state and location of each tenant's SQLite file.

## Development Guidelines

### Key Files & Directories
- `src/TenantDatabaseServiceProvider.php`: Main service provider where all bindings and commands are registered.
- `src/Contracts/`: Interfaces defining the behavior of core services.
- `src/Services/`: Concrete implementations of the core services.
- `src/Concerns/`: Traits for application-side models (e.g., `UsesTenantConnection`).
- `config/tenant-database.php`: Package configuration (connection names, paths, pragmas).
- `docs/`: Comprehensive documentation on architecture, API, and operations.

### Coding Standards
- **Strict Typing:** All files use `declare(strict_types=1);`.
- **Contract-Based:** Prefer injecting interfaces (Contracts) rather than concrete service implementations.
- **Laravel Idiomatic:** Adheres to Laravel's service provider patterns, facades, and migration systems.

## Operations & Commands

### Building and Running
- **Install Dependencies:** `composer install`
- **Run Tests:** `composer test` (or `vendor/bin/pest`)
- **Linting:** (Infered) Use standard PSR-12/Laravel Pint if available.

### Console Commands
- `tenant-database:doctor`: Runs environment diagnostics.
- `tenant-database:inspect {tenant?}`: Inspects tenant database files and metadata.
- `tenant-database:backup {tenant?}`: Creates a copy-based backup of tenant databases.
- `tenant-database:archive {tenant}`: Moves a tenant database to the archival directory.
- `tenant-database:purge {tenant}`: Permanently deletes a tenant database file.

## Testing with Pest
Tests are located in `tests/Feature`.
- **Base Class:** `NexusScholar\LaravelTenantSqlite\Tests\TestCase` (extends Orchestra Testbench).
- **Setup:** The test environment uses an in-memory SQLite database for central metadata and a temporary directory (`tests/temp`) for tenant files.
- **Helper:** `uses(TestCase::class)->in('Feature');` in `tests/Pest.php`.

## Usage in Application
1. **Model Setup:** Use the `UsesTenantConnection` trait on any model that should live in the tenant database.
2. **Tenant Resolution:** Configure the `resolver` in `config/tenant-database.php` (defaults to `UserTenantResolver`).
3. **Activation:** Use `TenantDatabase::activate($tenantId)` to switch context at runtime.
