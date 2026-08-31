@extends('layouts.app')

@section('content')
    <h1>Input Surat Baru</h1>

    <form method="POST" action="{{ route('surat.store') }}" enctype="multipart/form-data">
        @csrf

        <label>Arah Surat
            <select name="arah_surat" required>
                <option value="masuk">Surat Masuk</option>
                <option value="keluar">Surat Keluar</option>
            </select>
        </label><br>

        <label>Jenis Surat <input type="text" name="jenis_surat" value="{{ old('jenis_surat') }}" required></label><br>
        <label>Nomor Surat <input type="text" name="nomor_surat" value="{{ old('nomor_surat') }}" required></label><br>
        <label>Nomor Agenda (manual) <input type="text" name="nomor_agenda" value="{{ old('nomor_agenda') }}"></label><br>
        <label>Tanggal Surat <input type="date" name="tanggal_surat" required></label><br>
        <label>Tanggal Diterima <input type="date" name="tanggal_diterima"></label><br>
        <label>Surat Dari (isi untuk surat masuk) <input type="text" name="surat_dari"></label><br>
        <label>Tujuan Surat (isi untuk surat keluar) <input type="text" name="tujuan_surat"></label><br>
        <label>Perihal <textarea name="perihal" required>{{ old('perihal') }}</textarea></label><br>
        <label>Lampiran (PDF, boleh lebih dari satu) <input type="file" name="lampiran[]" multiple accept="application/pdf"></label><br>

        <hr>
        <h3>Lembar Disposisi Pertama (dikirim ke Kabag Umum)</h3>

        <label>Kirim ke
            <select name="penerima_id" required>
                @foreach ($kabagList as $kabag)
                    <option value="{{ $kabag->id }}">{{ $kabag->nama }}</option>
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

        <button type="submit">Simpan &amp; Kirim Disposisi</button>
    </form>
@endsection
