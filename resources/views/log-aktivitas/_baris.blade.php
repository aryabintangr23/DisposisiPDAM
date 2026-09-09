@foreach ($logs as $log)
    <tr
        @if ($sorot ?? false)
            data-sorot="1"
            class="bg-amber-50 transition-colors duration-1000"
        @endif
    >
        <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
            {{ $log->created_at->format('d-m-Y H:i:s') }}
        </td>
        <td class="px-5 py-3">
            @if ($log->user)
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">
                        {{ strtoupper(substr($log->user->nama, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-700">{{ $log->user->nama }}</p>
                        <p class="truncate text-[11px] text-slate-400">{{ ucwords(str_replace('_', ' ', $log->user->role->nama_role ?? '-')) }}</p>
                    </div>
                </div>
            @else
                <span class="text-sm italic text-slate-400">Tidak dikenal</span>
            @endif
        </td>
        <td class="px-5 py-3">
            @php
                $warnaBadge = match (true) {
                    str_starts_with($log->aksi, 'login_gagal') => 'bg-rose-50 text-rose-700',
                    str_starts_with($log->aksi, 'login') => 'bg-emerald-50 text-emerald-700',
                    str_starts_with($log->aksi, 'user') => 'bg-violet-50 text-violet-700',
                    str_contains($log->aksi, 'hapus') => 'bg-rose-50 text-rose-700',
                    str_starts_with($log->aksi, 'surat') => 'bg-sky-50 text-sky-700',
                    default => 'bg-indigo-50 text-indigo-700',
                };
            @endphp
            <span class="inline-flex items-center whitespace-nowrap rounded-full {{ $warnaBadge }} px-2.5 py-1 text-[11px] font-semibold">
                {{ ucwords(str_replace('_', ' ', $log->aksi)) }}
            </span>
        </td>
        <td class="px-5 py-3 text-sm text-slate-600">
            {{ $log->deskripsi }}
            @if ($log->entitas === 'surat' && $log->entitas_id)
                <a href="{{ route('surat.show', $log->entitas_id) }}" class="ml-1 whitespace-nowrap text-xs font-semibold text-brand-700 hover:underline">Lihat surat</a>
            @elseif ($log->entitas === 'user' && $log->entitas_id)
                <a href="{{ route('pengguna.edit', $log->entitas_id) }}" class="ml-1 whitespace-nowrap text-xs font-semibold text-brand-700 hover:underline">Lihat user</a>
            @endif
        </td>
        <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-400">{{ $log->ip_address ?? '-' }}</td>
    </tr>
@endforeach
