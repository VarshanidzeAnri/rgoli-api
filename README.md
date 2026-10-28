# RGOLI ZEROFRAME OS — API

Laravel 13 REST API (`/api/v1`) for the RGOLI studio OS. Full setup, demo logins and architecture:
see the workspace [README](../README.md) and [docs](../docs).

## Quick start (host PHP 8.4 + MySQL 8)

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed && php artisan storage:link
php artisan serve          # http://localhost:8000  ·  docs at /docs
php artisan queue:work     # notifications and exports
```

## Quality

```bash
php artisan test --compact               # Pest (feature + unit, role × permission matrix)
vendor/bin/pint                          # code style (PSR-12 / Laravel preset)
vendor/bin/phpstan analyse --memory-limit=1G   # Larastan level 8
php artisan scribe:generate              # API docs
```

## Layout

| Folder | Purpose |
| --- | --- |
| `app/Actions/<Domain>` | Use cases, one `handle()` each (writes, transactions, events) |
| `app/Repositories` + `Contracts/` | All Eloquent queries behind interfaces (`RepositoryProvider` binds them) |
| `app/Services` | Read models: dashboard, finance, reports, search, calendar, workload |
| `app/Http/Controllers/<Domain>` | Thin controllers: authorize → action/repository → resource |
| `app/Http/Requests/<Domain>` | Validation and list filters (→ `app/DTOs/Filters`) |
| `app/Policies` | Authorization (permission + project visibility) |
| `resources/permissions.json` | Permission catalog and default roles |
| `lang/{ka,en,ru}` | Translations (validation, messages, enums, permissions, attributes) |
