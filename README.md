# OverflowAchievement

<img width="256" height="256" alt="icon" src="https://github.com/user-attachments/assets/a70bb703-b85c-4708-83ac-5e69eb3d1e28" />

OverflowAchievement adds XP, levels, achievements, and motivational UI feedback to FreeScout.
Over 100+ Icon packs for hundreds of trophies
Over 100+ motivation quotes for each trophies.
Over 100+ triggers to motivate users to unlock these trophies and compete internally for collecting them!


![Recording 2026-02-22 152000](https://github.com/user-attachments/assets/d329039b-cad9-4090-9d49-a00ce4e4f368)


Toast notification for achievement trophy unlocked
Toast notification for level reach

<img width="800" height="422" alt="Screenshot 2026-02-22 151614" src="https://github.com/user-attachments/assets/59de2201-ac51-45ae-9306-c6aafab1e2cf" />

<img width="1200" height="484" alt="image" src="https://github.com/user-attachments/assets/9905176d-1ccc-46eb-a6b0-08ff24a8c150" />

<img width="250" height="323" alt="Screenshot 2026-02-22 152050" src="https://github.com/user-attachments/assets/fb062186-3f3b-44ef-b40b-6de695974073" />

<img width="439" height="257" alt="Screenshot 2026-02-22 151551" src="https://github.com/user-attachments/assets/f6adba7a-a68a-4cbc-84ad-343e984bf02c" />

over 3+ different themes to choose from.
and more...

<img width="761" height="798" alt="Screenshot 2026-02-22 152116" src="https://github.com/user-attachments/assets/228dd822-e93e-49e5-bae6-d1908817a159" />


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
- Current version: **1.1.3**
## Compatibility With Freescout Module(s)
- Teams Module
- Custom Field Module
