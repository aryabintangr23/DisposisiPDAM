<?php

namespace App\Http\Controllers;

use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use App\Http\Requests\StoreDisposisiRequest;
use App\Models\Disposisi;
use App\Models\Surat;
use App\Models\User;
use App\Services\DisposisiRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DisposisiController extends Controller
{
    public function store(StoreDisposisiRequest $request, Surat $surat, DisposisiRuleService $rule): RedirectResponse
    {
        $data = $request->validated();
        $pengirim = $request->user();
        $penerima = User::findOrFail($data['penerima_id']);

        abort_unless($rule->bolehDisposisi($pengirim, $penerima), 403, 'Tujuan disposisi tidak sesuai alur yang diizinkan.');

        $prioritas = Prioritas::from($data['prioritas']);
        $tanggalDisposisi = now();

        $surat->disposisi()->create([
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'tanggal_disposisi' => $tanggalDisposisi,
            'prioritas' => $prioritas,
            'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
            'instruksi' => $data['instruksi'] ?? null,
            'status' => StatusDisposisi::Terkirim,
        ]);

        return redirect()->route('surat.show', $surat)->with('status', 'Disposisi berhasil dikirim.');
    }

    public function selesaikan(Request $request, Surat $surat, Disposisi $disposisi, DisposisiRuleService $rule): RedirectResponse
    {
        abort_unless($rule->bolehMenyelesaikan($request->user()), 403, 'Hanya Staff yang boleh menandai disposisi selesai.');
        abort_unless($disposisi->surat_id === $surat->id, 404);
        abort_unless($disposisi->penerima_id === $request->user()->id, 403, 'Hanya penerima disposisi ini yang boleh menandainya selesai.');

        $disposisi->update(['status' => StatusDisposisi::Selesai]);

        return back()->with('status', 'Disposisi ditandai selesai.');
    }
}
