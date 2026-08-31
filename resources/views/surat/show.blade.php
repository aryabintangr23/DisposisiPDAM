@extends('layouts.app')

@section('content')
    <h1>{{ $surat->nomor_surat }}</h1>

    <h3>Informasi Surat</h3>
    <table border="1" cellpadding="6">
        <tr><td>Arah</td><td>{{ $surat->arah_surat->label() }}</td></tr>
        <tr><td>Jenis Surat</td><td>{{ $surat->jenis_surat }}</td></tr>
        <tr><td>Nomor Agenda</td><td>{{ $surat->nomor_agenda ?? '-' }}</td></tr>
        <tr><td>Tanggal Surat</td><td>{{ $surat->tanggal_surat?->format('d-m-Y') }}</td></tr>
        <tr><td>Tanggal Diterima</td><td>{{ $surat->tanggal_diterima?->format('d-m-Y') ?? '-' }}</td></tr>
        <tr><td>Surat Dari</td><td>{{ $surat->surat_dari ?? '-' }}</td></tr>
        <tr><td>Tujuan Surat</td><td>{{ $surat->tujuan_surat ?? '-' }}</td></tr>
        <tr><td>Perihal</td><td>{{ $surat->perihal }}</td></tr>
        <tr><td>Status Surat</td><td>{{ $surat->status->label() }}</td></tr>
        <tr><td>Dibuat oleh</td><td>{{ $surat->pembuat->nama }}</td></tr>
    </table>

    <h3>Lampiran</h3>
    <ul>
        @forelse ($surat->lampiran as $file)
            <li><a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank">{{ $file->nama_file }}</a> ({{ number_format($file->ukuran_file / 1024, 0) }} KB)</li>
        @empty
            <li>Tidak ada lampiran.</li>
        @endforelse
    </ul>

    {{-- Riwayat disposisi ditampilkan langsung di sini, tidak perlu buka halaman lain --}}
    <h3>Riwayat Disposisi</h3>
    <table border="1" cellpadding="6">
        <tr>
            <th>Dari</th>
            <th>Ke</th>
            <th>Tanggal</th>
            <th>Prioritas</th>
            <th>Batas Waktu</th>
            <th>Instruksi</th>
            <th>Status</th>
            <th></th>
        </tr>
        @foreach ($surat->disposisi as $d)
            <tr>
                <td>{{ $d->pengirim->nama }} ({{ $d->pengirim->role->nama_role }})</td>
                <td>{{ $d->penerima->nama }} ({{ $d->penerima->role->nama_role }})</td>
                <td>{{ $d->tanggal_disposisi?->format('d-m-Y') }}</td>
                <td>{{ $d->prioritas->label() }}</td>
                <td>{{ $d->batas_waktu?->format('d-m-Y') ?? '-' }}</td>
                <td>{{ $d->instruksi ?? '-' }}</td>
                <td>{{ $d->status->label() }}</td>
                <td>
                    @if (auth()->user()->isStaff() && $d->penerima_id === auth()->id() && $d->status->value !== 'selesai')
                        <form method="POST" action="{{ route('disposisi.selesaikan', [$surat, $d]) }}">
                            @csrf
                            <button type="submit">Tandai Selesai</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    @if ($penerimaOptions->isNotEmpty())
        <h3>Kirim Disposisi Baru</h3>
        <form method="POST" action="{{ route('disposisi.store', $surat) }}">
            @csrf
            <label>Kirim ke
                <select name="penerima_id" required>
                    @foreach ($penerimaOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->nama }} ({{ $opt->role->nama_role }})</option>
                    @endforeach
                </select>
            </label><br>
            <label>Prioritas
                <select name="prioritas" required>
                    <option value="sangat_segera">Sangat Segera (3 hari)</option>
                    <option value="segera">Segera (5 hari)</option>
                    <option value="biasa">Biasa (7 hari)</option>
                    <option value="tunggu_petunjuk">Tunggu Petunjuk</option>
                </select>
            </label><br>
            <label>Instruksi <textarea name="instruksi"></textarea></label><br>
            <button type="submit">Kirim Disposisi</button>
        </form>
    @endif
@endsection
