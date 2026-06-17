<x-app-layout title="Beranda Belajar" subtitle="Upload materi, pilih fitur, lalu belajar">
    <div class="min-w-0 overflow-x-hidden">
    <section class="min-w-0 overflow-hidden rounded-[1.75rem] bg-campus-50">
        <div class="grid min-w-0 gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,.85fr)] lg:items-center">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-white px-3 py-1.5 text-campus-700 shadow-sm">Ruang Belajar</span>
                    <span class="rounded-full bg-accent-50 px-3 py-1.5 text-accent-700">{{ auth()->user()->program_studi ?: 'Pembelajar' }}</span>
                </div>
                <h1 class="mt-5 max-w-2xl text-3xl font-semibold leading-tight tracking-tight text-campus-900 sm:text-4xl">
                    Hai, {{ auth()->user()->username }}. Mulai dari materi yang kamu punya.
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Upload file, pilih materi, lalu gunakan AI untuk memahami, bertanya, latihan, dan mengulang konsep penting.
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('documents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-campus-900">
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Materi
                    </a>
                    <a href="{{ route('student.guide') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-100 px-5 py-3 text-sm font-semibold text-campus-800 shadow-sm transition hover:bg-campus-200">
                        <i data-lucide="map" class="h-4 w-4"></i> Lihat Panduan
                    </a>
                    <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-campus-700 shadow-sm transition hover:bg-campus-100">
                        <i data-lucide="library" class="h-4 w-4"></i> Materi Saya
                    </a>
                </div>
            </div>

            <div class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-4 shadow-sm">
                <div class="grid grid-cols-2 gap-2">
                    @foreach([
                        ['label' => 'Materi', 'value' => $stats['materials'], 'icon' => 'library'],
                        ['label' => 'Ringkasan', 'value' => $stats['summaries'], 'icon' => 'notebook-tabs'],
                        ['label' => 'Flashcard', 'value' => $stats['flashcards'], 'icon' => 'copy-check'],
                        ['label' => 'Chat AI', 'value' => $stats['chats'], 'icon' => 'messages-square'],
                    ] as $stat)
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-500">{{ $stat['label'] }}</span>
                                <i data-lucide="{{ $stat['icon'] }}" class="h-4 w-4 text-campus-700"></i>
                            </div>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $stat['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-[1.5rem] bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Langkah belajar yang disarankan</h2>
                <p class="mt-1 text-sm text-slate-500">Ikuti urutan ini kalau baru pertama kali memakai Ruang Belajar.</p>
            </div>
            <a href="{{ route('student.guide') }}" class="inline-flex w-fit items-center gap-2 rounded-full bg-campus-50 px-3 py-2 text-xs font-semibold text-campus-700 hover:bg-campus-100">
                Panduan lengkap <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
            </a>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-4">
            @foreach([
                ['title' => 'Upload', 'text' => 'Masukkan file atau folder materi.', 'icon' => 'upload-cloud', 'active' => $stats['materials'] === 0],
                ['title' => 'Buka materi', 'text' => 'Pilih file/folder dari Materi Saya.', 'icon' => 'folder-open', 'active' => $stats['materials'] > 0],
                ['title' => 'Pakai AI', 'text' => 'Ringkas, tanya, soal, atau flashcard.', 'icon' => 'sparkles', 'active' => $stats['materials'] > 0],
                ['title' => 'Ulangi', 'text' => 'Cek riwayat dan ulang kartu belajar.', 'icon' => 'repeat-2', 'active' => $stats['flashcards'] > 0 || $stats['chats'] > 0],
            ] as $index => $step)
                <div class="rounded-2xl {{ $step['active'] ? 'bg-campus-50 ring-1 ring-campus-100' : 'bg-slate-50' }} p-4">
                    <div class="flex items-center justify-between">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-white text-campus-700 shadow-sm">
                            <i data-lucide="{{ $step['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <span class="text-xs font-semibold text-slate-400">0{{ $index + 1 }}</span>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $step['title'] }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-6 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-campus-700">Menu belajar</p>
            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Mau dibantu apa hari ini?</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Semua fitur dimulai dari memilih materi. Klik salah satu aksi di samping, lalu pilih file atau folder.
            </p>
            <div class="mt-4 flex min-w-0 items-start gap-3 rounded-[1.1rem] bg-white p-3 text-sm text-slate-600 shadow-sm">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-campus-50 text-campus-700">
                    <i data-lucide="loader-circle" class="h-4 w-4"></i>
                </span>
                <p class="min-w-0 leading-6">Saat AI membuat ringkasan, soal, atau kartu belajar, tombol akan berubah menjadi proses berjalan. Tunggu sampai halaman hasil terbuka.</p>
            </div>
        </div>

        <div class="min-w-0 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
            @foreach([
                ['title' => 'Ringkas materi', 'caption' => 'Buat ringkasan singkat, lengkap, poin penting, dan kesimpulan.', 'icon' => 'notebook-tabs', 'href' => route('documents.index', ['intent' => 'summary']), 'tone' => 'campus'],
                ['title' => 'Tanya AI', 'caption' => 'Ajukan pertanyaan dan AI menjawab berdasarkan isi materi.', 'icon' => 'messages-square', 'href' => route('documents.index', ['intent' => 'chat']), 'tone' => 'accent'],
                ['title' => 'Latihan soal', 'caption' => 'Buat soal pilihan ganda otomatis beserta pembahasan.', 'icon' => 'list-checks', 'href' => route('documents.index', ['intent' => 'quiz']), 'tone' => 'campus'],
                ['title' => 'Flashcard', 'caption' => 'Ulang konsep penting dengan kartu tanya jawab singkat.', 'icon' => 'copy-check', 'href' => route('documents.index', ['intent' => 'flashcard']), 'tone' => 'accent'],
            ] as $mode)
                <a href="{{ $mode['href'] }}" class="group flex min-w-0 items-center gap-4 border-b border-slate-100 p-4 transition last:border-b-0 hover:bg-campus-50">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $mode['tone'] === 'accent' ? 'bg-accent-50 text-accent-700' : 'bg-campus-50 text-campus-700' }}">
                        <i data-lucide="{{ $mode['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-900">{{ $mode['title'] }}</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $mode['caption'] }}</span>
                    </span>
                    <span class="hidden items-center gap-1 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-campus-700 shadow-sm sm:inline-flex">
                        Pilih materi <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-1"></i>
                    </span>
                    <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400 sm:hidden"></i>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-6 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)]">
        <div class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
            <div class="flex min-w-0 items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Materi terakhir</h2>
                    <p class="mt-1 text-sm text-slate-500">Lanjutkan dari file atau folder terbaru.</p>
                </div>
                <a href="{{ route('documents.index') }}" class="inline-flex items-center gap-1 rounded-full bg-campus-50 px-3 py-2 text-xs font-semibold text-campus-700 hover:bg-campus-100">
                    Semua <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>
            <div class="mt-4 min-w-0 divide-y divide-slate-100">
                @forelse($recentMaterials as $material)
                    <a href="{{ $material['url'] }}" class="group flex min-w-0 items-center gap-3 py-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $material['type'] === 'folder' ? 'bg-accent-50 text-accent-700' : 'bg-campus-50 text-campus-700' }}">
                            <i data-lucide="{{ $material['icon'] }}" class="h-5 w-5"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900 group-hover:text-campus-700">{{ $material['title'] }}</span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $material['meta'] }} | {{ $material['created_at']->format('d M Y') }}</span>
                        </span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-campus-700"></i>
                    </a>
                @empty
                    <div class="py-7 text-center">
                        <i data-lucide="file-plus-2" class="mx-auto h-9 w-9 text-slate-400"></i>
                        <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada materi</p>
                        <p class="mt-1 text-sm text-slate-500">Upload materi pertama untuk mulai belajar.</p>
                        <a href="{{ route('documents.create') }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-campus-900">
                            <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Materi
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-900">Lanjutkan ini</h2>
                    <p class="mt-1 text-sm text-slate-500">Saran ringan dari aktivitasmu.</p>
                </div>
                <span class="rounded-full bg-accent-50 px-3 py-1 text-xs font-semibold text-accent-700">{{ $stats['due_flashcards'] }} ulang</span>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($focusItems as $item)
                    <a href="{{ $item['url'] }}" class="group flex min-w-0 items-start gap-3 rounded-2xl {{ $item['active'] ? 'bg-campus-50' : 'bg-slate-50' }} p-3 transition hover:bg-campus-50">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white text-campus-700 shadow-sm">
                            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $item['meta'] }}</span>
                        </span>
                        <i data-lucide="chevron-right" class="mt-1 h-4 w-4 shrink-0 text-slate-400 group-hover:text-campus-700"></i>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mt-6 min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Aktivitas terakhir</h2>
            <p class="mt-1 text-sm text-slate-500">Riwayat singkat dari materi dan AI.</p>
        </div>
        <div class="mt-4 grid min-w-0 gap-2 md:grid-cols-2">
            @forelse($activities as $activity)
                @php
                    $activityLabel = [
                        'upload_document' => 'Mengunggah materi',
                        'create_document_folder' => 'Membuat folder materi',
                        'generate_summary' => 'Membuat ringkasan',
                        'generate_folder_summary' => 'Membuat ringkasan folder',
                        'generate_quiz' => 'Membuat latihan soal',
                        'generate_folder_quiz' => 'Membuat latihan folder',
                        'generate_flashcards' => 'Membuat flashcard',
                        'generate_folder_flashcards' => 'Membuat flashcard folder',
                        'chat_document' => 'Bertanya ke AI',
                        'submit_quiz_attempt' => 'Mengerjakan latihan',
                        'review_flashcard' => 'Mereview flashcard',
                        'save_study_note' => 'Menyimpan catatan',
                        'save_folder_note' => 'Menyimpan catatan folder',
                    ][$activity->action] ?? ucwords(str_replace('_', ' ', $activity->action));
                @endphp
                <div class="flex min-w-0 gap-3 rounded-2xl bg-slate-50 px-3 py-2.5">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-campus-500"></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $activityLabel }}</p>
                        <p class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500 md:col-span-2">Aktivitas belajar akan muncul setelah kamu mulai memakai materi.</p>
            @endforelse
        </div>
    </section>
    </div>
</x-app-layout>
