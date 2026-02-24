# Changelog — OverflowAchievement

## [1.1.2] - 2026-02-23

Fixed compatibility issue with Team Module (Freescout Paid Module)
Fixed Recent Trophy listing endlessly the earned achievements by users - capped it to 10 recent instead.

## [1.1.0] — 2026-02-21

This release is a **structural + performance** cleanup focused on correctness, PostgreSQL safety, and a simpler settings experience.

### Added
- **PostgreSQL-safe index hardening** for the events hot paths (idempotent index creation; avoids aborted transactions).
- **Hot-path composite index** for daily caps and event counting:
  - `overflowachievement_events(user_id, event_type, created_at)`

### Changed
- **Settings UI redesign:** single-form, tabbed layout for module options (no cross-tab hidden “preserve” scaffolding).
- **Hook gating:** expensive hooks now short-circuit earlier when the module/feature/XP value is disabled.
- **Reward engine cap checks:** reduced DB work by avoiding expensive counting where possible.

### Fixed
- **Settings Appearance tab crash:** prevents `Undefined variable $toast_theme` by deriving defaults from the settings payload.

### Notes
- This package is intended to be the **single** distribution for both fresh installs and upgrades.
