<x-app-layout title="Kartu belajar">
    @php
        $source = $folder ?: $document;
        $sourceUrl = $folder ? route('folders.show', $folder) : route('documents.show', $document);
        $deckCards = $flashcards->map(fn ($card) => ['id' => $card->id, 'study_status' => $card->study_status, 'review_count' => $card->review_count])->values();
        $statusLabels = ['sulit' => 'Sulit', 'ulang' => 'Ulang lagi', 'baru' => 'Baru', 'paham' => 'Paham'];
    @endphp

    <div class="learning-live live-flashcards min-w-0" x-data="flashcardDeck(@js($deckCards))">
        <header class="live-page-heading">
            <a class="back-link" href="{{ isset($room) && $room ? route('study-rooms.show', $room) : $sourceUrl }}">
                <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
                {{ isset($room) && $room ? 'Kembali ke belajar bareng' : 'Kembali ke materi' }}
            </a>
            <p class="eyebrow">Kartu belajar</p>
            <h1>{{ $source->title }}</h1>
            <p class="subtitle">Coba ingat jawabannya, lalu balik kartunya. Sedikit-sedikit, makin paham.</p>
        </header>

        <div class="live-flashcard-stats" aria-label="Progres kartu belajar">
            @foreach($statusLabels as $key => $label)
                <div class="live-flashcard-stat"><span>{{ $label }}</span><strong x-text="count(@js($key))">{{ $stats[$key] ?? 0 }}</strong></div>
            @endforeach
        </div>

        <div class="live-card-deck" >
            @if($flashcards->isNotEmpty())
                <div class="live-card-count">
                    <span aria-live="polite">Kartu <strong x-text="current + 1">1</strong> dari {{ $flashcards->count() }}</span>
                    <span>Yang perlu diulang tampil dulu</span>
                </div>
            @endif

            @forelse($flashcards as $flashcard)
                <article x-show="current === {{ $loop->index }}" @if(!$loop->first) x-cloak @endif
                         class="live-flashcard" aria-label="Kartu {{ $loop->iteration }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-500">Pikirkan dulu, nggak perlu buru-buru.</span>
                        <span class="live-card-status" data-status="{{ $flashcard->study_status }}" :data-status="cards[{{ $loop->index }}].study_status" x-text="labels[cards[{{ $loop->index }}].study_status]">{{ $statusLabels[$flashcard->study_status] ?? 'Baru' }}</span>
                    </div>

                    <div class="live-flashcard-question">{!! Str::markdown($flashcard->front, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>

                    <button type="button" @click="open = !open" :disabled="saving" :aria-expanded="open" aria-controls="answer-{{ $flashcard->id }}" class="live-card-flip">
                        <i data-lucide="rotate-3d" aria-hidden="true"></i>
                        <span x-text="open ? 'Tutup jawaban' : 'Lihat jawaban'">Lihat jawaban</span>
                    </button>

                    <div id="answer-{{ $flashcard->id }}" x-show="open" x-cloak x-transition.opacity class="live-card-answer">
                        <p class="text-xs font-semibold text-slate-500">JAWABAN</p>
                        <div>{!! Str::markdown($flashcard->display_back ?? $flashcard->back, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                        <p class="mt-5 text-center text-sm">Sejauh mana kamu ingat?</p>
                        <div class="live-card-review">
                            @foreach(['sulit' => 'Masih sulit', 'ulang' => 'Ulang lagi', 'paham' => 'Sudah paham'] as $status => $label)
                                <form method="POST" action="{{ route('flashcards.review', $flashcard) }}" @submit.prevent="review($el)">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="study_status" value="{{ $status }}">
                                    <button :disabled="saving" class="disabled:opacity-50" x-text="saving ? 'Menyimpan...' : @js($label)">{{ $label }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap justify-between gap-2 text-xs text-slate-500">
                        <span x-text="`Diulang ${cards[{{ $loop->index }}].review_count} kali`">Diulang {{ $flashcard->review_count }} kali</span>
                    </div>
                </article>
            @empty
                <section class="live-panel p-8 text-center">
                    <i data-lucide="layers" class="mx-auto mb-4 h-10 w-10 text-slate-400" aria-hidden="true"></i>
                    <h2 class="text-lg font-semibold">Belum ada kartu belajar</h2>
                    <p class="mt-2 text-sm text-slate-500">Buat kartu dari materi ini, lalu mulai ulang konsep pentingnya.</p>
                    <a href="{{ isset($room) && $room ? route('study-rooms.show', $room) : $sourceUrl.'#learn-flashcard' }}" class="button button-primary mt-5">Buka materi</a>
                </section>
            @endforelse

            <p x-show="error" x-cloak x-text="error" role="alert" class="mt-3 text-sm text-red-700"></p>
            <p x-show="notice" x-cloak x-text="notice" role="status" class="mt-3 text-sm text-slate-600"></p>

            @if($flashcards->count() > 1)
                <div class="live-card-controls">
                    <button type="button" @click="go(current - 1)" :disabled="saving || current === 0">
                        <i data-lucide="arrow-left" aria-hidden="true"></i> Sebelumnya
                    </button>
                    <button type="button" @click="go(current + 1)" :disabled="saving || current === total - 1">
                        Berikutnya <i data-lucide="arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            @endif
            @if($flashcards->isNotEmpty())
                <section class="live-deck-list" aria-label="Daftar kartu">
                    <h2>Daftar kartu <span>{{ $flashcards->count() }}</span></h2>
                    <ol>
                        @foreach($flashcards as $flashcard)
                            <li x-show="listPage === {{ intdiv($loop->index, 5) }}" @if($loop->index >= 5) x-cloak @endif>
                                <button type="button" @click="go({{ $loop->index }})" :disabled="saving"
                                        :aria-current="current === {{ $loop->index }} ? 'true' : null">
                                    <span class="live-deck-number">{{ $loop->iteration }}</span>
                                    <span class="live-deck-title">{{ strip_tags(Str::markdown($flashcard->front, ['html_input' => 'strip', 'allow_unsafe_links' => false])) }}</span>
                                    <span class="live-deck-status live-card-status" data-status="{{ $flashcard->study_status }}" :data-status="cards[{{ $loop->index }}].study_status" x-text="labels[cards[{{ $loop->index }}].study_status]">{{ $statusLabels[$flashcard->study_status] ?? 'Baru' }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ol>
                    @if($flashcards->count() > 5)
                        <div class="live-card-controls">
                            <button type="button" @click="listPage--" :disabled="saving || listPage === 0">Sebelumnya</button>
                            <span x-text="`${listPage + 1} / ${Math.ceil(total / 5)}`" aria-live="polite"></span>
                            <button type="button" @click="listPage++" :disabled="saving || listPage >= Math.ceil(total / 5) - 1">Selanjutnya</button>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
