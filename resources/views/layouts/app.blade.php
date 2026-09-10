<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - Disposisi Surat | Perumda Tirta Gemilang</title>

    <!-- Tailwind CSS -->
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

    <!-- Turbo Hotwired -->
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8/dist/turbo.es2017-umd.js"></script>
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">

        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 z-40 w-64 transform bg-brand-900 text-brand-50 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                <img src="{{ asset('images/LOGO PERUMDA.png') }}" alt="Logo Tirta Gemilang" class="h-10 w-10">
                <div class="leading-tight">
                    <p class="text-sm font-bold tracking-wide">TIRTA GEMILANG</p>
                    <p class="text-[11px] text-brand-200">Sistem Disposisi Surat</p>
                </div>
            </div>

            <nav class="mt-4 space-y-1 overflow-y-auto px-3" style="max-height: calc(100vh - 4rem - 8.5rem);">
                @php
                    $arahAktif = request()->routeIs('surat.*') && !request()->routeIs('surat.create') && !request()->routeIs('surat.sampah') && !request()->routeIs('surat.edit')
                        ? request()->query('arah')
                        : null;
                @endphp

                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 11a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zm10-11a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1V5zm0 11a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-3z" />
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('surat.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('surat.*') && !request()->routeIs('surat.create') && !request()->routeIs('surat.sampah') && !request()->routeIs('surat.edit') && !$arahAktif ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Semua Surat
                </a>

                <a href="{{ route('surat.index', ['arah' => 'masuk']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $arahAktif === 'masuk' ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                    </svg>
                    Surat Masuk
                </a>

                <a href="{{ route('surat.index', ['arah' => 'keluar']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $arahAktif === 'keluar' ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                    Surat Keluar
                </a>

                <a href="{{ route('pesan.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('pesan.*') && !request()->routeIs('pesan.sampah') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Pesan
                </a>

                <!-- Menu Khusus Admin -->
                @if (auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('pengguna.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('pengguna.*') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Kelola Pengguna
                    </a>

                    <a href="{{ route('logAktivitas.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('logAktivitas.*') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Log Aktivitas
                    </a>
                @endif

                <p class="mt-5 mb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-brand-300">Tempat Sampah</p>

                @php
                    $urlSampah = auth()->check() && (auth()->user()->isStaff() || auth()->user()->isKabag()) ? route('surat.sampah') : route('pesan.sampah');
                @endphp
                <a href="{{ $urlSampah }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('surat.sampah') || request()->routeIs('pesan.sampah') ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Sampah
                </a>
            </nav>

            @auth
                <div class="absolute bottom-0 left-0 right-0 border-t border-white/10 p-4" x-data="{ profileOpen: false }" @click.outside="profileOpen = false">
                    <div class="relative">
                        <button type="button" @click="profileOpen = !profileOpen" class="flex w-full items-center gap-3 rounded-lg p-1 text-left transition hover:bg-white/5">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                                {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1 leading-tight">
                                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()->nama }}</p>
                                <p class="truncate text-[11px] text-brand-200">{{ ucwords(str_replace('_', ' ', auth()->user()->role->nama_role)) }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brand-300 transition" :class="profileOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>

                        <div x-cloak x-show="profileOpen" x-transition class="absolute bottom-full left-0 mb-2 w-full overflow-hidden rounded-lg border border-white/10 bg-brand-800 py-1 shadow-lg">
                            <a href="{{ route('profil.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-brand-100 transition hover:bg-white/10 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Profil
                            </a>
                            <a href="{{ route('profil.pengaturan') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-brand-100 transition hover:bg-white/10 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Pengaturan
                            </a>
                            <div class="my-1 border-t border-white/10"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-brand-100 transition hover:bg-white/10 hover:text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth
        </aside>

        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

        <!-- Main Content Area -->
        <div class="flex min-h-screen w-full flex-1 flex-col lg:pl-0">

            <!-- Topbar Header -->
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
                        <a href="{{ route('pesan.index') }}" title="Pesan" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700 {{ request()->routeIs('pesan.*') ? 'bg-brand-50 text-brand-700' : '' }}">
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

                        <div class="relative" x-data="{ topProfileOpen: false }" @click.outside="topProfileOpen = false">
                            <button type="button" @click="topProfileOpen = !topProfileOpen" class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 transition hover:ring-2 hover:ring-brand-300">
                                {{ strtoupper(substr(auth()->user()->nama, 0, 1)) }}
                            </button>

                            <div x-cloak x-show="topProfileOpen" x-transition class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                <div class="border-b border-slate-100 px-3 py-2">
                                    <p class="truncate text-sm font-semibold text-slate-700">{{ auth()->user()->nama }}</p>
                                    <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profil.edit') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Profil
                                </a>
                                <a href="{{ route('profil.pengaturan') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Pengaturan
                                </a>
                                <div class="my-1 border-t border-slate-100"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-rose-600 transition hover:bg-rose-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endauth
            </header>

            <!-- Alert Flash Messages -->
            <div class="px-4 pt-4 sm:px-6">
                @if (session('status'))
                    <div class="mb-4 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-medium">{{ session('status') }}</p>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <p class="font-medium">{{ session('warning') }}</p>
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

            <!-- Page Content -->
            <main class="flex-1 px-4 pb-10 sm:px-6">
                @yield('content')
            </main>

            <footer class="border-t border-slate-200 px-6 py-4 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} Perumda Air Minum Tirta Gemilang — Kabupaten Magelang
            </footer>
        </div>
    </div>

    @auth
        @php
            $tampilkanNotifLogin = session()->pull('tampilkan_notif_login', false);
            $jumlahNotifLogin = $tampilkanNotifLogin ? auth()->user()->jumlahPesanBelumDibaca() : 0;
        @endphp
        @if ($jumlahNotifLogin > 0)
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="fixed right-4 top-4 z-50 w-[calc(100%-2rem)] max-w-sm sm:right-6 sm:top-6">
                <div class="flex items-start gap-3 rounded-xl border border-brand-200 bg-white p-4 shadow-lg ring-1 ring-black/5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800">Anda punya pesan baru</p>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ $jumlahNotifLogin }} pesan belum dibaca menunggu Anda.
                        </p>
                        <a href="{{ route('pesan.index') }}" class="mt-1.5 inline-block text-sm font-semibold text-brand-700 hover:underline">
                            Lihat pesan
                        </a>
                    </div>
                    <button type="button" @click="show = false" class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        @endif
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.addEventListener('turbo:before-cache', () => {
            if (window.Alpine) {
                Alpine.destroyTree(document.body);
            }
        });
        document.addEventListener('turbo:render', () => {
            if (window.Alpine) {
                Alpine.initTree(document.body);
            }
        });
    </script>
    @stack('scripts')
</body>
</html>