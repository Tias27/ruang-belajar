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
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm hover:bg-campus-100" href="{{ $folder ? route('folders.show', $folder) : route('documents.show', $document) }}">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $folder ? 'folder' : 'materi' }}
            </a>
            <div class="mt-5 min-w-0">
                <p class="text-sm font-semibold text-campus-700">Kartu belajar</p>
                <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $source->title }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Buka jawaban, lalu tandai mana yang sudah paham atau perlu diulang.</p>
            </div>
            <div class="mt-5 grid min-w-0 grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach($statCards as $item)
                    <div class="rounded-[1.1rem] {{ $item['class'] }} p-3">
                        <p class="text-xs font-semibold">{{ $item['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold">{{ $stats[$item['key']] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="mt-5 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($flashcards as $flashcard)
                @php
                    $statusLabel = ['baru' => 'Baru', 'ulang' => 'Ulang lagi', 'sulit' => 'Sulit', 'paham' => 'Paham'][$flashcard->study_status] ?? 'Baru';
                @endphp
                <article x-data="{open:false}" class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <span class="rounded-full bg-campus-50 px-3 py-1 text-xs font-semibold uppercase text-campus-700">Depan</span>
                        <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $statusLabel }}</span>
                    </div>
                    <p class="mt-4 min-h-16 break-words text-sm leading-7 text-slate-900">{{ $flashcard->front }}</p>
                    <button type="button" x-on:click="open=!open" class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-full bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                        <span x-text="open ? 'Tutup jawaban' : 'Lihat jawaban'"></span>
                    </button>
                    <div x-show="open" x-cloak class="mt-4 whitespace-pre-line break-words rounded-[1.1rem] bg-slate-50 p-4 text-sm leading-7 text-slate-700">{{ $flashcard->display_back ?? $flashcard->back }}</div>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        @foreach([
                            ['status' => 'sulit', 'label' => 'Sulit', 'class' => 'bg-rose-50 text-rose-700 hover:bg-rose-100'],
                            ['status' => 'ulang', 'label' => 'Ulang', 'class' => 'bg-amber-50 text-amber-700 hover:bg-amber-100'],
                            ['status' => 'paham', 'label' => 'Paham', 'class' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'],
                        ] as $action)
                            <form method="POST" action="{{ route('flashcards.review', $flashcard) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="study_status" value="{{ $action['status'] }}">
                                <button class="h-10 w-full rounded-full px-2 text-xs font-semibold {{ $action['class'] }}">{{ $action['label'] }}</button>
                            </form>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        Direview {{ $flashcard->review_count }} kali
                        @if($flashcard->last_reviewed_at)
                            · terakhir {{ $flashcard->last_reviewed_at->diffForHumans() }}
                        @endif
                    </p>
                </article>
            @empty
                <div class="rounded-[1.5rem] bg-white p-8 text-center text-sm text-slate-500 shadow-sm md:col-span-2 xl:col-span-3">
                    Belum ada kartu belajar.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
