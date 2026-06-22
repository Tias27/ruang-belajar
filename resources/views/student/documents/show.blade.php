<x-app-layout title="{{ $document->title }}">
    <div class="min-w-0 overflow-x-hidden" x-data="{ 
        aiBusy: false,
        setBusy(val) {
            this.aiBusy = val;
        },
        confirmOpen: false,
        confirmTitle: '',
        confirmMessage: '',
        confirmAction: null,
        triggerConfirm(title, message, callback) {
            this.confirmTitle = title;
            this.confirmMessage = message;
            this.confirmAction = callback;
            this.confirmOpen = true;
        },
        executeConfirm() {
            if (this.confirmAction) {
                this.confirmAction();
            }
            this.confirmOpen = false;
        }
    }">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <div class="flex min-w-0 flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm hover:bg-campus-100">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i> Materi Saya
                    </a>
                    <div class="mt-5 flex min-w-0 items-start gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-[1.25rem] bg-white text-campus-700 shadow-sm">
                            <i data-lucide="file-text" class="h-7 w-7"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-campus-700">File materi</p>
                            <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $document->title }}</h1>
                            <div class="mt-3 flex min-w-0 flex-wrap gap-2 text-xs">
                                <span class="max-w-full truncate rounded-full bg-white px-3 py-1.5 font-semibold text-slate-700 shadow-sm">{{ $document->original_name }}</span>
                                <span class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-700 shadow-sm">{{ strtoupper($document->extension) }}</span>
                                <span class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-700 shadow-sm">{{ number_format($document->size / 1024 / 1024, 2) }} MB</span>
                                <span class="rounded-full {{ filled($document->extracted_text) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1.5 font-semibold">{{ filled($document->extracted_text) ? 'Siap AI' : 'Belum terbaca' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                    <a href="{{ route('documents.download', $document) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">
                        <i data-lucide="download" class="h-4 w-4"></i> Unduh
                    </a>
                    <form method="POST" action="{{ route('documents.destroy', $document) }}" x-ref="deleteDocForm">
                        @csrf
                        @method('DELETE')
                        <button type="button" x-bind:disabled="aiBusy" @click="if (!aiBusy) triggerConfirm('Hapus File?', 'Hapus file ini? Semua hasil belajar terkait juga akan terhapus secara permanen.', () => $refs.deleteDocForm.submit())" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-white px-4 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i data-lucide="trash-2" class="h-4 w-4"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-6 grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <form method="POST" action="{{ route('summaries.store', $document) }}" x-data="{loading:false}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } setBusy(true); loading=true" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy || loading" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span x-show="!loading" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700"><i data-lucide="notebook-tabs" class="h-5 w-5"></i></span>
                        <span x-show="loading" x-cloak class="h-10 w-10 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                        <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900" x-text="loading ? 'Sedang meringkas...' : 'Ringkas materi'"></span><span class="mt-1 block text-xs leading-5 text-slate-500" x-text="loading ? 'AI sedang membaca dan menyusun hasil.' : 'Inti, poin penting, kesimpulan.'"></span></span>
                    </button>
                </form>
                <form method="POST" action="{{ route('chat.create', $document) }}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } setBusy(true)" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700"><i data-lucide="messages-square" class="h-5 w-5"></i></span>
                        <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">Tanya materi</span><span class="mt-1 block text-xs leading-5 text-slate-500">Chat berdasarkan file ini.</span></span>
                    </button>
                </form>
                <form method="POST" action="{{ route('quizzes.store', $document) }}" x-data="{loading:false, open:false, type:'multiple_choice', count:10}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } setBusy(true); loading=true" class="min-w-0">
                    @csrf
                    <button type="button" x-show="!open" x-on:click="if (! aiBusy) open=true" x-bind:disabled="aiBusy" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span x-show="!loading" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700"><i data-lucide="list-checks" class="h-5 w-5"></i></span>
                        <span x-show="loading" x-cloak class="h-10 w-10 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                        <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900">Buat soal</span><span class="mt-1 block text-xs leading-5 text-slate-500">Pilih PG/esai dan jumlah.</span></span>
                    </button>
                    <div x-show="open" x-cloak class="rounded-[1.25rem] bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat soal...' : 'Atur soal'"></p>
                            <button type="button" x-on:click="open=false" x-bind:disabled="aiBusy || loading" class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 disabled:cursor-wait disabled:opacity-60" title="Tutup">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <input type="hidden" name="question_type" x-bind:value="type">
                        <input type="hidden" name="question_count" x-bind:value="count">
                        <div class="mt-3 grid grid-cols-2 gap-2 rounded-full bg-slate-100 p-1">
                            <button type="button" x-on:click="type='multiple_choice'" class="h-10 rounded-full text-xs font-semibold transition" x-bind:class="type === 'multiple_choice' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500'">Pilihan ganda</button>
                            <button type="button" x-on:click="type='essay'" class="h-10 rounded-full text-xs font-semibold transition" x-bind:class="type === 'essay' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500'">Esai</button>
                        </div>
                        <div class="mt-3 rounded-[1.1rem] bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-slate-500">Jumlah soal</p>
                                    <p class="mt-1 text-xs text-slate-500">Maksimal 30</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" x-on:click="count = Math.max(1, Number(count) - 1)" class="grid h-9 w-9 place-items-center rounded-full bg-white text-slate-600 shadow-sm"><i data-lucide="minus" class="h-4 w-4"></i></button>
                                    <input type="number" min="1" max="30" x-model="count" class="h-9 w-16 rounded-full bg-white py-0 text-center text-sm font-semibold">
                                    <button type="button" x-on:click="count = Math.min(30, Number(count) + 1)" class="grid h-9 w-9 place-items-center rounded-full bg-white text-slate-600 shadow-sm"><i data-lucide="plus" class="h-4 w-4"></i></button>
                                </div>
                            </div>
                        </div>
                        <button x-bind:disabled="aiBusy || loading" class="mt-3 h-11 w-full rounded-full bg-campus-700 px-3 text-sm font-semibold text-white disabled:cursor-wait disabled:opacity-75">
                            <span x-text="loading ? 'Membuat soal...' : 'Buat soal'"></span>
                        </button>
                    </div>
                </form>
                <form method="POST" action="{{ route('flashcards.store', $document) }}" x-data="{loading:false}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } setBusy(true); loading=true" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy || loading" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span x-show="!loading" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700"><i data-lucide="copy-check" class="h-5 w-5"></i></span>
                        <span x-show="loading" x-cloak class="h-10 w-10 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                        <span class="min-w-0"><span class="block truncate text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat kartu...' : 'Kartu belajar'"></span><span class="mt-1 block text-xs leading-5 text-slate-500">Flashcard singkat.</span></span>
                    </button>
                </form>

                <form method="POST" action="{{ route('documents.study-rooms.store', $document) }}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } setBusy(true)" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700"><i data-lucide="users" class="h-5 w-5"></i></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">Belajar bareng</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Mulai room real-time.</span>
                        </span>
                    </button>
                </form>
            </div>
        </section>

        @if($document->processing_notes)
            <div class="mt-5 flex min-w-0 gap-3 rounded-[1.25rem] bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <span class="min-w-0 break-words">{{ $document->processing_notes }}</span>
            </div>
        @endif

        <div class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,.85fr)]">
            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-slate-900">Teks yang dibaca AI</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ filled($document->extracted_text) ? number_format(strlen($document->extracted_text)).' karakter siap dipakai AI.' : 'Belum ada teks yang berhasil dibaca.' }}</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full {{ filled($document->extracted_text) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-2 text-xs font-semibold">
                        <i data-lucide="{{ filled($document->extracted_text) ? 'check-circle' : 'alert-circle' }}" class="h-4 w-4"></i>
                        {{ filled($document->extracted_text) ? 'Siap AI' : 'Perlu cek file' }}
                    </span>
                </div>
                <div class="mt-4 max-h-80 overflow-y-auto rounded-[1.25rem] bg-slate-50 p-4 text-sm leading-7 text-slate-700">
                    @if(filled($document->extracted_text))
                        {{ \Illuminate\Support\Str::limit($document->extracted_text, 3500) }}
                    @else
                        Teks dokumen belum tersedia. Kalau ini PDF hasil scan, coba pakai PDF yang punya teks asli atau lakukan OCR dulu.
                    @endif
                </div>
            </section>

            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Catatan pribadi</h2>
                <p class="mt-1 text-sm text-slate-500">Tulis poin penting versi kamu sendiri.</p>
                <form method="POST" action="{{ route('documents.notes.store', $document) }}" class="mt-4">
                    @csrf
                    <textarea name="content" rows="9" placeholder="Contoh: bagian REST API masih perlu diulang..." class="resize-y px-4 py-3 leading-7 placeholder:text-slate-400">{{ old('content', $document->notes->first()?->content) }}</textarea>
                    <button class="mt-3 inline-flex h-11 w-full items-center justify-center gap-2 rounded-full bg-campus-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                        <i data-lucide="save" class="h-4 w-4"></i> Simpan catatan
                    </button>
                </form>
            </section>
        </div>

        <section class="mt-5 grid min-w-0 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['title' => 'Ringkasan', 'count' => $document->summaries_count, 'items' => $document->summaries, 'empty' => 'Belum ada ringkasan.', 'type' => 'summary'],
                ['title' => 'Latihan soal', 'count' => $document->quizzes_count, 'items' => $document->quizzes, 'empty' => 'Belum ada soal.', 'type' => 'quiz'],
            ] as $block)
                <section class="min-w-0 overflow-hidden rounded-[1.25rem] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-slate-900">{{ $block['title'] }}</h2>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ $block['count'] }}</span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @forelse($block['items'] as $item)
                            <a href="{{ $block['type'] === 'summary' ? route('summaries.show', $item) : route('quizzes.show', $item) }}" class="block truncate rounded-xl bg-slate-50 p-3 text-sm font-medium text-campus-700 hover:bg-campus-50">
                                {{ $block['type'] === 'summary' ? $item->created_at->format('d M Y H:i') : $item->title }}
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">{{ $block['empty'] }}</p>
                        @endforelse
                    </div>
                </section>
            @endforeach

            <section class="min-w-0 overflow-hidden rounded-[1.25rem] bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Kartu belajar</h2>
                <a href="{{ route('flashcards.index', $document) }}" class="mt-4 flex min-w-0 items-center justify-between gap-3 rounded-xl bg-slate-50 p-3 text-sm font-medium text-campus-700 hover:bg-campus-50">
                    <span class="min-w-0 truncate">{{ $document->flashcards_count }} kartu tersedia</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 shrink-0"></i>
                </a>
            </section>
            <section class="min-w-0 overflow-hidden rounded-[1.25rem] bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Riwayat tanya</h2>
                <div class="mt-4 space-y-2">
                    @forelse($document->chatSessions as $session)
                        <a href="{{ route('chat.show', $session) }}" class="block truncate rounded-xl bg-slate-50 p-3 text-sm font-medium text-campus-700 hover:bg-campus-50">{{ $session->title }}</a>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada percakapan.</p>
                    @endforelse
                </div>
            </section>
        </section>
        <!-- Custom Confirmation Modal -->
        <div x-show="confirmOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="w-full max-w-sm transform overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-2xl border border-slate-100 text-center" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 shadow-inner mb-4">
                    <i data-lucide="alert-triangle" class="h-7 w-7"></i>
                </div>

                <h3 class="text-lg font-bold text-slate-800 tracking-tight" x-text="confirmTitle">Konfirmasi</h3>
                <p class="mt-2 text-sm text-slate-500 leading-relaxed" x-text="confirmMessage"></p>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="confirmOpen = false" class="inline-flex justify-center rounded-full bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">Batal</button>
                    <button type="button" @click="executeConfirm()" class="inline-flex justify-center rounded-full bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
