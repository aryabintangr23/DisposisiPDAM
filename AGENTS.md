# AGENTS.md

Aplikasi disposisi surat-menyurat internal PDAM. Laravel 13 monolith, PHP ^8.3 (native enums), MySQL, Blade + Vite, PDF via `barryvdh/laravel-dompdf`. All UI/domain text is Indonesian.

## Don't edit the wrong files

- Composer maps `App\` → `app/` (see `composer.json`), so any PHP file at the repo root is dead code — an earlier `Services/DisposisiRuleService.php` lived there and was silently not autoloaded. Service classes live in `app/Services/`; always edit there.
- See `README-tahap5.md` for the frozen domain design decisions (they are the source of truth for business rules).

## Domain model (Indonesian)

- `User` uses column **`nama`** (not `name`) + `role_id`; helper methods `isStaff()`, `isKabag()`, `isDirektur()`.
- `Role.nama_role` values: `staff_umum`, `kabag_umum`, `direktur`, `admin`. The `admin` role (not in the flow matrix) exists only for user management via `/pengguna` routes, guarded by `EnsureAdmin` → `User::isAdmin()`.
- Native enums in `app/Enums`: `ArahSurat`, `StatusSurat`, `Prioritas`, `StatusDisposisi`. `Prioritas::batasHari()` uses **calendar days** (not workdays); `TungguPetunjuk` returns `null` (no deadline).
- Centralize all domain rules in `app/Services/DisposisiRuleService`: valid flow matrix (only Staff→Kabag, Kabag→Staff/Direktur, Direktur→Kabag); only **Staff** may mark a disposisi `selesai`; deadline calc; `bolehSetKeputusan()`. Controllers `abort_unless(...)` against it rather than duplicating rules.
- Gates are registered in `app/Providers/AuthServiceProvider.php` (`kirim-disposisi`, `selesaikan-disposisi`); controllers also check rules directly.
- Data cakupan (daftar surat, akses edit/hapus, dashboard) dibagi per **role**, bukan per akun: `User::sameRoleAs()`, `SuratController::scopeSuratUntukUser()` / `authorizeAkses()`. Dua akun berrole sama melihat & memproses data yang sama persis. Tidak ada kepemilikan per-user untuk surat.
- Modul kedua selain surat: pesan internal (`Message`, route `pesan.*`), model inbox dengan soft-delete per sisi (`deleted_by_sender_at` / `deleted_by_receiver_at`); pesan dihapus permanen saat kedua pihak menghapus.

## Route gotcha

`/surat/sampah` and `/pesan/sampah` (and their hapus/pulihkan sub-routes) **must be declared before** `/surat/{surat}` / `/pesan/{pesan}`, otherwise route model binding treats `sampah` as an ID → 404. Already ordered correctly in `routes/web.php` — preserve that order when adding routes.

## Commands

- Migrate + seed: `php artisan migrate` then `php artisan db:seed` (seeders: `RoleSeeder` — the 4 roles — `UserSeeder`, `DatabaseSeeder`).
- Lampiran preview needs the storage symlink: `php artisan storage:link` (runs once; without it `Storage::url()` → `/storage/...` is 404).
- Assets (frontend, Vite): `npm run dev` / `npm run build`.
- Formatting: `vendor/bin/pint` (repo has no config override; default PSR-12).
- PHP CS fixes via Pint are the only lint; there is no separate typecheck step.

## Tests

`tests/` only has the default `ExampleTest` (Unit/Feature); phpunit.xml has **no** sqlite overrides, so tests run against the configured MySQL. Set up a DB before running `vendor/bin/phpunit`.
