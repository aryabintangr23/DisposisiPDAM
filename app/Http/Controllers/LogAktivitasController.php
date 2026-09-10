<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    /**
     * Tampilkan halaman log aktivitas (khusus admin).
     */
    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $query = LogAktivitas::with('user.role')->latest('id');
        $this->terapkanFilter($query, $filters);

        $logs = $query->paginate(20)->withQueryString();

        $daftarAksi = LogAktivitas::query()
            ->select('aksi')
            ->distinct()
            ->orderBy('aksi')
            ->pluck('aksi');

        $daftarUser = User::orderBy('nama')->get();

        return view('log-aktivitas.index', compact('logs', 'daftarAksi', 'daftarUser', 'filters'));
    }

    /**
     * Endpoint AJAX untuk polling data log terbaru secara real-time.
     */
    public function data(Request $request): JsonResponse
    {
        $filters = $this->filtersFromRequest($request);
        $afterId = (int) $request->query('after', 0);

        $query = LogAktivitas::with('user.role')
            ->where('id', '>', $afterId)
            ->latest('id');

        $this->terapkanFilter($query, $filters);

        $logsBaru = $query->limit(30)->get();

        $html = $logsBaru->isEmpty()
            ? ''
            : view('log-aktivitas._baris', ['logs' => $logsBaru, 'sorot' => true])->render();

        return response()->json([
            'html' => $html,
            'jumlah_baru' => $logsBaru->count(),
            'last_id' => $logsBaru->max('id') ?? $afterId,
        ]);
    }

    private function filtersFromRequest(Request $request): array
    {
        $cari = trim((string) $request->query('cari', ''));

        return [
            'aksi' => $request->query('aksi') ?: null,
            'user_id' => $request->query('user_id') ?: null,
            'tanggal' => $request->query('tanggal') ?: null,
            'cari' => $cari !== '' ? $cari : null,
        ];
    }

    private function terapkanFilter(Builder $query, array $filters): void
    {
        if ($filters['aksi']) {
            $query->where('aksi', $filters['aksi']);
        }

        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['tanggal']) {
            $query->whereDate('created_at', $filters['tanggal']);
        }

        if ($filters['cari']) {
            $query->where('deskripsi', 'like', "%{$filters['cari']}%");
        }
    }
}