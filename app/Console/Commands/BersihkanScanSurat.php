<?php

namespace App\Console\Commands;

use App\Services\ScanSuratService;
use Illuminate\Console\Command;

class BersihkanScanSurat extends Command
{
    protected $signature = 'scan:bersihkan';

    protected $description = 'Hapus berkas dan data scan surat yang sudah kedaluwarsa';

    public function handle(ScanSuratService $service): int
    {
        $jumlah = $service->bersihkanKedaluwarsa();

        $this->info("{$jumlah} data scan surat kedaluwarsa dibersihkan.");

        return self::SUCCESS;
    }
}
