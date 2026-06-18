<x-app-layout title="Kartu Belajar">
    @php
        $source = $folder ?: $document;
        $statCards = [
            ['key' => 'sulit', 'label' => 'Sulit', 'class' => 'bg-rose-50 text-rose-700'],
            ['key' => 'ulang', 'label' => 'Ulang lagi', 'class' => 'bg-amber-50 text-amber-700'],
            ['key' => 'baru', 'label' => 'Baru', 'class' => 'bg-slate-50 text-slate-700'],
            ['key' => 'paham', 'label' => 'Paham', 'class' => 'bg-emerald-50 text-emerald-700'],
        ];
    @endphp

    <div class="min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.75rem] bg-gradient-to-r from-campus-50 to-white p-5 sm:p-8 shadow-sm border border-campus-100 relative">
            @if(isset($room) && $room)
                <a class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-800" href="{{ route('study-rooms.show', $room) }}">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke Room Belajar
                </a>
            @else
                <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm border border-slate-100 transition-colors hover:bg-campus-100" href="{{ $folder ? route('folders.show', $folder) : route('documents.show', $document) }}">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $folder ? 'folder' : 'materi' }}
                </a>
            @endif
            <div class="mt-5 flex min-w-0 flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <span class="inline-flex rounded-full bg-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-campus-700 shadow-sm ring-1 ring-slate-100">Flashcards</span>
                    <h1 class="mt-4 break-words text-2xl font-extrabold tracking-tight text-campus-950 sm:text-3xl">{{ $source->title }}</h1>
                    <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-slate-600">Hafalkan materi dengan interaktif. Buka jawaban, lalu tandai tingkat pemahaman Anda.</p>
                </div>
            </div>

            <div class="mt-6 grid min-w-0 grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($statCards as $item)
                    <div class="rounded-[1.25rem] {{ $item['class'] }} p-4 ring-1 ring-black/5 shadow-sm transition-all hover:scale-[1.02]">
                        <p class="text-[11px] font-bold uppercase tracking-wider opacity-80">{{ $item['label'] }}</p>
                        <p class="mt-1 text-3xl font-black">{{ $stats[$item['key']] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mt-6 grid min-w-0 gap-6 md:grid-cols-2 xl:grid-cols-3">
            @forelse($flashcards as $flashcard)
                @php
                    $statusLabel = ['baru' => 'Baru', 'ulang' => 'Ulang', 'sulit' => 'Sulit', 'paham' => 'Paham'][$flashcard->study_status] ?? 'Baru';
                    $statusColor = [
                        'baru' => 'bg-slate-100 text-slate-700 ring-slate-200',
                        'ulang' => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'sulit' => 'bg-rose-50 text-rose-700 ring-rose-200',
                        'paham' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    ][$flashcard->study_status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
                @endphp
                <article x-data="{open:false}" class="flex flex-col min-w-0 overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-sm border border-slate-100 transition-all hover:shadow-md hover:border-campus-200 relative">
                    <div class="flex min-w-0 items-start justify-between gap-3 mb-4">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-campus-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-campus-700 ring-1 ring-campus-200/50">
                            <i data-lucide="help-circle" class="h-3 w-3"></i> Pertanyaan
                        </span>
                        <span class="shrink-0 rounded-full px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider ring-1 {{ $statusColor }}">{{ $statusLabel }}</span>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <p class="min-h-[4rem] text-[15px] font-medium leading-relaxed text-slate-900 overflow-wrap-anywhere break-words hyphens-auto">{!! Str::markdown($flashcard->front) !!}</p>
                    </div>

                    <button type="button" @click="open=!open" class="mt-6 flex w-full h-12 items-center justify-center gap-2 rounded-full bg-slate-50 px-5 text-[14px] font-bold text-slate-700 shadow-sm ring-1 ring-slate-200 transition-colors hover:bg-slate-100 focus:outline-none">
                        <i data-lucide="eye" class="h-4 w-4" x-show="!open"></i>
                        <i data-lucide="eye-off" class="h-4 w-4" x-show="open" x-cloak></i>
                        <span x-text="open ? 'Sembunyikan Jawaban' : 'Buka Jawaban'"></span>
                    </button>

                    <div x-show="open" x-cloak x-transition.opacity class="mt-4 overflow-hidden rounded-[1.25rem] bg-gradient-to-b from-campus-50 to-white p-5 ring-1 ring-campus-100 shadow-inner">
                        <span class="block mb-2 text-[11px] font-bold uppercase tracking-wider text-campus-700">Kunci Jawaban</span>
                        <div class="text-[14px] leading-relaxed text-slate-700 overflow-wrap-anywhere break-words hyphens-auto [&>h1]:font-bold [&>h2]:font-bold [&>h3]:font-bold [&>p]:mb-2 [&>ul]:list-disc [&>ul]:pl-4 [&>ol]:list-decimal [&>ol]:pl-4 [&>li]:mb-1">{!! Str::markdown($flashcard->display_back ?? $flashcard->back) !!}</div>
                        
                        <div class="mt-5 grid grid-cols-3 gap-3">
                            @foreach([
                                ['status' => 'sulit', 'label' => 'Sulit', 'class' => 'bg-white text-rose-700 ring-rose-200 hover:bg-rose-50 hover:ring-rose-300'],
                                ['status' => 'ulang', 'label' => 'Ulang', 'class' => 'bg-white text-amber-700 ring-amber-200 hover:bg-amber-50 hover:ring-amber-300'],
                                ['status' => 'paham', 'label' => 'Paham', 'class' => 'bg-white text-emerald-700 ring-emerald-200 hover:bg-emerald-50 hover:ring-emerald-300'],
                            ] as $action)
                                <form method="POST" action="{{ route('flashcards.review', $flashcard) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="study_status" value="{{ $action['status'] }}">
                                    <button class="h-10 w-full rounded-xl px-2 text-[12px] font-bold shadow-sm ring-1 transition-colors {{ $action['class'] }}">{{ $action['label'] }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5 flex items-center justify-between text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                        <span>Direview {{ $flashcard->review_count }}x</span>
                        @if($flashcard->last_reviewed_at)
                            <span>{{ $flashcard->last_reviewed_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="flex flex-col items-center justify-center rounded-[1.75rem] border border-dashed border-slate-200 bg-slate-50 p-12 text-center shadow-sm md:col-span-2 xl:col-span-3">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-white text-slate-300 shadow-sm mb-4">
                        <i data-lucide="layers" class="h-8 w-8"></i>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">Belum Ada Flashcard</h3>
                    <p class="mt-1 text-sm text-slate-500">Buat flashcard dari materi Anda untuk mulai menghafal.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
