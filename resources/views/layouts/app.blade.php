<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - Disposisi Surat | Perumda Tirta Gemilang</title>

    {{-- Tailwind via CDN: tidak perlu npm run build / vite, cukup koneksi internet browser --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff8ff', 100: '#dbeefe', 200: '#bfe3fe', 300: '#93d2fd',
                            400: '#60b8fa', 500: '#3b9df3', 600: '#2380e8', 700: '#1b67d5',
                            800: '#1c56ac', 900: '#1c4a88',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">

        {{-- ============ SIDEBAR ============ --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 transform bg-brand-900 text-brand-50 transition-transform duration-200 ease-in-out
                   lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                <img src="{{ asset('images/LOGO PERUMDA.png') }}" 
                alt="Logo Tirta Gemilang" class="h-10 w-10">
                <div class="leading-tight">
                    <p class="text-sm font-bold tracking-wide">TIRTA GEMILANG</p>
                    <p class="text-[11px] text-brand-200">Sistem Disposisi Surat</p>
                </div>
            </div>

            <nav class="mt-4 space-y-1 overflow-y-auto px-3" style="max-height: calc(100vh - 4rem - 5.5rem);">
                <a href="{{ route('surat.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ request()->routeIs('surat.*') && !request()->routeIs('surat.create') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Daftar Surat
                </a>

                @if (auth()->check() && auth()->user()->isStaff())
                <a href="{{ route('surat.create') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ request()->routeIs('surat.create') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Input Surat Baru
                </a>
                @endif

                <a href="{{ route('pesan.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ request()->routeIs('pesan.*') && !request()->routeIs('pesan.sampah') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Pesan
                </a>

                {{-- ============ TEMPAT SAMPAH ============ --}}
                <p class="mt-5 mb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-brand-300">Tempat Sampah</p>

                @if (auth()->check() && auth()->user()->isStaff())
                <a href="{{ route('surat.sampah') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ request()->routeIs('surat.sampah') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sampah Surat
                </a>
                @endif

                <a href="{{ route('pesan.sampah') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ request()->routeIs('pesan.sampah') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sampah Pesan
                </a>
            </nav>

            @auth
            <div class="absolute bottom-0 left-0 right-0 border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                        {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                    </div>
                    <div class="min-w-0 leading-tight">
                        <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->nama }}</p>
                        <p class="truncate text-[11px] text-brand-200">{{ ucwords(str_replace('_', ' ', auth()->user()->role->nama_role)) }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-white/5 px-3 py-2 text-xs font-semibold text-brand-100 transition hover:bg-white/10 hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
            @endauth
        </aside>

        {{-- Overlay saat sidebar mobile terbuka --}}
        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

        {{-- ============ MAIN AREA ============ --}}
        <div class="flex min-h-screen w-full flex-1 flex-col lg:pl-0">

            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-base font-semibold text-slate-800 sm:text-lg">@yield('title', 'Dashboard')</h1>
                </div>

                @auth
                <div class="flex items-center gap-3">
                    <a href="{{ route('pesan.index') }}" title="Pesan"
                       class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700
                              {{ request()->routeIs('pesan.*') ? 'bg-brand-50 text-brand-700' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        @php $jumlahBelumDibaca = auth()->user()->jumlahPesanBelumDibaca(); @endphp
                        @if ($jumlahBelumDibaca > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full border-2 border-white bg-rose-500 px-1 text-[10px] font-bold text-white">
                                {{ $jumlahBelumDibaca > 9 ? '9+' : $jumlahBelumDibaca }}
                            </span>
                        @endif
                    </a>
                    <span class="hidden rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 sm:inline-block">
                        {{ ucwords(str_replace('_', ' ', auth()->user()->role->nama_role)) }}
                    </span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                        {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                    </div>
                </div>
                @endauth
            </header>

            {{-- Flash messages --}}
            <div class="px-4 pt-4 sm:px-6">
                @if (session('status'))
                    <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-medium">{{ session('status') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <div class="flex items-center gap-2 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Terdapat kesalahan pada input:
                        </div>
                        <ul class="mt-2 list-disc space-y-1 pl-9">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 px-4 pb-10 sm:px-6">
                @yield('content')
            </main>

            <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} Perumda Air Minum Tirta Gemilang — Kabupaten Magelang
            </footer>
        </div>
    </div>

    {{-- Alpine.js untuk interaksi sidebar (toggle mobile) --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
