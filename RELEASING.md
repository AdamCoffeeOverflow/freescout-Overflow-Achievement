# Releasing OverflowAchievement

This repository is a FreeScout module.

## Branching

- `develop`: active development
- `main`: stable, tagged releases
- `release/vX.Y.Z`: short-lived release preparation
- `hotfix/vX.Y.(Z+1)`: emergency fixes off `main`

## Release steps

1. Create a release branch from `develop`:
   ```bash
   git checkout develop
   git checkout -b release/vX.Y.Z
   ```

2. Bump version in `module.json` and update `CHANGELOG.md`.

3. Ensure `LICENSE` exists and README is up to date.

4. Merge into `main`, tag, and push:
   ```bash
   git checkout main
   git merge --no-ff release/vX.Y.Z
   git tag -a vX.Y.Z -m "OverflowAchievement vX.Y.Z"
   git push origin main --tags
   ```

5. Merge back into `develop`:
   ```bash
   git checkout develop
   git merge --no-ff release/vX.Y.Z
   git push origin develop
   ```

6. Delete the release branch.

## GitHub Release

Create a GitHub Release from the tag `vX.Y.Z` and paste the corresponding `CHANGELOG.md` section.
