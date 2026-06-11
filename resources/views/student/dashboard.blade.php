<x-app-layout title="Beranda Belajar" subtitle="Upload materi, pilih fitur, lalu belajar">
    <section class="grid gap-5 lg:grid-cols-[1.05fr_.95fr]">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-panel sm:p-7">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <span class="rounded-md bg-campus-50 px-2.5 py-1 text-campus-700">Ruang Belajar</span>
                <span class="rounded-md bg-accent-50 px-2.5 py-1 text-accent-700">{{ auth()->user()->program_studi ?: 'Pembelajar' }}</span>
            </div>
            <h1 class="mt-5 max-w-2xl text-3xl font-semibold tracking-tight text-campus-900 sm:text-4xl">
                Hai, {{ auth()->user()->username }}. Belajar dari file jadi lebih gampang.
            </h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">
                Upload PDF, Word, atau PPT. Setelah itu pilih mau diringkas, ditanyai, dibuat soal, atau dibuat flashcard.
            </p>
            <div class="mt-5 grid gap-2 text-sm sm:grid-cols-3">
                @foreach([
                    ['no' => '1', 'text' => 'Upload materi'],
                    ['no' => '2', 'text' => 'Pilih cara belajar'],
                    ['no' => '3', 'text' => 'Baca hasilnya'],
                ] as $step)
                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-slate-700">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-md bg-campus-700 text-xs font-semibold text-white">{{ $step['no'] }}</span>
                        <span class="font-medium">{{ $step['text'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('documents.create') }}" class="group rounded-lg border border-campus-100 bg-campus-700 p-4 text-white shadow-sm transition hover:bg-campus-900">
                    <span class="flex items-center justify-between">
                        <span class="text-sm font-semibold">Upload materi</span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                    </span>
                    <span class="mt-2 block text-xs leading-5 text-campus-100">PDF, Word, atau PPT. Bisa banyak file sekaligus.</span>
                </a>
                <a href="{{ route('documents.index') }}" class="group rounded-lg border border-slate-200 bg-white p-4 text-slate-800 shadow-sm transition hover:border-campus-100 hover:bg-campus-50">
                    <span class="flex items-center justify-between">
                        <span class="text-sm font-semibold">Buka materi saya</span>
                        <i data-lucide="library" class="h-4 w-4 text-campus-700"></i>
                    </span>
                    <span class="mt-2 block text-xs leading-5 text-slate-500">Masuk ke materi yang sudah pernah kamu upload.</span>
                </a>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Terakhir Dibuka</h2>
                <a href="{{ route('documents.index') }}" class="text-sm font-semibold text-campus-700">Semua materi</a>
            </div>
            <div class="mt-4 space-y-2">
                @forelse($recentMaterials->take(3) as $material)
                    <a href="{{ $material['url'] }}" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3 transition hover:border-campus-100 hover:bg-campus-50">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $material['type'] === 'folder' ? 'bg-accent-50 text-accent-700' : 'bg-campus-50 text-campus-700' }}">
                            <i data-lucide="{{ $material['icon'] }}" class="h-5 w-5"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $material['title'] }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $material['meta'] }}</span>
                        </span>
                        <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400"></i>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center">
                        <i data-lucide="book-open-check" class="mx-auto h-8 w-8 text-slate-400"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada materi</p>
                        <p class="mt-1 text-sm text-slate-500">Unggah materi pertama untuk mulai.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['title' => 'Ringkas', 'caption' => 'Ambil inti materi', 'icon' => 'notebook-tabs', 'href' => route('documents.index')],
            ['title' => 'Tanya AI', 'caption' => 'Tanya isi materi', 'icon' => 'messages-square', 'href' => route('documents.index')],
            ['title' => 'Latihan', 'caption' => 'Buat soal pilihan ganda', 'icon' => 'list-checks', 'href' => route('documents.index')],
            ['title' => 'Flashcard', 'caption' => 'Ulang poin penting', 'icon' => 'copy-check', 'href' => route('documents.index')],
        ] as $mode)
            <a href="{{ $mode['href'] }}" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-campus-100 hover:bg-campus-50">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-campus-700">
                    <i data-lucide="{{ $mode['icon'] }}" class="h-4 w-4"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-slate-900">{{ $mode['title'] }}</span>
                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $mode['caption'] }}</span>
                </span>
            </a>
        @endforeach
    </section>

    <section class="mt-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Lanjutkan Ini</h2>
                <p class="mt-1 text-sm text-slate-500">Rekomendasi sederhana dari aktivitas belajarmu.</p>
            </div>
            <span class="rounded-md bg-accent-50 px-2.5 py-1 text-xs font-semibold text-accent-700">{{ $stats['due_flashcards'] }} kartu perlu diulang</span>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach($focusItems as $item)
                <a href="{{ $item['url'] }}" class="flex items-start gap-3 rounded-lg border {{ $item['active'] ? 'border-campus-100 bg-campus-50' : 'border-slate-200 bg-slate-50' }} p-4 transition hover:border-campus-200 hover:bg-campus-50">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-campus-700 shadow-sm">
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $item['meta'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    <div class="mt-5 grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Materi Saya</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $stats['materials'] }} materi, termasuk {{ $stats['folders'] }} folder gabungan.</p>
                </div>
                <span class="rounded-md bg-campus-50 px-2.5 py-1 text-xs font-semibold text-campus-700">{{ $stats['documents'] }} file mandiri</span>
            </div>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse($recentMaterials as $material)
                    <a href="{{ $material['url'] }}" class="flex items-center gap-3 py-3 transition hover:bg-slate-50">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ $material['type'] === 'folder' ? 'bg-accent-50 text-accent-700' : 'bg-campus-50 text-campus-700' }}">
                            <i data-lucide="{{ $material['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $material['title'] }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500">{{ $material['meta'] }} | {{ $material['created_at']->format('d M Y') }}</span>
                        </span>
                    </a>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Materi terbaru akan muncul di sini.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Aktivitas Terakhir</h2>
            <p class="mt-1 text-sm text-slate-500">Yang baru kamu lakukan di materi dan AI.</p>
            <div class="mt-4 space-y-2">
                @forelse($activities as $activity)
                    @php
                        $activityLabel = [
                            'upload_document' => 'Mengunggah materi',
                            'create_document_folder' => 'Membuat folder materi',
                            'generate_summary' => 'Membuat ringkasan',
                            'generate_folder_summary' => 'Membuat ringkasan folder',
                            'generate_quiz' => 'Membuat soal latihan',
                            'generate_folder_quiz' => 'Membuat soal folder',
                            'generate_flashcards' => 'Membuat flashcard',
                            'generate_folder_flashcards' => 'Membuat kartu folder',
                            'chat_document' => 'Bertanya ke AI',
                            'submit_quiz_attempt' => 'Mengerjakan kuis',
                            'review_flashcard' => 'Mereview flashcard',
                            'save_study_note' => 'Menyimpan catatan',
                            'save_folder_note' => 'Menyimpan catatan folder',
                        ][$activity->action] ?? ucwords(str_replace('_', ' ', $activity->action));
                    @endphp
                    <div class="flex gap-3 rounded-lg bg-slate-50 px-3 py-2.5">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-campus-500"></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800">{{ $activityLabel }}</p>
                            <p class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Aktivitas belajar akan muncul setelah kamu mulai memakai materi.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
