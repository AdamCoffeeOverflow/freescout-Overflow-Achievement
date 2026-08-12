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

## Reward mutation and concurrency contract

All module reward mutations for a real user use one lock order:

1. lock `App\\User` by `users.id` with `FOR UPDATE`;
2. lock or create that user's `overflowachievement_user_stats` row;
3. apply dedupe and daily-cap checks while the lock is held;
4. persist the XP event/stat changes and commit;
5. evaluate candidate trophy unlocks in a short follow-up transaction that locks the same user, locks the achievement definition, re-reads current stats, and inserts only if the threshold is still satisfied;
6. award any trophy XP bonus through the same reward mutation path, then let UI polling/toasts observe durable state.

Admin reset, test unlock, and level repair acquire the same core-user lock before changing module-owned user state. This gives the first-ever award a real serialization point even before a stats row exists. Achievement deletion also locks the definition row and is refused once unlock history exists, so deletion cannot race a new unlock into an orphaned state.

## Core hook contracts used by rewards

Verified against FreeScout 1.8.233 / `9492779dfc83fde23073f7faa7d0d44700581f15` during the 2.0.14 hardening pass. Exact upstream source remains authoritative on upgrade.

- `conversation.created_by_user` — action, `($conversation, $thread)`; delayed final hook after the undo window. Used for proactive-conversation XP so an undone send is not rewarded.
- `conversation.user_forwarded` — action, `($conversation, $thread, $forwarded_conversation, $forwarded_thread)`.
- `conversation.view.finish` — action, `($conversation_id, $user_id, $seconds)` from the conversation-viewer check command.
- `customer.merged` — action, `($customer, $customer2, $user)`.
- `attachment.created` — action receives the attachment; uploader attribution uses core `Attachment::$user_id`.
- `customer.created` / `customer.updated` — the Customer model carries no creating/updating user ID. The module awards these only when an authenticated agent is present and does not guess an actor for system/inbound changes.

Other registered reward hooks retain the argument contracts documented in the module provider and should be rechecked against exact core source whenever `requiredAppVersion` or the target FreeScout release moves.

## Authorization boundaries

- Personal progress, trophy state, polling, and mark-seen operations are always scoped to the authenticated user on the server.
- Reward hooks reload the acting user and conversation and require current mailbox access before conversation-scoped XP is awarded; inactive/deleted/robot actors are excluded.
- The leaderboard is global for administrators. For normal agents, the server derives visible users from `mailbox_user` membership in mailboxes returned by the authenticated user's `mailboxesIdsCanView()` plus the user themself. Agent email addresses are not used as leaderboard display fallbacks.
- Achievement administration, reset, test, repair, and icon operations require an authenticated administrator and validate target IDs before mutation.
