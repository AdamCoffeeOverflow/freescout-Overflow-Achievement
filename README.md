# OverflowAchievement

OverflowAchievement adds XP, levels, achievements, and motivational UI feedback to FreeScout.

This package is intended to be the **single distribution** for both:
- **Fresh installs** (no existing OverflowAchievement tables)
- **Upgrades** (previous versions already installed)

## Install / Upgrade
1. Copy `OverflowAchievement/` into your FreeScout `Modules/` directory.
2. In FreeScout: **Manage → Modules** → Activate (or Update).
3. Run migrations:
   - `php artisan migrate`
4. Clear caches:
   - `php artisan cache:clear`
   - `php artisan view:clear`
   - `php artisan config:clear`
5. Rebuild module assets (recommended when updating):
   - `php artisan freescout:module-build`

## PostgreSQL
PostgreSQL is supported.

The migration that hardens hot-path indexes is designed to be **idempotent** on PostgreSQL (uses `CREATE INDEX IF NOT EXISTS` and runs outside a transaction) to prevent the classic “transaction aborted” cascade.

## Version
- Current version: **1.1.0**
