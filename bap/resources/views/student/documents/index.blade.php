<x-app-layout title="Pustaka Materi" subtitle="Pilih materi untuk belajar">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-campus-700">Pustaka Materi</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900">Materi kuliahmu</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Folder dipakai untuk belajar dari beberapa file sekaligus. Dokumen mandiri dipakai untuk satu materi tertentu.</p>
            </div>
            <a href="{{ route('documents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                <i data-lucide="folder-plus" class="h-4 w-4"></i> Tambah Materi
            </a>
        </div>
    </section>

    <section class="mt-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('documents.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative min-w-0 flex-1">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input name="q" value="{{ $search }}" placeholder="Cari judul, nama file, folder, atau isi materi..." class="pl-9">
            </div>
            <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                <i data-lucide="search" class="h-4 w-4"></i> Cari
            </button>
            @if($search !== '')
                <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="x" class="h-4 w-4"></i> Bersihkan
                </a>
            @endif
        </form>
        @if($search !== '')
            <p class="mt-3 text-sm text-slate-500">Hasil pencarian untuk <strong class="text-slate-800">{{ $search }}</strong>.</p>
        @endif
    </section>

    @if($folders->count() > 0)
        <section class="mt-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Folder belajar</h2>
                <span class="text-xs font-medium text-slate-500">{{ $folders->total() }} folder</span>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($folders as $folder)
                    <a href="{{ route('folders.show', $folder) }}" class="group rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-accent-100 hover:bg-accent-50">
                        <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-accent-50 text-accent-700">
                                <i data-lucide="folder" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $folder->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $folder->documents_count }} file dibaca sebagai satu materi</p>
                            </div>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400 group-hover:text-accent-700"></i>
                        </div>
                    </a>
                @endforeach
            </div>
            @if($folders->hasPages())
                <div class="mt-4">{{ $folders->links() }}</div>
            @endif
        </section>
    @endif

    <section class="mt-5" x-data="{
        selected: [],
        allIds: @js($documents->pluck('public_id')->values()),
        get allSelected() {
            return this.allIds.length > 0 && this.selected.length === this.allIds.length;
        },
        toggleAll() {
            this.selected = this.allSelected ? [] : [...this.allIds];
        },
    }">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Dokumen mandiri</h2>
            <span class="text-xs font-medium text-slate-500">{{ $documents->total() }} dokumen</span>
        </div>
        @if($documents->count() > 0)
            <div class="mb-3 flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <button type="button" x-on:click="toggleAll()" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="check-square" class="h-3.5 w-3.5"></i>
                    <span x-text="allSelected ? 'Batal pilih semua' : 'Pilih semua di halaman ini'"></span>
                </button>
                <form method="POST" action="{{ route('documents.bulk-destroy') }}" onsubmit="return selected.length > 0 && confirm('Hapus ' + selected.length + ' file terpilih? Hasil belajar terkait juga ikut terhapus.')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="document_ids[]" :value="id">
                    </template>
                    <button x-bind:disabled="selected.length === 0" class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-rose-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        <span>Hapus terpilih</span>
                        <span x-show="selected.length > 0" x-text="selected.length" class="rounded-md bg-white/20 px-1.5 py-0.5"></span>
                    </button>
                </form>
            </div>
        @endif
        <div class="space-y-3">
            @forelse($documents as $document)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <label class="mt-3 grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white shadow-sm">
                                <input type="checkbox" value="{{ $document->public_id }}" x-model="selected" aria-label="Pilih {{ $document->title }}">
                            </label>
                            <a href="{{ route('documents.show', $document) }}" class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-campus-50 text-campus-700">
                                <i data-lucide="file-text" class="h-5 w-5"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-900">{{ $document->title }}</span>
                                <span class="mt-1 block truncate text-xs text-slate-500">{{ $document->original_name }}</span>
                                <span class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">{{ strtoupper($document->extension) }}</span>
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">{{ number_format($document->size / 1024 / 1024, 2) }} MB</span>
                                    <span class="rounded-md {{ $document->status === 'processed' ? 'bg-emerald-50 text-emerald-700' : ($document->status === 'processing' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }} px-2 py-1 font-semibold">
                                        {{ $document->status === 'processed' ? 'Siap AI' : ($document->status === 'processing' ? 'Membaca file' : 'Belum terbaca') }}
                                    </span>
                                    @if(filled($document->extracted_text))
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">{{ number_format(strlen($document->extracted_text)) }} karakter terbaca</span>
                                    @endif
                                </span>
                            </span>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
                            <a class="inline-flex h-9 items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50" href="{{ route('documents.show', $document) }}">
                                <i data-lucide="book-open" class="h-3.5 w-3.5"></i> Buka
                            </a>
                            <form method="POST" action="{{ route('summaries.store', $document) }}">
                                @csrf
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg bg-campus-700 px-3 text-xs font-semibold text-white shadow-sm hover:bg-campus-900">
                                    <i data-lucide="notebook-tabs" class="h-3.5 w-3.5"></i> Ringkas
                                </button>
                            </form>
                            <form method="POST" action="{{ route('chat.create', $document) }}">
                                @csrf
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg border border-campus-100 bg-campus-50 px-3 text-xs font-semibold text-campus-700 hover:bg-campus-100">
                                    <i data-lucide="messages-square" class="h-3.5 w-3.5"></i> Tanya
                                </button>
                            </form>
                            <form method="POST" action="{{ route('quizzes.store', $document) }}">
                                @csrf
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                    <i data-lucide="list-checks" class="h-3.5 w-3.5"></i> Soal
                                </button>
                            </form>
                            <form method="POST" action="{{ route('flashcards.store', $document) }}">
                                @csrf
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                    <i data-lucide="copy-check" class="h-3.5 w-3.5"></i> Kartu
                                </button>
                            </form>
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Hapus file ini? Ringkasan, soal, kartu belajar, dan riwayat tanya materi terkait juga ikut terhapus.')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                                <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-700 shadow-sm hover:bg-rose-50">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center">
                    <i data-lucide="file-plus-2" class="mx-auto h-8 w-8 text-slate-400"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada dokumen mandiri.</p>
                    <p class="mt-1 text-sm text-slate-500">Unggah file atau buat folder materi dari tombol Tambah Materi.</p>
                </div>
            @endforelse
        </div>

        @if($documents->hasPages())
            <div class="mt-4">{{ $documents->links() }}</div>
        @endif
    </section>
</x-app-layout>
