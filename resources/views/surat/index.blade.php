@extends('layouts.app')

@section('content')
    <h1>Daftar Surat</h1>

    @if (auth()->user()->isStaff())
        <p><a href="{{ route('surat.create') }}">+ Input Surat Baru</a></p>
    @endif

    <table border="1" cellpadding="6">
        <tr>
            <th>Nomor Surat</th>
            <th>Perihal</th>
            <th>Arah</th>
            <th>Status</th>
            <th></th>
        </tr>
        @forelse ($surat as $item)
            <tr>
                <td>{{ $item->nomor_surat }}</td>
                <td>{{ $item->perihal }}</td>
                <td>{{ $item->arah_surat->label() }}</td>
                <td>{{ $item->status->label() }}</td>
                <td><a href="{{ route('surat.show', $item) }}">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Belum ada surat.</td></tr>
        @endforelse
    </table>

    {{ $surat->links() }}
@endsection
