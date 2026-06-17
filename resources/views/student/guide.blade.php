<x-app-layout title="Panduan Belajar" subtitle="Ikuti langkah singkat untuk mulai memakai Ruang Belajar">
    <div class="min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-campus-700 shadow-sm">Panduan cepat</span>
                <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-campus-900 sm:text-4xl">Mulai belajar dari materi tanpa bingung.</h1>
                <p class="mt-3 text-sm leading-6 text-slate-600">Ruang Belajar bekerja dari materi yang kamu upload. Jadi urutannya selalu: upload materi, buka materi, pilih fitur AI, lalu simpan atau ulangi hasilnya.</p>
                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('documents.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload materi pertama
                    </a>
                    <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-semibold text-campus-700 shadow-sm hover:bg-campus-100">
                        <i data-lucide="library" class="h-4 w-4"></i> Buka Materi Saya
                    </a>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-4">
            @foreach([
                ['no' => '1', 'title' => 'Upload materi', 'text' => 'Masukkan PDF, DOCX, atau PPTX. Bisa satu file, banyak file, atau folder gabungan.', 'icon' => 'file-up-2'],
                ['no' => '2', 'title' => 'Buka file/folder', 'text' => 'Semua fitur AI ada di halaman detail materi, bukan dari dashboard utama.', 'icon' => 'folder-open'],
                ['no' => '3', 'title' => 'Pilih fitur AI', 'text' => 'Gunakan Ringkas, Tanya materi, Latihan soal, atau Kartu belajar sesuai kebutuhan.', 'icon' => 'sparkles'],
                ['no' => '4', 'title' => 'Lanjutkan belajar', 'text' => 'Cek riwayat AI, ulang flashcard, atau kerjakan soal lagi kapan saja.', 'icon' => 'repeat-2'],
            ] as $step)
                <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                            <i data-lucide="{{ $step['icon'] }}" class="h-5 w-5"></i>
                        </span>
                        <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-500">Langkah {{ $step['no'] }}</span>
                    </div>
                    <h2 class="mt-4 font-semibold text-slate-900">{{ $step['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="mt-6 grid gap-5 lg:grid-cols-2">
            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Fitur ini buat apa?</h2>
                <div class="mt-4 space-y-3">
                    @foreach([
                        ['title' => 'Ringkas materi', 'text' => 'Dipakai kalau kamu ingin memahami inti materi dengan cepat sebelum membaca detail.'],
                        ['title' => 'Tanya materi', 'text' => 'Dipakai kalau ada bagian yang belum paham. AI menjawab berdasarkan isi file/folder yang dipilih.'],
                        ['title' => 'Latihan soal', 'text' => 'Dipakai untuk menguji pemahaman. Bisa pilihan ganda atau esai.'],
                        ['title' => 'Kartu belajar', 'text' => 'Dipakai untuk mengulang konsep penting secara singkat sebelum kuis/ujian.'],
                    ] as $item)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="font-semibold text-slate-900">{{ $item['title'] }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ $item['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Urutan yang disarankan</h2>
                <ol class="mt-4 space-y-3">
                    @foreach([
                        'Upload semua materi yang mau dipelajari.',
                        'Buka folder atau file yang sesuai.',
                        'Klik Ringkas materi untuk memahami gambaran besar.',
                        'Gunakan Tanya materi untuk bagian yang masih bingung.',
                        'Buat latihan soal untuk cek pemahaman.',
                        'Buat kartu belajar untuk mengulang sebelum ujian.',
                    ] as $index => $text)
                        <li class="flex gap-3 rounded-2xl bg-campus-50 p-3 text-sm leading-6 text-campus-900">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-xs font-semibold text-campus-700 shadow-sm">{{ $index + 1 }}</span>
                            <span>{{ $text }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    </div>
</x-app-layout>
