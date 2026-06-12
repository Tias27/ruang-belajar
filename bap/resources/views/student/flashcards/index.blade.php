<x-app-layout title="Kartu Belajar">
    @php
        $source = $folder ?: $document;
    @endphp
    <a class="inline-flex items-center gap-1 text-sm font-semibold text-campus-700" href="{{ $folder ? route('folders.show', $folder) : route('documents.show', $document) }}">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $folder ? 'folder' : 'dokumen' }}
    </a>

    <section class="mt-4 rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <p class="text-sm font-semibold text-campus-700">Kartu Belajar</p>
        <h1 class="mt-2 text-2xl font-semibold text-campus-900">{{ $source->title }}</h1>
        <div class="mt-4 grid gap-3 sm:grid-cols-4">
            @foreach([
                ['key' => 'sulit', 'label' => 'Sulit', 'color' => 'rose'],
                ['key' => 'ulang', 'label' => 'Ulang lagi', 'color' => 'amber'],
                ['key' => 'baru', 'label' => 'Baru', 'color' => 'slate'],
                ['key' => 'paham', 'label' => 'Paham', 'color' => 'emerald'],
            ] as $item)
                <div class="rounded-lg bg-{{ $item['color'] }}-50 p-3 text-{{ $item['color'] }}-700">
                    <p class="text-xs font-semibold">{{ $item['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $stats[$item['key']] ?? 0 }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($flashcards as $flashcard)
            @php
                $statusLabel = ['baru' => 'Baru', 'ulang' => 'Ulang lagi', 'sulit' => 'Sulit', 'paham' => 'Paham'][$flashcard->study_status] ?? 'Baru';
            @endphp
            <article x-data="{open:false}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase text-campus-700">Depan</p>
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $statusLabel }}</span>
                </div>
                <p class="mt-2 min-h-16 text-sm leading-6 text-slate-900">{{ $flashcard->front }}</p>
                <button type="button" x-on:click="open=!open" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                    <span x-text="open ? 'Tutup jawaban' : 'Lihat jawaban'"></span>
                </button>
                <div x-show="open" class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-700">{{ $flashcard->display_back ?? $flashcard->back }}</div>

                <div class="mt-4 grid grid-cols-3 gap-2">
                    @foreach([
                        ['status' => 'sulit', 'label' => 'Sulit', 'class' => 'border-rose-200 text-rose-700 hover:bg-rose-50'],
                        ['status' => 'ulang', 'label' => 'Ulang', 'class' => 'border-amber-200 text-amber-700 hover:bg-amber-50'],
                        ['status' => 'paham', 'label' => 'Paham', 'class' => 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'],
                    ] as $action)
                        <form method="POST" action="{{ route('flashcards.review', $flashcard) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="study_status" value="{{ $action['status'] }}">
                            <button class="h-9 w-full rounded-lg border bg-white px-2 text-xs font-semibold {{ $action['class'] }}">{{ $action['label'] }}</button>
                        </form>
                    @endforeach
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    Direview {{ $flashcard->review_count }} kali
                    @if($flashcard->last_reviewed_at)
                        • terakhir {{ $flashcard->last_reviewed_at->diffForHumans() }}
                    @endif
                </p>
            </article>
        @empty
            <p class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Belum ada kartu belajar.</p>
        @endforelse
    </div>
</x-app-layout>
