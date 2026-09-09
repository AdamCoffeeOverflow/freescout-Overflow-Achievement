# Changelog

## 2.0.15 — 2026-09-09

### Fixed

- Avoids the PHP 8 `method_exists(null, ...)` fatal when saving an empty mailbox quote-rules setting through Laravel 5.5 validation.
- Makes achievement unlock toasts smaller and tones down the oversized rarity bloom/glow.
- Repairs the historical `2026_02_20_000007_more_triggers_and_counters.php` migration class-name collision reported in issue #21 with a new append-only forward migration.
- Restores the seven missing `overflowachievement_user_stats` counters when absent: `replies_sent_count`, `customer_replies_count`, `pending_set_count`, `spam_marked_count`, `deleted_count`, `customers_merged_count`, and `focus_minutes`.
- Restores any missing built-in achievements for reply-sent, customer-reply, pending, spam, delete, customer-merge, and focus-time triggers without duplicating existing keys.

### Upgrade safety

- The shipped `000007` migration is intentionally left unchanged. Version 2.0.15 adds a new idempotent repair migration so fresh installs, already-affected installs, partially repaired installs, and installs where `000007` previously ran correctly all converge on the same schema/data state.
- The repair migration is forward-fix only and does not drop counters or achievement progress on rollback.

### Verification status

- Source and migration-class resolution were inspected against the repository's pinned Laravel 5.5 migration behavior.
- Live fresh-install and upgrade migration execution across the supported database matrix remains runtime verification for the target installation.
