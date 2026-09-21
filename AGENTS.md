# Repository Guidelines

## Project Structure & Module Organization

This student portal uses PHP, Composer autoloading, and plain JavaScript/CSS. `public/index.php` wires dependencies and registers routes. Application code lives in `app/`: `Controllers/` handles HTTP requests, `Services/` holds business rules, `Repositories/` handles database access, `Models/` represents entities, and `Middleware/` enforces access. Shared infrastructure lives in `Core/`.

Use `app/Views/` for templates and partials, and `public/assets/` for CSS, JavaScript, and images. Database configuration lives in `config/database.php`; ordered SQL migrations live in `database/migrations/`. Protected tool content belongs in `storage/tools/`. Standalone tests are root-level `test-*.php` files.

## Build, Test, and Development Commands

Run commands from the repository root:

- `composer install` — install dependencies from `composer.lock`.
- `php -S localhost:8000 -t public` — serve locally with `public/` as the document root; visit `/login`.
- `php -l app/Services/CourseAccessService.php` — check a changed PHP file for syntax errors.
- `php test-course-repository.php` — run repository checks against configured database fixtures.
- `php test-course-access-service.php` — check course access rules; this script modifies database records.

There is no frontend build step, Composer test script, or configured formatter/linter.

## Coding Style & Naming Conventions

Follow the surrounding code: four-space indentation, PHP `declare(strict_types=1);`, typed properties, and parameter/return types. Classes use PascalCase and match filenames; methods and variables use camelCase. Preserve the PSR-4 namespace `Leilabrito\PortalAluno\` mapped to `app/`.

Keep SQL in repositories and business rules in services. Use prepared statements. Name assets and tests with lowercase hyphenated names; number migrations sequentially, such as `005_add_example.sql`. Preserve Portuguese user-facing text.

## Testing Guidelines

Tests use standalone PHP scripts, not PHPUnit; no coverage threshold is configured. Name new scripts `test-<feature>.php` and make failed assertions exit nonzero. Run relevant scripts and inspect their output. Use a dedicated test database with the required fixtures: existing scripts assume records such as user/course ID `1`, and mutations may persist after failures.

## Commit & Pull Request Guidelines

Recent history uses `feat:`, `fix:`, `refactor:`, and `perf:` prefixes with short descriptions. Follow that pattern and keep commits focused. PRs should describe behavior changes, list validation performed, link relevant issues, and include screenshots for UI changes. Explain migration or configuration requirements.

## Security & Configuration

Create a local `.env` for bootstrap configuration, including `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. Never commit credentials. Keep protected tools outside `public/` and preserve authentication, role, and course-access checks.

@RTK.md
