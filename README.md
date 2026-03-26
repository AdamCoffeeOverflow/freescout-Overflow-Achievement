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


![ezgif-372cdcb9b7825df9](https://github.com/user-attachments/assets/4fd6c65a-50c9-448d-a664-da7293a802fc)



Toast notification for achievement trophy unlocked
Toast notification for level reach

<img width="800" height="622" alt="Screenshot 2026-02-22 151614" src="https://github.com/user-attachments/assets/59de2201-ac51-45ae-9306-c6aafab1e2cf" />

<img width="1200" height="684" alt="image" src="https://github.com/user-attachments/assets/9905176d-1ccc-46eb-a6b0-08ff24a8c150" />

<img width="250" height="423" alt="Screenshot 2026-02-22 152050" src="https://github.com/user-attachments/assets/fb062186-3f3b-44ef-b40b-6de695974073" />

<img width="439" height="657" alt="Screenshot 2026-02-22 151551" src="https://github.com/user-attachments/assets/f6adba7a-a68a-4cbc-84ad-343e984bf02c" />

over 3+ different themes to choose from.
and more...

<img width="761" height="798" alt="Screenshot 2026-02-22 152116" src="https://github.com/user-attachments/assets/228dd822-e93e-49e5-bae6-d1908817a159" />


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
PostgreSQL is supported.

The migration that hardens hot-path indexes is designed to be **idempotent** on PostgreSQL (uses `CREATE INDEX IF NOT EXISTS` and runs outside a transaction) to prevent the classic “transaction aborted” cascade.




## Localization
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
