<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Menampilkan daftar pesan (Kotak Masuk / Terkirim).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $tab = $request->get('tab', 'inbox');

        if ($tab === 'sent') {
            $pesan = Message::terkirimUntuk($user)
                ->with(['pengirim', 'penerima', 'surat'])
                ->latest()
                ->paginate(15)
                ->appends(['tab' => 'sent']);
        } else {
            $pesan = Message::kotakMasukUntuk($user)
                ->with(['pengirim', 'penerima', 'surat'])
                ->orderBy('is_read', 'asc')
                ->latest()
                ->paginate(15)
                ->appends(['tab' => 'inbox']);
        }

        return view('messages.index', compact('pesan', 'tab'));
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

        return redirect()->back()->with('success', $count . ' pesan dipindahkan ke tempat sampah.');
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

        return redirect()->back()->with('success', count($messages) . ' pesan berhasil dipulihkan.');
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

        return redirect()->back()->with('success', count($request->ids) . ' pesan dihapus permanen.');
    }

    /**
     * Menandai pesan yang dipilih sebagai sudah dibaca.
     */
    public function tandaiDibaca(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:messages,id',
        ]);

        $user = auth()->user();

        $messages = Message::whereIn('id', $request->ids)
            ->where('receiver_id', $user->id)
            ->get();

        foreach ($messages as $message) {
            $message->tandaiSudahDibaca();
        }

        return redirect()->back()->with('success', count($messages) . ' pesan ditandai sudah dibaca.');
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
