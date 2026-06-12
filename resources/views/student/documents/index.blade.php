<x-app-layout title="Materi Saya" subtitle="Buka materi untuk ringkas, tanya AI, latihan, atau flashcard">
    <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
        <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
            <div>
                <span class="inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-campus-700 shadow-sm">Materi Saya</span>
                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-campus-900">Pilih materi untuk belajar</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Folder gabungan untuk banyak file sekaligus. File satuan untuk satu dokumen yang dipelajari sendiri.</p>
            </div>
            <a href="{{ route('documents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Materi
            </a>
        </div>

        <form method="GET" action="{{ route('documents.index') }}" class="mt-5 flex flex-col gap-3 rounded-[1.25rem] bg-white p-3 shadow-sm sm:flex-row">
            <div class="relative min-w-0 flex-1">
                <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input name="q" value="{{ $search }}" placeholder="Cari nama materi atau isi file..." class="h-12 rounded-full border-slate-200 bg-slate-50" style="padding-left: 2.75rem;">
            </div>
            <button class="inline-flex h-12 min-w-24 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-campus-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                <i data-lucide="search" class="h-4 w-4 shrink-0"></i> Cari
            </button>
            @if($search !== '')
                <a href="{{ route('documents.index') }}" class="inline-flex h-12 min-w-28 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-slate-100 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                    <i data-lucide="x" class="h-4 w-4 shrink-0"></i> Bersihkan
                </a>
            @endif
        </form>
        @if($search !== '')
            <p class="mt-3 text-sm text-slate-600">Hasil pencarian untuk <strong class="text-slate-900">{{ $search }}</strong>.</p>
        @endif
    </section>

    @if($folders->count() > 0)
        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Folder Gabungan</h2>
                    <p class="mt-1 text-sm text-slate-500">Banyak file yang dibaca AI sebagai satu paket.</p>
                </div>
                <span class="rounded-full bg-accent-50 px-3 py-1 text-xs font-semibold text-accent-700">{{ $folders->total() }} folder</span>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($folders as $folder)
                    <a href="{{ route('folders.show', $folder) }}" class="group flex min-h-28 items-center gap-3 rounded-[1.25rem] bg-white p-4 shadow-sm transition hover:bg-accent-50">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700">
                            <i data-lucide="folder" class="h-5 w-5"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-accent-700">{{ $folder->name }}</span>
                            <span class="mt-1 block text-xs text-slate-500">{{ $folder->documents_count }} file dibaca sebagai satu materi</span>
                        </span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 text-slate-400 group-hover:text-accent-700"></i>
                    </a>
                @endforeach
            </div>
            @if($folders->hasPages())
                <div class="mt-4">{{ $folders->links() }}</div>
            @endif
        </section>
    @endif

    <section class="mt-6" x-data="{
        selected: [],
        allIds: @js($documents->pluck('public_id')->values()),
        get allSelected() {
            return this.allIds.length > 0 && this.selected.length === this.allIds.length;
        },
        toggleAll() {
            this.selected = this.allSelected ? [] : [...this.allIds];
        },
    }">
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">File Satuan</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $documents->total() }} dokumen terpisah.</p>
            </div>
            @if($documents->count() > 0)
                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="button" x-on:click="toggleAll()" class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-white px-4 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        <i data-lucide="check-square" class="h-3.5 w-3.5"></i>
                        <span x-text="allSelected ? 'Batal pilih semua' : 'Pilih semua'"></span>
                    </button>
                    <form method="POST" action="{{ route('documents.bulk-destroy') }}" onsubmit="return selected.length > 0 && confirm('Hapus ' + selected.length + ' file terpilih? Hasil belajar terkait juga ikut terhapus.')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="document_ids[]" :value="id">
                        </template>
                        <button x-bind:disabled="selected.length === 0" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-rose-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                            <span>Hapus</span>
                            <span x-show="selected.length > 0" x-text="selected.length" class="rounded-full bg-white/20 px-1.5 py-0.5"></span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-3">
            @forelse($documents as $document)
                <article class="rounded-[1.25rem] bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <label class="mt-2 grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-slate-50">
                                <input type="checkbox" value="{{ $document->public_id }}" x-model="selected" aria-label="Pilih {{ $document->title }}">
                            </label>
                            <a href="{{ route('documents.show', $document) }}" class="flex min-w-0 flex-1 items-start gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                                    <i data-lucide="file-text" class="h-5 w-5"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $document->title }}</span>
                                    <span class="mt-1 block truncate text-xs text-slate-500">{{ $document->original_name }}</span>
                                    <span class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ strtoupper($document->extension) }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ number_format($document->size / 1024 / 1024, 2) }} MB</span>
                                        <span class="rounded-full {{ $document->status === 'processed' ? 'bg-campus-50 text-campus-700' : ($document->status === 'processing' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }} px-2.5 py-1 font-semibold">
                                            {{ $document->status === 'processed' ? 'Siap AI' : ($document->status === 'processing' ? 'Membaca file' : 'Belum terbaca') }}
                                        </span>
                                    </span>
                                </span>
                            </a>
                        </div>

                        <div class="grid grid-cols-2 gap-2 xl:flex xl:flex-wrap xl:justify-end">
                            <form method="POST" action="{{ route('summaries.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">@csrf
                                <button x-bind:disabled="loading" class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200 disabled:cursor-wait disabled:opacity-75">
                                    <span x-show="!loading"><i data-lucide="notebook-tabs" class="h-3.5 w-3.5"></i></span>
                                    <span x-show="loading" x-cloak class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-campus-700"></span>
                                    <span x-text="loading ? 'Membuat...' : 'Ringkas'"></span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('chat.create', $document) }}">@csrf
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-full bg-campus-50 px-3 text-xs font-semibold text-campus-700 hover:bg-campus-100"><i data-lucide="messages-square" class="h-3.5 w-3.5"></i> Tanya</button>
                            </form>
                            <form method="POST" action="{{ route('quizzes.store', $document) }}" x-data="{loading:false, open:false, type:'multiple_choice', count:10}" x-on:submit="loading=true" class="col-span-2 sm:col-span-1 xl:col-span-auto">@csrf
                                <button type="button" x-show="!open" x-on:click="open=true" class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                    <i data-lucide="list-checks" class="h-3.5 w-3.5"></i> Latihan
                                </button>
                                <div x-show="open" x-cloak class="rounded-2xl bg-slate-50 p-2 shadow-sm">
                                    <input type="hidden" name="question_type" x-bind:value="type">
                                    <input type="hidden" name="question_count" x-bind:value="count">
                                    <div class="grid grid-cols-2 gap-1 rounded-full bg-slate-200/70 p-1">
                                        <button type="button" x-on:click="type='multiple_choice'" class="h-8 rounded-full text-xs font-semibold" x-bind:class="type === 'multiple_choice' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500'">PG</button>
                                        <button type="button" x-on:click="type='essay'" class="h-8 rounded-full text-xs font-semibold" x-bind:class="type === 'essay' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500'">Esai</button>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <button type="button" x-on:click="count = Math.max(1, Number(count) - 1)" class="grid h-8 w-8 place-items-center rounded-full bg-white text-slate-600"><i data-lucide="minus" class="h-3.5 w-3.5"></i></button>
                                        <input type="number" min="1" max="30" x-model="count" class="h-8 min-w-0 flex-1 rounded-full bg-white py-0 text-center text-xs font-semibold">
                                        <button type="button" x-on:click="count = Math.min(30, Number(count) + 1)" class="grid h-8 w-8 place-items-center rounded-full bg-white text-slate-600"><i data-lucide="plus" class="h-3.5 w-3.5"></i></button>
                                    </div>
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        <button type="button" x-on:click="open=false" x-bind:disabled="loading" class="h-8 rounded-full bg-white text-xs font-semibold text-slate-600">Batal</button>
                                        <button x-bind:disabled="loading" class="h-8 rounded-full bg-campus-700 text-xs font-semibold text-white disabled:cursor-wait disabled:opacity-75">
                                            <span x-text="loading ? 'Membuat...' : 'Buat'"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('flashcards.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">@csrf
                                <button x-bind:disabled="loading" class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200 disabled:cursor-wait disabled:opacity-75">
                                    <span x-show="!loading"><i data-lucide="copy-check" class="h-3.5 w-3.5"></i></span>
                                    <span x-show="loading" x-cloak class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-300 border-t-campus-700"></span>
                                    <span x-text="loading ? 'Membuat...' : 'Flashcard'"></span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Hapus file ini? Ringkasan, latihan, flashcard, dan riwayat AI terkait juga ikut terhapus.')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-full bg-rose-50 px-3 text-xs font-semibold text-rose-700 hover:bg-rose-100"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[1.5rem] bg-white p-8 text-center shadow-sm">
                    <i data-lucide="file-plus-2" class="mx-auto h-9 w-9 text-slate-400"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada file satuan.</p>
                    <p class="mt-1 text-sm text-slate-500">Klik Upload Materi untuk mulai.</p>
                </div>
            @endforelse
        </div>

        @if($documents->hasPages())
            <div class="mt-4">{{ $documents->links() }}</div>
        @endif
    </section>
</x-app-layout>
