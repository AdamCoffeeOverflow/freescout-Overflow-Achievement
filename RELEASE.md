# Release process

1. Update `module.json` version.
2. Run CI locally:
   - `composer lint`
   - `composer phpstan`
   - `composer psalm`
   - `composer cs:check`
   - `composer rector:dry`
3. Tag and create GitHub release.
4. Attach the `*_release_*.zip` artifact (without `.github/`, tooling config, or `vendor/`).
