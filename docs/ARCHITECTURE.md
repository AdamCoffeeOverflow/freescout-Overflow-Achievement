# OverflowAchievement architecture

## Core responsibilities

- `Providers/OverflowAchievementServiceProvider.php`
  - module registration only
  - delegates settings, assets, menu, and event hooks to provider traits

- `Providers/Concerns/*`
  - `RegistersOverflowAchievementSettings`: Settings > Achievement integration
  - `RegistersOverflowAchievementAssets`: frontend asset loading
  - `RegistersOverflowAchievementMenu`: menu and navbar UI
  - `RegistersOverflowAchievementHooks`: event listeners that feed the reward engine

- `Services/RewardEngine.php`
  - XP awards, dedupe rules, stat updates, and achievement evaluation

- `Services/UserProgressService.php`
  - read-oriented user stat access and progress snapshots
  - avoids writing rows during normal read-only page loads

- `Services/RuntimeBootstrapService.php`
  - frontend bootstrap payload (UI options, strings, trigger labels)

- `Services/QuoteService.php`
  - built-in quote selection and mailbox-aware quote assignment

- `Services/LevelService.php`
  - level curve and XP-to-level calculations

- `Support/*Catalog.php`
  - runtime localization and canonicalization helpers
  - `AchievementCatalog`, `TriggerCatalog`, `QuoteCatalog`, `LocaleCatalog`

- `Entities/*`
  - Eloquent models and display helpers only

## Frontend

- `Public/js/module.js`
  - runtime bootstrap fetch
  - toast queue / polling
  - achievement modal
  - settings lazy-tab behavior

- `Public/css/module.css`
  - shared module UI styles

## Views

- `Resources/views/settings/index.blade.php`
  - tab shell only

- `Resources/views/settings/tabs/*`
  - tab-specific settings forms

- `Resources/views/settings/index_manage.blade.php`
  - admin trophy manager, lazy-loaded

## Current maintenance notes

- `RewardEngine.php` and `Public/js/module.js` remain the largest files in the module.
- They are stable, but still the best candidates for a future behavior-preserving split.
