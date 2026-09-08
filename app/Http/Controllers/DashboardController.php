<?php

namespace App\Http\Controllers;

use App\Enums\ArahSurat;
use App\Enums\StatusSurat;
use App\Models\Disposisi;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Halaman Dashboard. Statistik (kartu & grafik) untuk Staff, Admin &
     * Kabag; Direktur hanya melihat halaman ini sebagai kotak peringatan
     * surat yang mendekati batas waktu prioritas.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $scope = fn () => Surat::untukRole($user);

        // Kotak peringatan: hanya untuk Kabag & Direktur.
        $disposisiMendekati = collect();
        if ($user->isKabag() || $user->isDirektur()) {
            $disposisiMendekati = Disposisi::query()
                ->whereIn('surat_id', $scope()->select('id'))
                ->mendekatiBatas()
                ->with(['surat', 'pengirim', 'penerima'])
                ->orderBy('batas_waktu')
                ->get()
                ->unique('surat_id')
                ->take(10);
        }

        // Statistik dashboard: Staff, Admin & Kabag (direktur tidak melihatnya).
        $lihatStatistik = ! $user->isDirektur();
        $jumlahSurat = $jumlahMasuk = $jumlahKeluar = 0;
        $statistikPerBulan = collect();
        $statistikStatus = collect();

        if ($lihatStatistik) {
            $jumlahSurat = $scope()->count();
            $jumlahMasuk = $scope()->where('arah_surat', ArahSurat::Masuk)->count();
            $jumlahKeluar = $scope()->where('arah_surat', ArahSurat::Keluar)->count();

            // Grafik jumlah surat masuk/keluar 6 bulan terakhir (termasuk bulan berjalan).
            $bulanIni = now()->startOfMonth();
            $statistikPerBulan = collect(range(5, 0))->map(function ($i) use ($bulanIni, $scope) {
                $awal = $bulanIni->copy()->subMonths($i);
                $akhir = $awal->copy()->endOfMonth();

                return [
                    'label' => $awal->translatedFormat('M Y'),
                    'masuk' => $scope()->where('arah_surat', ArahSurat::Masuk)
                        ->whereBetween('tanggal_surat', [$awal, $akhir])
                        ->count(),
                    'keluar' => $scope()->where('arah_surat', ArahSurat::Keluar)
                        ->whereBetween('tanggal_surat', [$awal, $akhir])
                        ->count(),
                ];
            });

            // Sebaran status surat (hanya status yang punya data).
            $statistikStatus = collect(StatusSurat::cases())
                ->map(fn (StatusSurat $status) => [
                    'status' => $status,
                    'jumlah' => $scope()->where('status', $status->value)->count(),
                ])
                ->filter(fn (array $item) => $item['jumlah'] > 0)
                ->values();
        }

        return view('dashboard.index', compact('lihatStatistik', 'jumlahSurat', 'jumlahMasuk', 'jumlahKeluar', 'statistikPerBulan', 'statistikStatus', 'disposisiMendekati'));
    }
}
