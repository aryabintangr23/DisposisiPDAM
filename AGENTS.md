# AGENTS.md

Aplikasi disposisi surat-menyurat internal PDAM. Laravel 13 monolith, PHP ^8.3 (native enums), MySQL, Blade + Vite, PDF via `barryvdh/laravel-dompdf`. All UI/domain text is Indonesian.

## Don't edit the wrong files

- Composer maps `App\` → `app/` (see `composer.json`), so any PHP file at the repo root is dead code — an earlier `Services/DisposisiRuleService.php` lived there and was silently not autoloaded. Service classes live in `app/Services/`; always edit there. Domain rules are frozen in this file (README-tahap5.md no longer exists).

## Domain model (Indonesian)

- `User` uses column **`nama`** (not `name`) + `role_id`; helper methods `isStaff()`, `isKasubag()`, `isKabag()`, `isDirektur()`.
- `Role.nama_role` values: `staff_umum`, `kasubag_umum`, `kabag_umum`, `direktur`, `admin`. `admin` sits outside the flow matrix: guard `admin` (`EnsureAdmin` → `isAdmin()`) protects `/pengguna` + `/log-aktivitas`, it sees all surats, but `MessageController` 403s it off `/pesan`.
- Native enums in `app/Enums`: `ArahSurat`, `StatusSurat`, `Prioritas`, `StatusDisposisi`. `Prioritas::batasHari()` uses **calendar days** (not workdays); `TungguPetunjuk` returns `null` (no deadline).
- Centralize all domain rules in `app/Services/DisposisiRuleService` (const `ALUR_SAH`): alur surat masuk = **Staff→Kasubag→(approve)→Kabag→(approve)→Direktur**; jalur paralel Kasubag/Kabag boleh kirim ke Staff/Kasubag/Kabag/Direktur sesuai matriks; `direktur => []` (Direktur tidak mengirim disposisi ke akun — target Keputusan berupa jabatan, lihat `keputusan`). Hanya **Staff** yang boleh menandai disposisi `selesai`, dan hanya disposisi yang mereka terima (`selesaikan` memeriksa `penerima_id === user`); deadline calc; `bolehSetKeputusan(User $user, string $keputusan)` (Direktur → `diterima`/`ditolak`; Kasubag **dan** Kabag → hanya `perlu_revisi`). Controllers `abort_unless(...)` against it rather than duplicating rules.
- Status surat: `baru` → `perlu_revisi` → `diterima`/`ditolak`. Surat berstatus `diterima` bersifat **final** — endpoint "kirim disposisi" diblokir server-side (`abort_if` di `DisposisiController::store` + form disembunyikan). Surat di luar `baru`/`perlu_revisi` tidak bisa diedit; saat `perlu_revisi`, edit hanya boleh selama disposisi terakhir masih di tangan Staff (`SuratController::authorizeEdit`).
- Dua alur review berjenjang yang sama berlaku untuk **Kasubag dan Kabag**: `reviewBaru` (surat status `baru`; Kasubag menerima dari Staff, Kabag menerima dari Kasubag) — "approve" mempertahankan `baru` dan meneruskan ke `roleBerikutnya()` (Kasubag→`kabag_umum`, Kabag→`direktur`), "revisi" mengembalikan ke **pembuat** surat (`$surat->pembuat ?? dispo terakhir->pengirim`) sebagai `perlu_revisi`; `reviewRevisi` (surat status `perlu_revisi`) — "Diterima" mengembalikan status ke **`baru`** (bukan `diterima`) supaya alur bisa lanjut dan meneruskan ke `roleBerikutnya()`, "Revisi" membiarkan `perlu_revisi`. `keputusan()` (hanya Direktur, surat `baru`) → `diterima`/`ditolak` (**final**), dengan target berupa **jabatan** bukan akun: disposisi balasan berisi `tujuan_jabatan` (dipilih dari dropdown tetap `TUJUAN_JABATAN`, divalidasi `Rule::in`, plus opsi "Lainnya" sentinel `JABATAN_LAINNYA` yang mewajibkan `tujuan_jabatan_lain` teks bebas) +`tujuan_bagian` bila `DisposisiRuleService::jabatanPerluBagian()` (jabatan "Kepala Unit"/"Kasubag") dengan `penerima_id` NULL; setelahnya surat diupdate final dan `Message` manual dikirim ke semua user yang terlibat (pengirim disposisi + pembuat). Ketiganya otomatis membuat disposisi balasan supaya terekam di riwayat, dan target akun dilengkapi server-side (`akunDenganRole()`), bukan pilihan bebas dari form; khusus `keputusan`, jabatan tujuan dipilih dari dropdown dan Kepala Unit/Kasubag wajib diberi `tujuan_bagian`. Jalur paralel: `DisposisiController::store` juga dipakai Kasubag/Kabag mengirim langsung ke Staff/Kasubag/Kabag/Direktur (penerima dari form) plus `keputusan_surat` opsional (`perlu_revisi`) — **Staff di endpoint ini selalu dikirim otomatis**: ke Kasubag (awal alur), atau saat `perlu_revisi` dan disposisi terakhir masih di tangannya, kembali ke pihak yang meminta revisi.
- `nomor_surat` ATAU `nomor_agenda` duplikat hanya memicu flash `warning` (`SuratController::tandaiJikaNomorSudahDipakai`), sengaja tanpa `unique` constraint; ada juga endpoint AJAX `surat/cek-nomor` untuk cek real-time.
- Overdue: `DisposisiRuleService::tandaiOtomatisJikaTerlambat()` menandai surat yang disposisi terakhirnya lewat `batas_waktu` sebagai `ditolak` — dipanggil lazy di `SuratController` (index/edit/show) dan oleh command terjadwal `php artisan surat:tandai-terlambat` (harian 00:05, terdaftar di `app/Console/Kernel.php`).
- Gates are registered in `app/Providers/AuthServiceProvider.php` (`kirim-disposisi`, `selesaikan-disposisi`); controllers also check rules directly.
- Data cakupan (daftar surat, akses edit/hapus, dashboard) dibagi per **role**, bukan per akun: `User::sameRoleAs()`, scope `Surat::scopeUntukRole()`, `SuratController::authorizeAkses()`. Dua akun berrole sama melihat & memproses data yang sama persis. Tidak ada kepemilikan per-user untuk surat. `Surat` memakai `SoftDeletes` (tempat sampah + pulihkan); `hapusPermanen` ikut menghapus file lampiran dari disk.
- Modul kedua selain surat: pesan internal (`Message`, route `pesan.*`), model inbox dengan soft-delete per sisi (`deleted_by_sender_at` / `deleted_by_receiver_at`); pesan dihapus permanen saat kedua pihak menghapus. **Tidak ada UI "tulis pesan"** — `Message` dibuat otomatis oleh hook `Disposisi::booted()` (event `created`) setiap disposisi baru dibuat, berisi ringkasan surat + instruksi; hook ini **di-skip saat `penerima_id` NULL**. Tapi alur `reviewBaru`/`reviewRevisi` **juga** membuat `Message` manual berisi status review — satu disposisi dari alur itu menghasilkan 2 pesan; jangan menambah kiriman `Message` manual lagi di flow baru yang menciptakan disposisi (hook sudah jalan). Pengecualian: `keputusan()` (disposisi ber-`penerima_id` NULL) justru **wajib** membuat `Message` manual ke semua pihak terlibat, karena hook otomatisnya dilewati.
- Semua endpoint yang mengubah data memanggil `LogAktivitas::catat($aksi, $deskripsi, $entitas, $entitasId)` dengan aksi kebab-case (`surat_dibuat`, `disposisi_dikirim`, `surat_keputusan_diterima`, `review_revisi_diterima`, `surat_ditolak_otomatis`); admin meninjau di `/log-aktivitas`. Ikuti pola ini di endpoint baru.

## Route gotcha

`/surat/sampah` and `/pesan/sampah` (and their hapus/pulihkan sub-routes) **must be declared before** `/surat/{surat}` / `/pesan/{pesan}`, otherwise route model binding treats `sampah` as an ID → 404. Already ordered correctly in `routes/web.php` — preserve that order when adding routes.

## Commands

- Migrate + seed: `php artisan migrate` then `php artisan db:seed` (seeders: `RoleSeeder` — the 5 roles — `UserSeeder`, `DatabaseSeeder`).
- Login lokal untuk manual smoke-test: `UserSeeder` membuat 5 akun demo — `staff@tirtagemilang.test`, `kasubag@tirtagemilang.test`, `kabag@tirtagemilang.test`, `direktur@tirtagemilang.test`, `admin@tirtagemilang.test`, semua password `password` (testing-only, jangan di-production).
- Lampiran preview needs the storage symlink: `php artisan storage:link` (runs once; without it `Storage::url()` → `/storage/...` is 404).
- Lampiran disimpan di disk `public` (`storage/app/public/lampiran`). Validasi mimes **berbeda antar form** (jangan disamakan diam-diam): `StoreSuratRequest` menerima `pdf,jpg,jpeg,heic`, `UpdateSuratRequest` menerima `pdf,jpg,jpeg,docx` — keduanya max 10MB/file.
- `DisposisiController::cetak()` sengaja menamai PDF `lembar-disposisi-{id}.pdf`: jangan diganti ke `nomor_surat` karena boleh berisi `/` dan `\` yang ditolak Windows.
- Assets (frontend, Vite): `npm run dev` / `npm run build`.
- Formatting: `vendor/bin/pint` (repo has no config override; default PSR-12).
- PHP CS fixes via Pint are the only lint; there is no separate typecheck step.

## Tests

`tests/` only has the default `ExampleTest` (Unit/Feature); phpunit.xml has **no** sqlite overrides, so tests run against the configured MySQL. Set up a DB before running `vendor/bin/phpunit`.
