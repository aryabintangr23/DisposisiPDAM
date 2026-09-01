<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Kotak masuk / terkirim, mirip tampilan email sederhana.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'sent' ? 'sent' : 'inbox';

        if ($tab === 'sent') {
            $pesan = Message::with('penerima.role')
                ->where('sender_id', $user->id)
                ->latest()
                ->paginate(15)
                ->withQueryString();
        } else {
            $pesan = Message::with('pengirim.role')
                ->where('receiver_id', $user->id)
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        return view('messages.index', compact('pesan', 'tab'));
    }

    public function create(Request $request): View
    {
        $penerimaList = User::with('role')
            ->where('id', '!=', $request->user()->id)
            ->orderBy('nama')
            ->get();

        // Prefill penerima saat membalas pesan, mis. dari tombol "Balas".
        $prefillPenerimaId = $request->query('to');
        $prefillSubjek = $request->query('subjek');

        return view('messages.create', compact('penerimaList', 'prefillPenerimaId', 'prefillSubjek'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'exists:users,id', 'different:'.$request->user()->id],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ], [
            'receiver_id.different' => 'Anda tidak bisa mengirim pesan ke diri sendiri.',
        ]);

        Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $data['receiver_id'],
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        return redirect()->route('pesan.index')->with('status', 'Pesan berhasil dikirim.');
    }

    public function show(Request $request, Message $pesan): View
    {
        $user = $request->user();

        abort_unless($pesan->sender_id === $user->id || $pesan->receiver_id === $user->id, 403, 'Anda tidak memiliki akses ke pesan ini.');

        if ($pesan->receiver_id === $user->id) {
            $pesan->tandaiSudahDibaca();
        }

        $pesan->load(['pengirim.role', 'penerima.role']);

        return view('messages.show', compact('pesan'));
    }

    /**
     * Pindahkan pesan terpilih ke tempat sampah.
     *
     * CATATAN PENTING: kolom deleted_at di tabel messages cuma satu untuk
     * seluruh baris (bukan per-pengirim/per-penerima). Artinya kalau salah
     * satu pihak (pengirim ATAU penerima) menghapus pesan ini, pesan itu
     * akan hilang dari tempat sampah KEDUA pihak, bukan cuma punya dia.
     * Ini beda dengan Gmail yang punya status hapus terpisah per akun.
     * Kalau perilaku ini tidak sesuai kebutuhan, beri tahu saya - perlu
     * kolom tambahan (mis. dihapus_oleh_pengirim_at / dihapus_oleh_penerima_at)
     * untuk membuatnya per-pengguna.
     */
    public function hapus(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:messages,id'],
        ]);

        $query = Message::whereIn('id', $data['ids'])
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id));

        $jumlah = $query->count();
        $query->delete();

        return redirect()->route('pesan.index')->with('status', "{$jumlah} pesan dipindahkan ke tempat sampah.");
    }

    public function sampah(Request $request): View
    {
        $user = $request->user();

        $pesan = Message::onlyTrashed()
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->with(['pengirim.role', 'penerima.role'])
            ->latest('deleted_at')
            ->paginate(15);

        return view('messages.sampah', compact('pesan'));
    }

    public function pulihkan(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = Message::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id));

        $jumlah = $query->count();
        $query->restore();

        return redirect()->route('pesan.sampah')->with('status', "{$jumlah} pesan dipulihkan.");
    }

    public function hapusPermanen(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = Message::onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id));

        $jumlah = $query->count();
        $query->forceDelete();

        return redirect()->route('pesan.sampah')->with('status', "{$jumlah} pesan dihapus permanen.");
    }
}
