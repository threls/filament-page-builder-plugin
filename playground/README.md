**Playground** — a minimal Laravel app used to develop and test this Filament Page Builder plugin locally.

## What is this?

The `playground/` directory contains a throwaway Laravel application used to manually test and iterate on the plugin during development. It is not part of the distributed package.

Use it to:

- Try builder blocks and blueprints in a real Filament panel.
- Verify migrations, DTOs, API responses, and UI behavior.
- Reproduce bugs and test fixes quickly.

## Requirements

- PHP and Composer
- Node.js and npm (for asset building if needed)
- A database (SQLite/MySQL/PostgreSQL). SQLite is simplest for local testing.

## Quick start

Run these from the repository root:

```bash
composer install

# Copy environment and set your DB (SQLite recommended)
cp playground/.env.example playground/.env

# Generate app key
php artisan key:generate --working-path=playground

# Run migrations (and seed if you add seeders)
php artisan migrate --working-path=playground

# Create a Filament admin user
php artisan make:filament-user --working-path=playground

# Serve the app
php artisan serve --working-path=playground
```

Then visit:

- App: http://localhost:8000
- Filament panel: http://localhost:8000/admin

## Notes

- The playground points at this plugin source for development. Changes in `src/` are immediately testable after a cache clear.
- Helpful commands (run with `--working-path=playground`):
  - `php artisan optimize:clear`
  - `php artisan migrate:fresh`
  - `php artisan make:filament-user`

## Cleanup

If you break the DB during testing, the fastest reset is:

```bash
php artisan migrate:fresh --seed --working-path=playground
```
