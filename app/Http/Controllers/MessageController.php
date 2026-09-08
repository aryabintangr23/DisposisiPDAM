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
     * Menampilkan daftar pesan terkirim (notifikasi disposisi surat).
     * Bisa difilter per arah surat (masuk/keluar) lewat query string "arah" —
     * tetap di halaman Pesan, tidak berpindah ke halaman Surat.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $arah = $request->query('arah');
        if (! in_array($arah, [ArahSurat::Masuk->value, ArahSurat::Keluar->value], true)) {
            $arah = null;
        }

        $pesan = Message::terkirimUntuk($user)
            ->with(['pengirim', 'penerima', 'surat'])
            ->when($arah, fn ($q) => $q->whereHas('surat', fn ($q2) => $q2->where('arah_surat', $arah)))
            ->latest()
            ->paginate(15);

        return view('messages.index', compact('pesan', 'arah'));
    }

    /**
     * Menampilkan daftar pesan di tempat sampah.
     */
    public function sampah(): View
    {
        $user = auth()->user();

        // Mengambil pesan di tempat sampah
        $messages = Message::sampahUntuk($user)
            ->with(['pengirim', 'penerima', 'surat'])
            ->latest()
            ->paginate(15);

        // DIPERBAIKI: Mengembalikan view messages.sampah dengan variabel $messages
        return view('messages.sampah', compact('messages'));
    }

    /**
     * Memindahkan pesan yang dipilih ke tempat sampah.
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
     * Memulihkan pesan dari tempat sampah.
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
     * Menghapus pesan secara permanen dari database.
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
     * Menampilkan detail pesan. Route: GET /pesan/{pesan} (name: pesan.show).
     * Parameter route bernama "pesan" sehingga di-bind ke Message $pesan.
     */
    public function show(Message $pesan): View
    {
        $user = auth()->user();

        // Hanya pengirim atau penerima pesan ini yang boleh membukanya.
        abort_unless(
            $pesan->dikirimOleh($user) || $pesan->diterimaOleh($user),
            403,
            'Anda tidak memiliki akses ke pesan ini.'
        );

        // Saat penerima membuka pesan, tandai otomatis sebagai sudah dibaca
        // supaya notif angka pesan belum dibaca di navbar langsung berkurang.
        if ($pesan->diterimaOleh($user)) {
            $pesan->tandaiSudahDibaca();
        }

        $pesan->load(['pengirim', 'penerima', 'surat']);

        return view('messages.show', compact('pesan'));
    }
}
