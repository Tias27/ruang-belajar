<x-app-layout title="{{ $folder->name }}">
    @php
        $processedDocuments = $folder->documents->filter(fn ($document) => filled($document->extracted_text))->count();
        $note = $folder->notes->first();
    @endphp
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-campus-700">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Materi Saya
                </a>
                <h1 class="mt-3 break-words text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $folder->name }}</h1>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-md bg-accent-50 px-2.5 py-1 font-semibold text-accent-700">Folder Gabungan</span>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-medium text-slate-600">{{ $folder->documents_count }} dokumen</span>
                </div>
                @if($folder->description)
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-500">{{ $folder->description }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('folders.destroy', $folder) }}" onsubmit="return confirm('Hapus folder ini? Semua file dan hasil belajar di dalam folder juga ikut terhapus.')">
                @csrf @method('DELETE')
                <button class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50">
                    <i data-lucide="trash-2" class="h-4 w-4"></i> Hapus Folder
                </button>
            </form>
        </div>
    </section>

    <div class="mt-5 flex items-start gap-3 rounded-lg border border-accent-100 bg-accent-50 px-4 py-3 text-sm text-accent-700">
        <i data-lucide="folder-check" class="mt-0.5 h-4 w-4 shrink-0"></i>
        <span>AI membaca semua file dalam folder <strong>{{ $folder->name }}</strong> sebagai satu paket. {{ $processedDocuments }}/{{ $folder->documents_count }} file sudah terbaca.</span>
    </div>

    <section class="mt-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-campus-700">Folder Gabungan</p>
                <h2 class="mt-1 text-xl font-semibold text-campus-900">Belajar dari banyak file sekaligus</h2>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-lg {{ $processedDocuments === $folder->documents_count && $folder->documents_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-2 text-xs font-semibold">
                <i data-lucide="{{ $processedDocuments === $folder->documents_count && $folder->documents_count > 0 ? 'check-circle' : 'alert-circle' }}" class="h-4 w-4"></i>
                {{ $processedDocuments === $folder->documents_count && $folder->documents_count > 0 ? 'Paket siap AI' : 'Sebagian file belum terbaca' }}
            </span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-4">
            @foreach([
                ['title' => 'Ringkas', 'text' => 'Ambil inti dari semua file.', 'icon' => 'notebook-tabs'],
                ['title' => 'Tanya AI', 'text' => 'Tanya isi seluruh folder.', 'icon' => 'messages-square'],
                ['title' => 'Latihan', 'text' => 'Buat soal dari gabungan materi.', 'icon' => 'list-checks'],
                ['title' => 'Flashcard', 'text' => 'Ulang poin penting.', 'icon' => 'copy-check'],
            ] as $step)
                <div class="rounded-lg bg-slate-50 p-4">
                    <i data-lucide="{{ $step['icon'] }}" class="h-5 w-5 text-campus-700"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $step['title'] }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <form method="POST" action="{{ route('folders.summaries.store', $folder) }}" x-data="{loading:false}" x-on:submit="loading=true">
            @csrf
            <button x-bind:disabled="loading" class="flex h-full w-full items-center gap-3 rounded-lg border border-campus-100 bg-campus-700 p-4 text-left text-white shadow-sm hover:bg-campus-900 disabled:cursor-wait disabled:opacity-75">
                <span x-show="!loading"><i data-lucide="notebook-tabs" class="h-5 w-5 shrink-0"></i></span>
                <span x-show="loading" x-cloak class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                <span><span class="block text-sm font-semibold" x-text="loading ? 'Meringkas...' : 'Ringkas'"></span><span class="mt-1 block text-xs text-campus-100" x-text="loading ? 'AI sedang membaca semua file.' : 'Inti dari semua file.'"></span></span>
            </button>
        </form>
        <form method="POST" action="{{ route('folders.chat.create', $folder) }}">
            @csrf
            <button class="flex h-full w-full items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-campus-100 hover:bg-campus-50">
                <i data-lucide="messages-square" class="h-5 w-5 shrink-0 text-campus-700"></i>
                <span><span class="block text-sm font-semibold text-slate-900">Tanya AI</span><span class="mt-1 block text-xs text-slate-500">Tanya isi folder ini.</span></span>
            </button>
        </form>
        <form method="POST" action="{{ route('folders.quizzes.store', $folder) }}" x-data="{loading:false}" x-on:submit="loading=true">
            @csrf
            <button x-bind:disabled="loading" class="flex h-full w-full items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-campus-100 hover:bg-campus-50 disabled:cursor-wait disabled:opacity-75">
                <span x-show="!loading"><i data-lucide="list-checks" class="h-5 w-5 shrink-0 text-campus-700"></i></span>
                <span x-show="loading" x-cloak class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                <span><span class="block text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat latihan...' : 'Latihan Soal'"></span><span class="mt-1 block text-xs text-slate-500" x-text="loading ? 'AI sedang menyusun soal.' : 'Dari gabungan folder.'"></span></span>
            </button>
        </form>
        <form method="POST" action="{{ route('folders.flashcards.store', $folder) }}" x-data="{loading:false}" x-on:submit="loading=true">
            @csrf
            <button x-bind:disabled="loading" class="flex h-full w-full items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-campus-100 hover:bg-campus-50 disabled:cursor-wait disabled:opacity-75">
                <span x-show="!loading"><i data-lucide="copy-check" class="h-5 w-5 shrink-0 text-campus-700"></i></span>
                <span x-show="loading" x-cloak class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                <span><span class="block text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat flashcard...' : 'Flashcard'"></span><span class="mt-1 block text-xs text-slate-500" x-text="loading ? 'AI sedang memilih poin penting.' : 'Kartu dari semua materi.'"></span></span>
            </button>
        </form>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1.2fr_.8fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" x-data="{
            selected: [],
            allIds: @js($folder->documents->pluck('public_id')->values()),
            get allSelected() {
                return this.allIds.length > 0 && this.selected.length === this.allIds.length;
            },
            toggleAll() {
                this.selected = this.allSelected ? [] : [...this.allIds];
            },
        }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-semibold text-slate-900">File Dalam Folder</h2>
                @if($folder->documents->count() > 0)
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" x-on:click="toggleAll()" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            <i data-lucide="check-square" class="h-3.5 w-3.5"></i>
                            <span x-text="allSelected ? 'Batal pilih semua' : 'Pilih semua'"></span>
                        </button>
                        <form method="POST" action="{{ route('documents.bulk-destroy') }}" onsubmit="return selected.length > 0 && confirm('Hapus ' + selected.length + ' file terpilih dari folder ini? Hasil belajar terkait juga ikut terhapus.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                            <template x-for="id in selected" :key="id">
                                <input type="hidden" name="document_ids[]" :value="id">
                            </template>
                            <button x-bind:disabled="selected.length === 0" class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-rose-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                <span>Hapus terpilih</span>
                                <span x-show="selected.length > 0" x-text="selected.length" class="rounded-md bg-white/20 px-1.5 py-0.5"></span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="mt-4 space-y-2">
                @forelse($folder->documents as $document)
                    <div class="flex flex-col gap-3 rounded-lg bg-slate-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <label class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white shadow-sm">
                                <input type="checkbox" value="{{ $document->public_id }}" x-model="selected" aria-label="Pilih {{ $document->title }}">
                            </label>
                            <a href="{{ route('documents.show', $document) }}" class="min-w-0 flex-1 font-medium text-slate-700 hover:text-campus-700">
                                <span class="block truncate">{{ $document->title }}</span>
                                <span class="mt-1 block truncate text-xs font-normal text-slate-500">{{ $document->original_name }}</span>
                                <span class="mt-2 inline-flex rounded-md {{ filled($document->extracted_text) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2 py-1 text-xs font-semibold">
                                    {{ filled($document->extracted_text) ? 'Siap AI' : 'Belum terbaca' }}
                                </span>
                            </a>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                            <a href="{{ route('documents.show', $document) }}" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-campus-100 bg-white px-2.5 text-xs font-semibold text-campus-700 hover:bg-campus-50">
                                <i data-lucide="book-open" class="h-3.5 w-3.5"></i> Detail
                            </a>
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Hapus file ini dari folder? Hasil belajar terkait juga ikut terhapus.')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                                <button class="inline-flex h-8 w-full items-center justify-center gap-1 rounded-lg border border-rose-200 bg-white px-2.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada dokumen di folder ini.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-5 rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="font-semibold text-slate-900">Catatan Folder</h2>
                    <p class="mt-1 text-sm text-slate-500">Catatan gabungan untuk paket materi ini.</p>
                </div>
                <form method="POST" action="{{ route('folders.notes.store', $folder) }}" class="p-4">
                    @csrf
                    <textarea name="content" rows="6" placeholder="Contoh: minggu ini fokus ulang bagian normalisasi..." class="resize-y px-4 py-3 leading-7 placeholder:text-slate-400">{{ old('content', $note?->content) }}</textarea>
                    <button class="mt-3 inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                        <i data-lucide="save" class="h-4 w-4"></i> Simpan catatan
                    </button>
                </form>
            </div>
            <h2 class="font-semibold text-slate-900">Hasil AI Folder</h2>
            <div class="mt-4 space-y-3 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400">Ringkasan</p>
                    <div class="mt-2 space-y-2">
                        @forelse($folder->summaries as $summary)
                            <a href="{{ route('summaries.show', $summary) }}" class="block rounded-lg bg-slate-50 p-3 font-medium text-campus-700">{{ $summary->created_at->format('d M Y H:i') }}</a>
                        @empty
                            <p class="text-slate-500">Belum ada.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400">Soal</p>
                    <div class="mt-2 space-y-2">
                        @forelse($folder->quizzes as $quiz)
                            <a href="{{ route('quizzes.show', $quiz) }}" class="block rounded-lg bg-slate-50 p-3 font-medium text-campus-700">{{ $quiz->title }}</a>
                        @empty
                            <p class="text-slate-500">Belum ada.</p>
                        @endforelse
                    </div>
                </div>
                <a href="{{ route('folders.flashcards.index', $folder) }}" class="flex items-center justify-between rounded-lg bg-slate-50 p-3 font-medium text-campus-700">
                    <span>{{ $folder->flashcards_count }} flashcard folder</span>
                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                </a>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-400">Tanya AI</p>
                    <div class="mt-2 space-y-2">
                        @forelse($folder->chatSessions as $session)
                            <a href="{{ route('chat.show', $session) }}" class="block rounded-lg bg-slate-50 p-3 font-medium text-campus-700">{{ $session->title }}</a>
                        @empty
                            <p class="text-slate-500">Belum ada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
