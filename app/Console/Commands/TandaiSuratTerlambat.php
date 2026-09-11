<?php

namespace App\Console\Commands;

use App\Enums\StatusSurat;
use App\Models\Surat;
use App\Services\DisposisiRuleService;
use Illuminate\Console\Command;

class TandaiSuratTerlambat extends Command
{
    protected $signature = 'surat:tandai-terlambat';

    protected $description = 'Tandai surat yang sudah melewati batas waktu prioritas disposisinya sebagai "Ditolak" secara otomatis.';

    public function handle(DisposisiRuleService $rule): int
    {
        $suratAktif = Surat::whereNotIn('status', [StatusSurat::Diterima->value, StatusSurat::Ditolak->value])
            ->with('disposisi')
            ->get();

        $jumlahDitolak = 0;

        foreach ($suratAktif as $surat) {
            if ($rule->tandaiOtomatisJikaTerlambat($surat)) {
                $jumlahDitolak++;
            }
        }

        $this->info("Selesai. {$jumlahDitolak} surat ditandai \"Ditolak\" otomatis karena melewati batas waktu prioritas.");

        return self::SUCCESS;
    }
}
