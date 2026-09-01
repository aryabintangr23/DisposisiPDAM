@extends('layouts.app')

@section('title', 'Tulis Pesan')

@section('content')
    <div class="mt-6 mb-6">
        <a href="{{ route('pesan.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Pesan
        </a>
        <h2 class="mt-2 text-2xl font-bold text-slate-800">Tulis Pesan Baru</h2>
    </div>

    <div class="max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('pesan.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kepada</label>
                <select name="receiver_id" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="" disabled {{ ! $prefillPenerimaId ? 'selected' : '' }}>-- Pilih penerima --</option>
                    @foreach ($penerimaList as $opt)
                        <option value="{{ $opt->id }}" {{ (string) $prefillPenerimaId === (string) $opt->id ? 'selected' : '' }}>
                            {{ $opt->nama }} ({{ ucwords(str_replace('_',' ',$opt->role->nama_role)) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Subjek</label>
                <input type="text" name="subject" required value="{{ old('subject', $prefillSubjek) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Isi Pesan</label>
                <textarea name="body" rows="8" required
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">{{ old('body') }}</textarea>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('pesan.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    Kirim Pesan
                </button>
            </div>
        </form>
    </div>
@endsection
