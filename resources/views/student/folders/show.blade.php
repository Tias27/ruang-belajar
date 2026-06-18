<x-app-layout title="{{ $folder->name }}">
    @php
        $processedDocuments = $folder->documents->filter(fn ($document) => filled($document->extracted_text))->count();
        $isReady = $processedDocuments === $folder->documents_count && $folder->documents_count > 0;
        $note = $folder->notes->first();
    @endphp

    <div class="min-w-0 overflow-x-hidden" x-data="{ aiBusy: false }">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <div class="flex min-w-0 flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm hover:bg-campus-100">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i> Materi Saya
                    </a>
                    <div class="mt-5 flex min-w-0 items-start gap-4">
                        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-[1.25rem] bg-white text-accent-700 shadow-sm">
                            <i data-lucide="folder" class="h-7 w-7"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-accent-700">Folder Gabungan</p>
                            <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $folder->name }}</h1>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1.5 font-semibold text-slate-700 shadow-sm">{{ $folder->documents_count }} file</span>
                                <span class="rounded-full {{ $isReady ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1.5 font-semibold">
                                    {{ $isReady ? 'Siap dipakai AI' : $processedDocuments.'/'.$folder->documents_count.' file terbaca' }}
                                </span>
                            </div>
                            @if($folder->description)
                                <p class="mt-3 max-w-3xl break-words text-sm leading-6 text-slate-600">{{ $folder->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('folders.destroy', $folder) }}" onsubmit="return confirm('Hapus folder ini? Semua file dan hasil belajar di dalam folder juga ikut terhapus.')" class="shrink-0">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50 sm:w-auto">
                        <i data-lucide="trash-2" class="h-4 w-4"></i> Hapus Folder
                    </button>
                </form>
            </div>

            <div class="mt-6 grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <form method="POST" action="{{ route('folders.summaries.store', $folder) }}" x-data="{loading:false}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } aiBusy=true; loading=true" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy || loading" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm transition hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span x-show="!loading" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700"><i data-lucide="notebook-tabs" class="h-5 w-5"></i></span>
                        <span x-show="loading" x-cloak class="h-10 w-10 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900" x-text="loading ? 'Sedang meringkas...' : 'Ringkas folder'"></span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500" x-text="loading ? 'AI membaca semua file dan menyusun hasil.' : 'Ambil inti seluruh materi.'"></span>
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('folders.chat.create', $folder) }}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } aiBusy=true" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm transition hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700"><i data-lucide="messages-square" class="h-5 w-5"></i></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">Tanya materi</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Chat dari isi folder ini.</span>
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('folders.quizzes.store', $folder) }}" x-data="{loading:false, open:false, type:'multiple_choice', count:10}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } aiBusy=true; loading=true" class="min-w-0">
                    @csrf
                    <button type="button" x-show="!open" x-on:click="if (! aiBusy) open=true" x-bind:disabled="aiBusy" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm transition hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span x-show="!loading" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700"><i data-lucide="list-checks" class="h-5 w-5"></i></span>
                        <span x-show="loading" x-cloak class="h-10 w-10 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">Buat soal</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Pilih PG/esai dan jumlah.</span>
                        </span>
                    </button>
                    <div x-show="open" x-cloak class="rounded-[1.25rem] bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat soal...' : 'Atur soal folder'"></p>
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

                 <form method="POST" action="{{ route('folders.flashcards.store', $folder) }}" x-data="{loading:false}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } aiBusy=true; loading=true" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy || loading" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm transition hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span x-show="!loading" class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700"><i data-lucide="copy-check" class="h-5 w-5"></i></span>
                        <span x-show="loading" x-cloak class="h-10 w-10 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat kartu...' : 'Kartu belajar'"></span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500" x-text="loading ? 'AI memilih poin penting.' : 'Flashcard singkat.'"></span>
                        </span>
                    </button>
                </form>

                <form method="POST" action="{{ route('folders.study-rooms.store', $folder) }}" x-on:submit="if (aiBusy) { $event.preventDefault(); return; } aiBusy=true" class="min-w-0">
                    @csrf
                    <button x-bind:disabled="aiBusy" class="flex h-full w-full min-w-0 items-center gap-3 rounded-[1.25rem] bg-white p-4 text-left shadow-sm transition hover:bg-campus-100 disabled:cursor-wait disabled:opacity-75">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-accent-50 text-accent-700"><i data-lucide="users" class="h-5 w-5"></i></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-slate-900">Belajar bareng</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Mulai room real-time.</span>
                        </span>
                    </button>
                </form>
            </div>
        </section>

        <div class="mt-5 flex min-w-0 items-start gap-3 rounded-[1.25rem] bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
            <i data-lucide="folder-check" class="mt-0.5 h-4 w-4 shrink-0 text-accent-700"></i>
            <span class="min-w-0 break-words">AI membaca semua file di folder ini sebagai satu paket. {{ $processedDocuments }}/{{ $folder->documents_count }} file sudah terbaca.</span>
        </div>

        <section class="mt-5 min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm" x-data="{ open:false, files: [] }">
            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Ada materi yang kelupaan?</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">Tambahkan PDF, DOCX, atau PPTX ke folder ini tanpa membuat folder baru.</p>
                </div>
                <button type="button" x-on:click="open = ! open" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-campus-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                    <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                    <span x-text="open ? 'Tutup' : 'Tambah file'"></span>
                </button>
            </div>

            <form x-show="open" x-cloak method="POST" action="{{ route('folders.documents.store', $folder) }}" enctype="multipart/form-data" class="mt-5 rounded-[1.25rem] bg-slate-50 p-3 sm:p-4">
                @csrf
                <label class="flex min-h-36 cursor-pointer flex-col items-center justify-center rounded-[1.1rem] border border-dashed border-campus-200 bg-white px-5 py-6 text-center transition hover:bg-campus-50">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                        <i data-lucide="upload-cloud" class="h-5 w-5"></i>
                    </span>
                    <span class="mt-3 text-sm font-semibold text-slate-900">Klik untuk pilih file tambahan</span>
                    <span class="mt-1 text-xs leading-5 text-slate-500">Maksimal 30 file sekali upload, 20 MB per file.</span>
                    <input name="files[]" type="file" accept=".pdf,.docx,.pptx,.jpg,.jpeg,.png,.gif,.webp" multiple required class="sr-only" x-on:change="files = Array.from($event.target.files)">
                </label>

                <div x-show="files.length" x-cloak class="mt-4 min-w-0">
                    <div class="flex min-w-0 items-center justify-between gap-3">
                        <p class="min-w-0 text-sm font-semibold text-slate-800"><span x-text="files.length"></span> file siap ditambahkan</p>
                        <p class="shrink-0 text-xs text-slate-500">Masuk folder ini</p>
                    </div>
                    <div class="mt-3 max-h-44 min-w-0 space-y-2 overflow-y-auto overflow-x-hidden rounded-2xl bg-white p-2">
                        <template x-for="file in files" :key="file.name">
                            <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-xl bg-slate-50 px-3 py-2">
                                <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-campus-700"></i>
                                <span class="block min-w-0 truncate text-left text-sm text-slate-700" x-bind:title="file.name" x-text="file.name"></span>
                                <span class="shrink-0 text-xs text-slate-500" x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" x-on:click="open=false; files=[]" class="inline-flex justify-center rounded-full bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100">Batal</button>
                    <button class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload ke folder
                    </button>
                </div>
            </form>
        </section>

        <div class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)]">
            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm" x-data="{
                selected: [],
                allIds: @js($folder->documents->pluck('public_id')->values()),
                get allSelected() {
                    return this.allIds.length > 0 && this.selected.length === this.allIds.length;
                },
                toggleAll() {
                    this.selected = this.allSelected ? [] : [...this.allIds];
                },
            }">
                <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-slate-900">File dalam folder</h2>
                        <p class="mt-1 text-sm text-slate-500">Semua file ini dibaca sebagai satu materi gabungan.</p>
                    </div>
                    @if($folder->documents->count() > 0)
                        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                            <button type="button" x-on:click="toggleAll()" class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-slate-100 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-200">
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
                                <button x-bind:disabled="selected.length === 0" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-full bg-rose-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300">
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
                        <div class="min-w-0 rounded-[1.1rem] bg-slate-50 p-3 text-sm">
                            <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 flex-1 items-start gap-3">
                                    <label class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white shadow-sm">
                                        <input type="checkbox" value="{{ $document->public_id }}" x-model="selected" aria-label="Pilih {{ $document->title }}">
                                    </label>
                                    <a href="{{ route('documents.show', $document) }}" class="min-w-0 flex-1 font-medium text-slate-700 hover:text-campus-700">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $document->title }}</span>
                                        <span class="mt-1 block truncate text-xs font-normal text-slate-500">{{ $document->original_name }}</span>
                                        <span class="mt-2 inline-flex rounded-full {{ filled($document->extracted_text) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2.5 py-1 text-xs font-semibold">
                                            {{ filled($document->extracted_text) ? 'Siap AI' : 'Belum terbaca' }}
                                        </span>
                                    </a>
                                </div>
                                <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                                    <a href="{{ route('documents.show', $document) }}" class="inline-flex h-9 items-center justify-center gap-1 rounded-full bg-white px-3 text-xs font-semibold text-campus-700 shadow-sm hover:bg-campus-50">
                                        <i data-lucide="book-open" class="h-3.5 w-3.5"></i> Detail
                                    </a>
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Hapus file ini dari folder? Hasil belajar terkait juga ikut terhapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                                        <button class="inline-flex h-9 w-full items-center justify-center gap-1 rounded-full bg-white px-3 text-xs font-semibold text-rose-700 shadow-sm hover:bg-rose-50">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.25rem] bg-slate-50 p-5 text-sm text-slate-500">
                            Belum ada dokumen di folder ini.
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="min-w-0 space-y-5">
                <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-slate-900">Catatan folder</h2>
                    <p class="mt-1 text-sm text-slate-500">Tulis pengingat singkat untuk paket materi ini.</p>
                    <form method="POST" action="{{ route('folders.notes.store', $folder) }}" class="mt-4">
                        @csrf
                        <textarea name="content" rows="6" placeholder="Contoh: minggu ini fokus ulang bagian normalisasi..." class="resize-y px-4 py-3 leading-7 placeholder:text-slate-400">{{ old('content', $note?->content) }}</textarea>
                        <button class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-full bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                            <i data-lucide="save" class="h-4 w-4"></i> Simpan catatan
                        </button>
                    </form>
                </section>

                <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                    <h2 class="font-semibold text-slate-900">Hasil belajar</h2>
                    <div class="mt-4 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">Ringkasan</p>
                            <div class="mt-2 space-y-2">
                                @forelse($folder->summaries as $summary)
                                    <a href="{{ route('summaries.show', $summary) }}" class="block truncate rounded-xl bg-slate-50 p-3 font-medium text-campus-700 hover:bg-campus-50">{{ $summary->created_at->format('d M Y H:i') }}</a>
                                @empty
                                    <p class="text-slate-500">Belum ada.</p>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">Soal</p>
                            <div class="mt-2 space-y-2">
                                @forelse($folder->quizzes as $quiz)
                                    <a href="{{ route('quizzes.show', $quiz) }}" class="block truncate rounded-xl bg-slate-50 p-3 font-medium text-campus-700 hover:bg-campus-50">{{ $quiz->title }}</a>
                                @empty
                                    <p class="text-slate-500">Belum ada.</p>
                                @endforelse
                            </div>
                        </div>
                        <a href="{{ route('folders.flashcards.index', $folder) }}" class="flex min-w-0 items-center justify-between gap-3 rounded-xl bg-slate-50 p-3 font-medium text-campus-700 hover:bg-campus-50">
                            <span class="min-w-0 truncate">{{ $folder->flashcards_count }} kartu belajar</span>
                            <i data-lucide="arrow-right" class="h-4 w-4 shrink-0"></i>
                        </a>
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">Riwayat tanya</p>
                            <div class="mt-2 space-y-2">
                                @forelse($folder->chatSessions as $session)
                                    <a href="{{ route('chat.show', $session) }}" class="block truncate rounded-xl bg-slate-50 p-3 font-medium text-campus-700 hover:bg-campus-50">{{ $session->title }}</a>
                                @empty
                                    <p class="text-slate-500">Belum ada.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
