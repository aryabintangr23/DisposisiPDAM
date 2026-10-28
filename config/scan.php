<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Scan Surat (OCR lokal)
    |--------------------------------------------------------------------------
    |
    | Fitur ini membaca foto/PDF surat memakai Tesseract yang terpasang di
    | server, lalu mengekstrak field form dengan aturan NLP. Tidak ada data
    | yang dikirim ke layanan AI cloud.
    |
    */

    'enabled' => env('SCAN_SURAT_ENABLED', true),

    'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),

    // "ind" untuk Bahasa Indonesia, "eng" untuk angka/istilah asing.
    // Kalau data bahasa "ind" tidak tersedia, pembaca otomatis mencoba "eng".
    'languages' => env('TESSERACT_LANGS', 'ind+eng'),

    // Folder traineddata alternatif. Kosongkan untuk memakai folder bawaan
    // Tesseract (sebelah binary). Berguna bila data bahasa dipasang di
    // direktori yang bisa ditulis user, misalnya di Windows tanpa admin.
    // Folder ini juga harus berisi subfolder "configs" dan "tessconfigs"
    // (untuk output TSV), bukan hanya berkas *.traineddata.
    'tessdata_dir' => env('TESSERACT_TESSDATA_DIR'),

    'psm' => (int) env('TESSERACT_PSM', 3),

    'timeout' => (int) env('SCAN_SURAT_TIMEOUT', 60),

    // Konversi halaman pertama PDF menjadi gambar. Butuh poppler (pdftoppm)
    // atau ekstensi Imagick di server.
    'pdftoppm_path' => env('PDFTOPPM_PATH', 'pdftoppm'),

    // Berkas sementara disimpan di disk privat, bukan disk publik.
    'temp_disk' => env('SCAN_SURAT_TEMP_DISK', 'local'),
    'temp_prefix' => 'scan-surat',

    // Batas waktu token scan sebelum dibersihkan otomatis.
    'expires_minutes' => (int) env('SCAN_SURAT_EXPIRES', 120),

    // Batas jumlah scan per menit per user (dipakai route middleware).
    'throttle_per_minute' => (int) env('SCAN_SURAT_THROTTLE', 10),

    'max_upload_kb' => (int) env('SCAN_SURAT_MAX_KB', 20480),
];
