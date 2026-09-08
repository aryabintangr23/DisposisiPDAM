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
     * Menampilkan daftar pesan (notifikasi disposisi surat).
     * Bisa difilter per kotak (masuk/keluar) lewat query string "kotak", dan
     * per arah surat (masuk/keluar) lewat query string "arah" — tetap di
     * halaman Pesan, tidak berpindah ke halaman Surat.
     *
     * DIPERBAIKI: sebelumnya index() selalu memakai scope terkirimUntuk()
     * (sender_id = user), sehingga pesan yang DITERIMA user (yang membuat
     * angka notif di navbar bertambah lewat jumlahPesanBelumDibaca()) tidak
     * pernah tampil di halaman ini dan tidak bisa dibuka/ditandai dibaca.
     * Sekarang defaultnya adalah Kotak Masuk, dengan tab untuk pindah ke
     * Kotak Terkirim.
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
     * BARU: Menandai pesan yang dipilih sebagai telah dibaca.
     * Hanya berlaku untuk pesan yang diterima oleh user (bukan pesan
     * terkirim), supaya konsisten dengan angka notif di navbar yang
     * dihitung dari User::jumlahPesanBelumDibaca().
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