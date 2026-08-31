# Tahap 5 — Struktur Database Final (Migration, Model, Enum, Business Rule)

## Cara pakai
Salin folder `database/migrations`, `app/Models`, `app/Enums`, `app/Services` ini ke dalam project Anda di `D:\DisposisiPDAM\Disposisi`, timpa `app/Models/User.php` bawaan Laravel dengan versi ini, lalu jalankan:

```
php artisan migrate
```

Setelah migrate, buat seeder untuk mengisi 3 role awal (`staff_umum`, `kabag_umum`, `direktur`) — beri tahu saya kalau Anda mau saya buatkan `RoleSeeder`-nya di iterasi berikutnya.

## Keputusan yang sudah dikunci sesuai konfirmasi Anda
1. `arah_surat` dipisah dari `jenis_surat` — kolom baru di tabel `surat`.
2. `tujuan_surat` ditambahkan untuk surat keluar.
3. Alur dianggap linear — **tidak** ditambahkan `disposisi_induk_id`, sesuai prinsip tidak overengineering.
4. Hanya **Staff** yang boleh menandai status disposisi menjadi `selesai` — logikanya ada di `DisposisiRuleService::bolehMenyelesaikan()`.
5. Status surat dipisah menurut arah — lihat `StatusSurat::untukArah()`.
6. Nomor agenda tetap input manual, **tidak** diberi `unique` constraint di database (hanya index biasa untuk pencarian), karena berpotensi human error. Rekomendasi saya: validasi di level Form Request sebagai peringatan "kemungkinan duplikat", bukan blokir keras — beri tahu saya kalau Anda ingin ini diperketat jadi unique per tahun.
7. Perhitungan `batas_waktu` menggunakan **hari kalender** — ada di `Prioritas::batasHari()` dan `DisposisiRuleService::hitungBatasWaktu()`.
8. Tipe file lampiran dibatasi **PDF saja** — validasi ini akan diterapkan di Form Request saat tahap upload file (belum dibuat di iterasi ini, lihat "Belum dikerjakan" di bawah).

## Satu hal yang masih perlu Anda putuskan (baru muncul setelah jawaban Anda)

Jawaban Anda soal status surat (poin 5) menjelaskan status **peninjauan awal** surat: diterima/ditolak/perlu revisi. Tapi requirement awal juga punya status disposisi `selesai` yang menandakan satu rangkaian disposisi benar-benar tuntas.

Pertanyaannya: apakah **status surat** dan **status disposisi terakhir** ini dua hal yang berjalan independen (surat bisa berstatus "diterima" sejak awal, terlepas dari disposisinya masih berjalan atau sudah selesai), atau status surat seharusnya otomatis mengikuti status disposisi terakhirnya?

Untuk sekarang saya asumsikan **independen** (status surat = hasil peninjauan konten surat, status disposisi = kondisi proses pengirimannya) karena itu paling sesuai dengan definisi yang sudah Anda berikan untuk masing-masing. Tapi ini **perlu dikonfirmasi** sebelum saya buat controller yang mengubah status surat, supaya tidak salah asumsi soal kapan status surat ikut berubah otomatis.

## Yang sudah selesai di iterasi ini
- Migration: `roles`, `users` (+`role_id`, rename `name`→`nama`), `surat`, `lampiran`, `disposisi`
- Enum PHP 8.3 native: `ArahSurat`, `StatusSurat`, `Prioritas`, `StatusDisposisi`
- Model + relasi Eloquent: `Role`, `User`, `Surat`, `Lampiran`, `Disposisi`
- `DisposisiRuleService`: matriks arah yang sah + perhitungan batas waktu + aturan siapa boleh menyelesaikan

## Belum dikerjakan (sengaja, bukan lompatan)
Middleware/Policy otorisasi, controller, route, Blade view, validasi form, upload file, preview & generate PDF, kirim email, dan testing **belum** saya buat di iterasi ini. Ini bukan berarti saya menahan, tapi karena men-dump seluruh modul itu sekaligus dalam satu balasan berisiko menghasilkan kode yang tidak konsisten satu sama lain dan sulit Anda review baris per baris — bertentangan dengan prinsip "mudah dipelihara" yang Anda tetapkan sendiri.

Kalau Anda konfirmasi poin di atas (status surat vs status disposisi), saya lanjutkan langsung ke Policy + Controller + Route di balasan berikutnya.
