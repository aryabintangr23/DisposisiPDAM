@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Kelola Pengguna</h2>
            <p class="mt-1 text-sm text-slate-500">Tambah, ubah, dan hapus akun pengguna beserta perannya.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('pengguna.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah User
            </a>
        </div>
    </div>

    {{-- Filter & pencarian --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('pengguna.index') }}"
           class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold transition
                  {{ ! $role ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Semua
        </a>
        @foreach ($roles as $r)
            <a href="{{ route('pengguna.index', ['role' => $r->nama_role]) }}"
               class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold transition
                      {{ $role === $r->nama_role ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ ucwords(str_replace('_', ' ', $r->nama_role)) }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('pengguna.index') }}" class="mb-5 flex items-center gap-2">
        <input type="search" name="cari" value="{{ $cari }}"
               placeholder="Cari nama atau email…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500">
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900">
            Cari
        </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nama</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Email</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr class="{{ $user->id === auth()->id() ? 'bg-brand-50/50' : '' }}">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                    {{ strtoupper(substr($user->nama, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $user->nama }}
                                        @if ($user->id === auth()->id())
                                            <span class="ml-1 inline-flex items-center rounded-full bg-brand-100 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700 align-middle">Anda</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $user->email }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                {{ ucwords(str_replace('_', ' ', $user->role->nama_role)) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('pengguna.edit', $user) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    Edit
                                </a>

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('pengguna.destroy', $user) }}"
                                          onsubmit="return confirm('Hapus user {{ $user->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                            <p class="text-sm">Tidak ada pengguna yang cocok.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $users->links() }}
    </div>
@endsection
