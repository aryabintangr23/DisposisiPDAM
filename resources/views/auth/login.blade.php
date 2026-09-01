<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Disposisi Surat | Perumda Tirta Gemilang</title>
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
    <style>body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600">

    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">

            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-2xl backdrop-blur">
                    💧
                </div>
                <h1 class="text-xl font-bold text-white">Sistem Disposisi Surat</h1>
                <p class="mt-1 text-sm text-brand-200">Perumda Air Minum Tirta Gemilang — Kab. Magelang</p>
            </div>

            <div class="rounded-2xl bg-white p-8 shadow-2xl">
                <h2 class="mb-1 text-lg font-semibold text-slate-800">Masuk ke akun Anda</h2>
                <p class="mb-6 text-sm text-slate-500">Gunakan email dan kata sandi yang terdaftar.</p>

                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nama@tirtagemilang.co.id"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Kata Sandi</label>
                        <input type="password" id="password" name="password" required
                               placeholder="••••••••"
                               class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    </div>

                    <button type="submit"
                        class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500/40">
                        Masuk
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-brand-200">
                &copy; {{ date('Y') }} Perumda Air Minum Tirta Gemilang. Internal use only.
            </p>
        </div>
    </div>
</body>
</html>
