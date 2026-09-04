# AGENTS.md

Aplikasi disposisi surat-menyurat internal PDAM. Laravel 13 monolith, PHP ^8.3 (native enums), MySQL, Blade + Vite, PDF via `barryvdh/laravel-dompdf`. All UI/domain text is Indonesian.

## Don't edit the wrong files

- There is a **stale duplicate** of the rule service at the repo root: `Services/DisposisiRuleService.php`. It declares `namespace App\Services`, but Composer maps `App\` → `app/`, so this root file is **not autoloaded and does nothing**. The live one is `app/Services/DisposisiRuleService.php` (it has `bolehSetKeputusan()`, which the root copy lacks and which `SuratController`/`DisposisiController` call). Never edit anything under the root `Services/` folder; change `app/Services/`.
- See `README-tahap5.md` for the frozen domain design decisions (they are the source of truth for business rules).

## Domain model (Indonesian)

- `User` uses column **`nama`** (not `name`) + `role_id`; helper methods `isStaff()`, `isKabag()`, `isDirektur()`.
- `Role.nama_role` values: `staff_umum`, `kabag_umum`, `direktur`.
- Native enums in `app/Enums`: `ArahSurat`, `StatusSurat`, `Prioritas`, `StatusDisposisi`. `Prioritas::batasHari()` uses **calendar days** (not workdays); `TungguPetunjuk` returns `null` (no deadline).
- Centralize all domain rules in `app/Services/DisposiRuleService` (the `DisposisiRuleService`): valid flow matrix (only Staff→Kabag, Kabag→Staff/Direktur, Direktur→Kabag); only **Staff** may mark a disposisi `selesai`; deadline calc; `bolehSetKeputusan()`. Controllers `abort_unless(...)` against it rather than duplicating rules.
- Gates are registered in `app/Providers/AuthServiceProvider.php` (`kirim-disposisi`, `selesaikan-disposisi`); controllers also check rules directly.

## Route gotcha

`/surat/sampah` and `/pesan/sampah` (and their hapus/pulihkan sub-routes) **must be declared before** `/surat/{surat}` / `/pesan/{pesan}`, otherwise route model binding treats `sampah` as an ID → 404. Already ordered correctly in `routes/web.php` — preserve that order when adding routes.

## Commands

- Migrate + seed: `php artisan migrate` then `php artisan db:seed` (seeders: `RoleSeeder` — the 3 roles — `UserSeeder`, `DatabaseSeeder`).
- Assets (frontend, Vite): `npm run dev` / `npm run build`.
- Formatting: `vendor/bin/pint` (repo has no config override; default PSR-12).
- PHP CS fixes via Pint are the only lint; there is no separate typecheck step.

## Tests

`tests/` only has the default `ExampleTest` (Unit/Feature); phpunit.xml has **no** sqlite overrides, so tests run against the configured MySQL. Set up a DB before running `vendor/bin/phpunit`.
