{{--
    Tab navigasi Sampah, dipakai bersama oleh surat/sampah.blade.php dan
    messages/sampah.blade.php supaya kedua tempat sampah terasa sebagai satu
    halaman "Sampah" dengan dua tab, bukan dua menu terpisah di sidebar.
--}}
<div class="mb-6 flex gap-1 border-b border-slate-200">
    @if (auth()->user()->isStaff())
        <a href="{{ route('surat.sampah') }}"
           class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition
                  {{ request()->routeIs('surat.sampah') ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Sampah Surat
        </a>
    @endif
    <a href="{{ route('pesan.sampah') }}"
       class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-semibold transition
              {{ request()->routeIs('pesan.sampah') ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        Sampah Pesan
    </a>
</div>
