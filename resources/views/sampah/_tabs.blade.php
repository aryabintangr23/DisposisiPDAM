<div class="mb-4 flex gap-1 border-b border-slate-200">
    {{-- Tab Sampah Surat --}}
    <a href="{{ route('surat.sampah') }}"
       class="border-b-2 px-4 py-2.5 text-sm font-semibold transition
              {{ request()->routeIs('surat.sampah') ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        Sampah Surat
    </a>

    {{-- Tab Sampah Pesan --}}
    <a href="{{ route('pesan.sampah') }}"
       class="border-b-2 px-4 py-2.5 text-sm font-semibold transition
              {{ request()->routeIs('pesan.sampah') ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        Sampah Pesan
    </a>
</div>