# OverflowAchievement
<img width="256" height="256" alt="icon" src="https://github.com/user-attachments/assets/a70bb703-b85c-4708-83ac-5e69eb3d1e28" />

Author: AdamCoffeeOverflow

<a href="https://www.buymeacoffee.com/AdamOverflow" target="_blank" rel="noopener noreferrer">
  <img
    src="https://img.buymeacoffee.com/button-api/?text=Buy%20me%20coffee!&emoji=%E2%98%95&slug=AdamOverflow&button_colour=FF5F5F&font_colour=ffffff&font_family=Inter&outline_colour=000000&coffee_colour=FFDD00"
    alt="Buy me a coffee"
  />
</a>

OverflowAchievement adds XP, levels, achievements, and motivational UI feedback to FreeScout.
Over 100+ Icon packs for hundreds of trophies
Over 100+ motivation quotes for each trophies.
Over 100+ triggers to motivate users to unlock these trophies and compete internally for collecting them!

## 2.0.15

- Fixes the PHP 8 `method_exists(null, ...)` fatal that could occur while FreeScout validates an empty mailbox quote-rules setting through Laravel 5.5's legacy `json` rule.
- Keeps mailbox quote rules normalized by the module before persistence instead of depending on the legacy framework JSON validator.
- Makes achievement unlock notifications more compact and reduces the oversized blue/purple rarity bloom that could look like a rendering artifact.
- Repairs the historical `2026_02_20_000007_more_triggers_and_counters.php` migration class-name collision reported in issue #21 using a new append-only, idempotent forward migration. The shipped migration is intentionally left unchanged.
- Restores the seven affected user-stat counters and any missing built-in achievements for those triggers without removing existing progress.
- Source-checked against the current FreeScout 1.8.239 source. Live PHP/database/browser verification is still required on the target installation before calling the release runtime-tested.


![ezgif-372cdcb9b7825df9](https://github.com/user-attachments/assets/4fd6c65a-50c9-448d-a664-da7293a802fc)



Toast notification for achievement trophy unlocked
Toast notification for level reach

<img width="800" height="622" alt="Screenshot 2026-02-22 151614" src="https://github.com/user-attachments/assets/59de2201-ac51-45ae-9306-c6aafab1e2cf" />

<img width="1200" height="684" alt="image" src="https://github.com/user-attachments/assets/9905176d-1ccc-46eb-a6b0-08ff24a8c150" />

<img width="550" height="423" alt="Screenshot 2026-02-22 152050" src="https://github.com/user-attachments/assets/fb062186-3f3b-44ef-b40b-6de695974073" />

<img width="939" height="657" alt="Screenshot 2026-02-22 151551" src="https://github.com/user-attachments/assets/f6adba7a-a68a-4cbc-84ad-343e984bf02c" />

over 3+ different themes to choose from.
and more...

<img width="861" height="598" alt="Screenshot 2026-02-22 152116" src="https://github.com/user-attachments/assets/228dd822-e93e-49e5-bae6-d1908817a159" />


This package is intended to be the **single distribution** for both:
- **Fresh installs** (no existing OverflowAchievement tables)
- **Upgrades** (previous versions already installed)

## Install
1. Download the **Release** Version for easy install (Do not download via **<> Code link**)
2. Copy the module folder into your FreeScout instance:
   - `Modules/OverflowAchievement`
3. Activate it in **Manage → Modules**.
4. (Optional) clear caches:
   - `php artisan cache:clear`

## PostgreSQL
The migration that hardens hot-path indexes is designed to be **idempotent** on PostgreSQL (uses `CREATE INDEX IF NOT EXISTS` and runs outside a transaction) to prevent the classic “transaction aborted” cascade. Runtime database support still needs release verification on the exact PostgreSQL version in use; see **Database compatibility** below.


## Localization

### Quote coverage note

Built-in quote text is fully localized for `en` and `fr`.
For `es`, `de`, `it`, `nl`, `pl`, and `pt_BR`, the quote library currently falls back to English until native quote packs are added.

### Architecture notes

A short architecture map lives in `docs/ARCHITECTURE.md`.

Built-in achievement titles/descriptions, trigger labels, and core UI strings are currently shipped for:
- English (`en`)
- French (`fr`)
- Spanish (`es`)
- German (`de`)
- Italian (`it`)
- Dutch (`nl`)
- Polish (`pl`)
- Portuguese (Brazil) (`pt_BR`, with `pt` alias)

Note: the new locale packs include English fallback quote libraries for now, so quote text remains readable everywhere while full per-locale quote translation can be expanded incrementally.

## Compatibility With Freescout Module(s)
- Teams Module
- Custom Field Module

## Requirements

- FreeScout 1.8.205 or newer (`requiredAppVersion` in `module.json`).
- Module source is written to the project's PHP 7.1 syntax floor and Laravel 5.5 APIs.
- JavaScript uses FreeScout's jQuery/Bootstrap 3 stack; no Node/npm build is required at runtime.

## Configuration and permissions

- Agents can view their own progress, trophies, and the leaderboard when those surfaces are enabled.
- Achievement/settings administration, user reset/test actions, icon uploads, and level repair are admin-only.
- Reward mutations re-check the real FreeScout user and mailbox scope before conversation-related awards; posted IDs are never treated as proof of scope.

## Custom trophy icons

Uploaded custom icons are stored on FreeScout's public storage disk under `overflowachievement/icons` and are referenced through `/storage/...`. Keep the normal FreeScout public-storage link available and include `storage/app/public` in backups.

Bundled icon-pack choices are stored by filename, so they remain subdirectory-safe. Older `fa-*` icon values are kept for data compatibility and are rendered using a deterministic bundled-image fallback; Font Awesome is not required.

## Database compatibility

The module is designed to use portable Laravel query/schema APIs and contains PostgreSQL-specific index hardening where needed. **Database compatibility must still be verified on the exact release environment before claiming a tested PostgreSQL, MySQL, or MariaDB matrix.** This source tree was not runtime-tested against a database as part of the current source-hardening pass.

## Upgrade and rollback

Before upgrading a production installation, back up the `overflowachievement_*` tables, `storage/app/public/overflowachievement`, and any legacy `Modules/OverflowAchievement/Public/icons/custom` directory if custom icons were uploaded by an older release. Released migrations are preserved rather than rewritten. Some historical normalization migrations are intentionally forward-oriented, so a package downgrade should use a database backup/forward fix instead of assuming every old data normalization can be losslessly reversed.

## Uninstall

Deactivate the module first. If you also want to remove historical gamification data, back up and then remove the module-owned `overflowachievement_*` tables and `storage/app/public/overflowachievement` custom-icon directory. FreeScout core tables are not modified by this module.

## Release verification

For a production release, verify activation and upgrade on the target FreeScout/PHP/database versions, exercise concurrent/repeated reward events, undo an outbound conversation and confirm no proactive-conversation XP remains, test admin reset/repair permissions, and run the user UI at mobile and desktop widths. Missing runtime environments are not treated as a pass.
