<x-app-layout title="{{ $document->title }}">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-campus-700">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Materi Saya
                </a>
                <h1 class="mt-3 break-words text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $document->title }}</h1>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-medium text-slate-600">{{ $document->original_name }}</span>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-medium text-slate-600">{{ strtoupper($document->extension) }}</span>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 font-medium text-slate-600">{{ number_format($document->size / 1024 / 1024, 2) }} MB</span>
                    <span class="rounded-md bg-campus-50 px-2.5 py-1 font-semibold text-campus-700">{{ $document->status }}</span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    <i data-lucide="download" class="h-4 w-4"></i> Unduh
                </a>
                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Hapus file ini? Semua hasil belajar terkait juga ikut terhapus.')">
                    @csrf @method('DELETE')
                    <button class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50">
                        <i data-lucide="trash-2" class="h-4 w-4"></i> Hapus
                    </button>
                </form>
            </div>
        </div>
    </section>

    @if($document->processing_notes)
        <div class="mt-4 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i>
            <span>{{ $document->processing_notes }}</span>
        </div>
    @endif

    <div class="mt-5 flex items-start gap-3 rounded-lg border border-campus-100 bg-campus-50 px-4 py-3 text-sm text-campus-900">
        <i data-lucide="file-check-2" class="mt-0.5 h-4 w-4 shrink-0"></i>
        <span>AI hanya membaca isi file ini: <strong>{{ $document->title }}</strong>.</span>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1fr_.85fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Isi File yang Dibaca AI</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ filled($document->extracted_text) ? number_format(strlen($document->extracted_text)).' karakter siap dipakai AI.' : 'Belum ada teks yang berhasil dibaca.' }}
                    </p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-lg {{ filled($document->extracted_text) ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-2 text-xs font-semibold">
                    <i data-lucide="{{ filled($document->extracted_text) ? 'check-circle' : 'alert-circle' }}" class="h-4 w-4"></i>
                    {{ filled($document->extracted_text) ? 'Siap AI' : 'Perlu cek file' }}
                </span>
            </div>
            <div class="mt-4 max-h-72 overflow-y-auto rounded-lg bg-slate-50 p-4 text-sm leading-7 text-slate-700">
                @if(filled($document->extracted_text))
                    {{ \Illuminate\Support\Str::limit($document->extracted_text, 3500) }}
                @else
                    Teks dokumen belum tersedia. Kalau ini PDF hasil scan, coba pakai PDF yang punya teks asli atau lakukan OCR dulu.
                @endif
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Catatan Pribadi</h2>
            <p class="mt-1 text-sm text-slate-500">Tulis poin penting versi kamu sendiri.</p>
            <form method="POST" action="{{ route('documents.notes.store', $document) }}" class="mt-4">
                @csrf
                <textarea name="content" rows="9" placeholder="Contoh: bagian REST API masih perlu diulang..." class="resize-y px-4 py-3 leading-7 placeholder:text-slate-400">{{ old('content', $document->notes->first()?->content) }}</textarea>
                <button class="mt-3 inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                    <i data-lucide="save" class="h-4 w-4"></i> Simpan catatan
                </button>
            </form>
        </section>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <form method="POST" action="{{ route('summaries.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">
            @csrf
            <button x-bind:disabled="loading" class="flex h-full w-full items-center gap-3 rounded-lg border border-campus-100 bg-campus-700 p-4 text-left text-white shadow-sm hover:bg-campus-900 disabled:cursor-wait disabled:opacity-75">
                <span x-show="!loading"><i data-lucide="notebook-tabs" class="h-5 w-5 shrink-0"></i></span>
                <span x-show="loading" x-cloak class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                <span><span class="block text-sm font-semibold" x-text="loading ? 'Meringkas...' : 'Ringkas'"></span><span class="mt-1 block text-xs text-campus-100" x-text="loading ? 'AI sedang membaca materi.' : 'Inti, poin penting, kesimpulan.'"></span></span>
            </button>
        </form>
        <form method="POST" action="{{ route('chat.create', $document) }}">
            @csrf
            <button class="flex h-full w-full items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-campus-100 hover:bg-campus-50">
                <i data-lucide="messages-square" class="h-5 w-5 shrink-0 text-campus-700"></i>
                <span><span class="block text-sm font-semibold text-slate-900">Tanya AI</span><span class="mt-1 block text-xs text-slate-500">Tanya isi file ini.</span></span>
            </button>
        </form>
        <form method="POST" action="{{ route('quizzes.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">
            @csrf
            <button x-bind:disabled="loading" class="flex h-full w-full items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-campus-100 hover:bg-campus-50 disabled:cursor-wait disabled:opacity-75">
                <span x-show="!loading"><i data-lucide="list-checks" class="h-5 w-5 shrink-0 text-campus-700"></i></span>
                <span x-show="loading" x-cloak class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                <span><span class="block text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat latihan...' : 'Latihan Soal'"></span><span class="mt-1 block text-xs text-slate-500" x-text="loading ? 'AI sedang menyusun soal.' : 'Pilihan ganda dan pembahasan.'"></span></span>
            </button>
        </form>
        <form method="POST" action="{{ route('flashcards.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">
            @csrf
            <button x-bind:disabled="loading" class="flex h-full w-full items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-campus-100 hover:bg-campus-50 disabled:cursor-wait disabled:opacity-75">
                <span x-show="!loading"><i data-lucide="copy-check" class="h-5 w-5 shrink-0 text-campus-700"></i></span>
                <span x-show="loading" x-cloak class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                <span><span class="block text-sm font-semibold text-slate-900" x-text="loading ? 'Membuat flashcard...' : 'Flashcard'"></span><span class="mt-1 block text-xs text-slate-500" x-text="loading ? 'AI sedang memilih poin penting.' : 'Kartu tanya jawab singkat.'"></span></span>
            </button>
        </form>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-4">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Ringkasan</h2>
                <span class="text-xs font-semibold text-slate-400">{{ $document->summaries_count }}</span>
            </div>
            <div class="mt-4 space-y-2">
                @forelse($document->summaries as $summary)
                    <a href="{{ route('summaries.show', $summary) }}" class="block rounded-lg bg-slate-50 p-3 text-sm font-medium text-campus-700">{{ $summary->created_at->format('d M Y H:i') }}</a>
                @empty
                    <p class="text-sm text-slate-500">Belum ada ringkasan.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Latihan Soal</h2>
                <span class="text-xs font-semibold text-slate-400">{{ $document->quizzes_count }}</span>
            </div>
            <div class="mt-4 space-y-2">
                @forelse($document->quizzes as $quiz)
                    <a href="{{ route('quizzes.show', $quiz) }}" class="block rounded-lg bg-slate-50 p-3 text-sm font-medium text-campus-700">{{ $quiz->title }}</a>
                @empty
                    <p class="text-sm text-slate-500">Belum ada soal.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Flashcard</h2>
            <a href="{{ route('flashcards.index', $document) }}" class="mt-4 flex items-center justify-between rounded-lg bg-slate-50 p-3 text-sm font-medium text-campus-700">
                <span>{{ $document->flashcards_count }} kartu tersedia</span>
                <i data-lucide="arrow-right" class="h-4 w-4"></i>
            </a>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Riwayat Tanya AI</h2>
            <div class="mt-4 space-y-2">
                @forelse($document->chatSessions as $session)
                    <a href="{{ route('chat.show', $session) }}" class="block rounded-lg bg-slate-50 p-3 text-sm font-medium text-campus-700">{{ $session->title }}</a>
                @empty
                    <p class="text-sm text-slate-500">Belum ada percakapan.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
