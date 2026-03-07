# Contributing

Thanks for considering a contribution!

## Development

- Target FreeScout `>= 1.8.205`.
- Keep PHP compatible with FreeScout's baseline (Laravel 5.5).
- Avoid Node/Vue builds; use plain JS/CSS (per FreeScout module guidance).

## Pull requests

- Keep changes focused.
- Add/adjust translations if you change UI text.
- Run a quick syntax check locally:
  ```bash
  find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
  ```

## Security

If you discover a security issue, please open a private report (GitHub Security Advisories) if enabled,
or email the maintainer.
