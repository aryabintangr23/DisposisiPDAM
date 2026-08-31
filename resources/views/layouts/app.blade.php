<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Disposisi Surat - Perumda Tirta Gemilang</title>
    {{-- Belum ada styling/CSS framework di iterasi ini --}}
</head>
<body>
    <header>
        <strong>Aplikasi Disposisi Surat</strong>
        @auth
            | {{ auth()->user()->nama }} ({{ auth()->user()->role->nama_role }})
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit">Logout</button>
            </form>
        @endauth
    </header>

    @if (session('status'))
        <p><strong>{{ session('status') }}</strong></p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
