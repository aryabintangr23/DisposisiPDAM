<?php

namespace App\Http\Controllers;

use App\Enums\ArahSurat;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Tampilkan daftar pesan (masuk/keluar).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $kotak = $request->query('kotak', 'masuk');
        if (! in_array($kotak, ['masuk', 'keluar'], true)) {
            $kotak = 'masuk';
        }

        $arah = $request->query('arah');
        if (! in_array($arah, [ArahSurat::Masuk->value, ArahSurat::Keluar->value], true)) {
            $arah = null;
        }

        $query = $kotak === 'keluar'
            ? Message::terkirimUntuk($user)
            : Message::masukUntuk($user);

        $pesan = $query
            ->with(['pengirim', 'penerima', 'surat'])
            ->when($arah, fn ($q) => $q->whereHas('surat', fn ($q2) => $q2->where('arah_surat', $arah)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('messages.index', compact('pesan', 'arah', 'kotak'));
    }

    /**
     * Tampilkan pesan di tempat sampah.
     */
    public function sampah(): View
    {
        $user = auth()->user();

        $messages = Message::sampahUntuk($user)
            ->with(['pengirim', 'penerima', 'surat'])
            ->latest()
            ->paginate(15);

        return view('messages.sampah', compact('messages'));
    }

    /**
     * Tandai pesan terpilih sebagai sudah dibaca (khusus pesan masuk).
     */
    public function tandaiDibaca(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:messages,id',
        ]);

        $user = auth()->user();
        $messages = Message::whereIn('id', $request->ids)->get();

        $count = 0;
        foreach ($messages as $message) {
            if ($message->diterimaOleh($user) && ! $message->is_read) {
                $message->tandaiSudahDibaca();
                $count++;
            }
        }

        return redirect()->back()->with('status', $count.' pesan ditandai telah dibaca.');
    }

    /**
     * Pindahkan pesan ke tempat sampah.
     */
    public function hapus(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:messages,id',
        ]);

        $user = auth()->user();
        $messages = Message::whereIn('id', $request->ids)->get();

        $count = 0;
        foreach ($messages as $message) {
            $message->hapusUntuk($user);
            $count++;
        }

        return redirect()->back()->with('status', $count.' pesan dipindahkan ke tempat sampah.');
    }

    /**
     * Pulihkan pesan dari tempat sampah.
     */
    public function pulihkan(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:messages,id',
        ]);

        $user = auth()->user();
        $messages = Message::whereIn('id', $request->ids)->get();

        foreach ($messages as $message) {
            $message->pulihkanUntuk($user);
        }

        return redirect()->back()->with('status', count($messages).' pesan berhasil dipulihkan.');
    }

    /**
     * Hapus pesan secara permanen.
     */
    public function hapusPermanen(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:messages,id',
        ]);

        $user = auth()->user();

        Message::whereIn('id', $request->ids)
            ->sampahUntuk($user)
            ->delete();

        return redirect()->back()->with('status', count($request->ids).' pesan dihapus permanen.');
    }

    /**
     * Tampilkan detail pesan.
     */
    public function show(Message $pesan): View
    {
        $user = auth()->user();

        // Cek hak akses pengirim / penerima
        abort_unless(
            $pesan->dikirimOleh($user) || $pesan->diterimaOleh($user),
            403,
            'Anda tidak memiliki akses ke pesan ini.'
        );

        // Otomatis tandai dibaca jika yang membuka adalah penerima
        if ($pesan->diterimaOleh($user)) {
            $pesan->tandaiSudahDibaca();
        }

        $pesan->load(['pengirim', 'penerima', 'surat']);

        return view('messages.show', compact('pesan'));
    }
}