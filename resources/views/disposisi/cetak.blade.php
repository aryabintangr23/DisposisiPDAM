<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 12px; color: #000; }
    .header { text-align: center; }
    .header h1 { font-size: 14px; margin: 2px 0; }
    .header h2 { font-size: 13px; margin: 2px 0; }
    .header p { font-size: 10px; margin: 2px 0; }
    hr { border: none; border-top: 1px dashed #000; margin: 6px 0 10px; }
    .judul { text-align: center; font-size: 13px; font-weight: bold; margin-bottom: 10px; }
    table.info td { padding: 3px 6px; vertical-align: top; }
    table.grid { border-collapse: collapse; }
    table.grid td { border: 1px solid #000; padding: 6px; vertical-align: top; }
    ul.pilihan { list-style: none; margin: 4px 0; padding-left: 0; }
    ul.pilihan li { margin-bottom: 2px; }
    .checked::before { content: "\2611  "; } {{-- kotak tercentang --}}
    .unchecked::before { content: "\2610  "; } {{-- kotak kosong --}}
    .kotak-instruksi { border: 1px solid #000; min-height: 100px; padding: 6px; margin-top: 4px; }
</style>
</head>
<body>
    <div class="header">
        <h1>PEMERINTAH KABUPATEN MAGELANG</h1>
        <h2>PERUMDA AIR MINUM TIRTA GEMILANG</h2>
    </div>
    <hr>
    <div class="judul">LEMBAR DISPOSISI</div>

    <table class="info" width="100%">
        <tr>
            <td width="15%">Surat dari</td>
            <td width="35%">: {{ $surat->surat_dari ?? $surat->tujuan_surat ?? '-' }}</td>
            <td width="15%">Diterima tgl</td>
            <td width="35%">: {{ $disposisi->tanggal_diterima?->format('d-m-Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tanggal surat</td>
            <td>: {{ $surat->tanggal_surat?->format('d-m-Y') }}</td>
            <td rowspan="2">No agenda</td>
            <td rowspan="2">: {{ $surat->nomor_agenda ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nomor surat</td>
            <td>: {{ $surat->nomor_surat }}</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td colspan="3">: {{ $surat->perihal }}</td>
        </tr>
    </table>

    <table width="100%" style="margin-top: 12px;">
        <tr>
            <td width="50%" style="vertical-align: top;">
                <strong>Disposisi Untuk:</strong>
                <ul class="pilihan">
                    <li class="{{ $disposisi->penerima->role->nama_role === 'staff_umum' ? 'checked' : 'unchecked' }}">Staff Umum</li>
                    <li class="{{ $disposisi->penerima->role->nama_role === 'kabag_umum' ? 'checked' : 'unchecked' }}">Kabag Umum &amp; Administrasi</li>
                    <li class="{{ $disposisi->penerima->role->nama_role === 'direktur' ? 'checked' : 'unchecked' }}">Direktur</li>
                </ul>
            </td>
            <td width="50%" style="vertical-align: top;">
                <strong>Prioritas:</strong>
                <ul class="pilihan">
                    <li class="{{ $disposisi->prioritas->value === 'sangat_segera' ? 'checked' : 'unchecked' }}">Sangat Segera (3 hari)</li>
                    <li class="{{ $disposisi->prioritas->value === 'segera' ? 'checked' : 'unchecked' }}">Segera (5 hari)</li>
                    <li class="{{ $disposisi->prioritas->value === 'biasa' ? 'checked' : 'unchecked' }}">Biasa (7 hari)</li>
                    <li class="{{ $disposisi->prioritas->value === 'tunggu_petunjuk' ? 'checked' : 'unchecked' }}">Tunggu Petunjuk</li>
                </ul>
            </td>
        </tr>
    </table>

    <div style="margin-top: 12px;">
        <strong>DISPOSISI</strong>
        <div class="kotak-instruksi">{{ $disposisi->instruksi ?? '-' }}</div>
    </div>

    <table width="100%" style="margin-top: 16px;">
        <tr>
            <td width="50%">Dari&nbsp;&nbsp;: {{ $disposisi->pengirim->nama }} ({{ ucwords(str_replace('_', ' ', $disposisi->pengirim->role->nama_role)) }})</td>
            <td width="50%">Kepada : {{ $disposisi->penerima->nama }} ({{ ucwords(str_replace('_', ' ', $disposisi->penerima->role->nama_role)) }})</td>
        </tr>
        <tr>
            <td>Tanggal&nbsp;&nbsp;: {{ $disposisi->tanggal_disposisi?->format('d-m-Y') }}</td>
            <td>Batas Waktu : {{ $disposisi->batas_waktu?->format('d-m-Y') ?? '-' }}</td>
        </tr>
    </table>
</body>
</html>
