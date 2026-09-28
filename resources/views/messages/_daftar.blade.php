        @forelse ($pesan as $item)
            @php
                $belumDibaca = $kotak === 'masuk' && ! $item->is_read;
                $lawan = $kotak === 'masuk' ? $item->pengirim : $item->penerima;
                $labelLawan = $kotak === 'masuk' ? 'Dari' : 'Kepada';
            @endphp
            <li class="flex items-center gap-3 px-3 {{ $belumDibaca ? 'bg-brand-50/40' : '' }}">
                <input type="checkbox" name="ids[]" value="{{ $item->id }}" x-model="selected" class="h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500">

                <a href="{{ route('pesan.show', $item) }}" class="flex flex-1 items-center gap-4 py-4 transition hover:bg-slate-50">
                    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                        {{ strtoupper(substr($lawan->nama, 0, 1)) }}
                        @if ($belumDibaca)
                            <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-rose-500"></span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm {{ $belumDibaca ? 'font-bold text-slate-900' : 'font-medium text-slate-700' }}">
                                {{ $labelLawan }}: {{ $lawan->nama }}
                            </p>
                            <span class="shrink-0 text-xs text-slate-400">{{ $item->created_at->format('d-m-Y H:i') }}</span>
                        </div>
                        <p class="truncate text-sm {{ $belumDibaca ? 'font-semibold text-slate-800' : 'text-slate-600' }}">
                            {{ $item->subject }}
                            @if ($item->surat_id)
                                <span class="ml-1 inline-flex items-center rounded-full bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700 align-middle">Disposisi</span>
                            @endif
                            @if ($belumDibaca)
                                <span class="ml-1 inline-flex items-center rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold text-rose-600 align-middle">Belum dibaca</span>
                            @endif
                        </p>
                        <p class="truncate text-xs text-slate-400">{{ Str::limit(strip_tags($item->body), 90) }}</p>
                    </div>
                </a>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-slate-400">
                <div class="flex flex-col items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <p class="text-sm">{{ $kotak === 'masuk' ? 'Belum ada pesan masuk.' : 'Belum ada pesan terkirim.' }}</p>
                </div>
            </li>
        @endforelse
