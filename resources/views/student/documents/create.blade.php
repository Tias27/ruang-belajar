<x-app-layout title="Upload Materi">
    <div class="mx-auto max-w-5xl min-w-0 overflow-x-hidden" x-data="{ mode: '{{ old('upload_mode', 'files') }}', files: [] }">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-campus-700 shadow-sm">Upload Materi</span>
                    <h1 class="mt-4 text-3xl font-semibold tracking-tight text-campus-900">Tambah bahan belajar baru</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Pilih file satuan untuk materi terpisah, atau folder gabungan kalau beberapa file ingin dipelajari sebagai satu paket.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-semibold text-campus-700 shadow-sm">
                    <i data-lucide="layers-3" class="h-4 w-4"></i> Maks. 30 file
                </span>
            </div>
        </section>

        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
            @csrf
            <input type="hidden" name="upload_mode" x-bind:value="mode">

            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">1. Pilih jenis upload</h2>
                <p class="mt-1 text-sm text-slate-500">Ini menentukan cara AI membaca file kamu.</p>

                <div class="mt-4 grid gap-3">
                    <button type="button" x-on:click="mode = 'files'" class="flex w-full min-w-0 items-start gap-3 rounded-2xl border p-4 text-left transition" x-bind:class="mode === 'files' ? 'border-campus-200 bg-campus-50 text-campus-900' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-campus-700 shadow-sm">
                            <i data-lucide="files" class="h-5 w-5"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">File satuan</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Setiap file jadi materi sendiri. Cocok untuk pertemuan terpisah.</span>
                        </span>
                    </button>

                    <button type="button" x-on:click="mode = 'folder'" class="flex w-full min-w-0 items-start gap-3 rounded-2xl border p-4 text-left transition" x-bind:class="mode === 'folder' ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-accent-700 shadow-sm">
                            <i data-lucide="folder-plus" class="h-5 w-5"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">Folder gabungan</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Banyak file dibaca sebagai satu paket untuk ringkasan, soal, flashcard, dan chat.</span>
                        </span>
                    </button>
                </div>

                <div class="mt-5 rounded-2xl bg-slate-50 p-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="badge-info" class="h-4 w-4 text-campus-700"></i>
                        <p class="text-sm font-semibold text-slate-800">Format yang diterima</p>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-600 shadow-sm">PDF</span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-600 shadow-sm">DOCX</span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-600 shadow-sm">PPTX</span>
                        <span class="rounded-full bg-campus-50 px-3 py-1.5 text-campus-700">20 MB / file</span>
                    </div>
                </div>
            </section>

            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">2. Lengkapi materi</h2>
                <p class="mt-1 text-sm text-slate-500" x-text="mode === 'folder' ? 'Beri nama folder agar mudah ditemukan lagi.' : 'Judul boleh kosong, nanti memakai nama file.'"></p>

                <div class="mt-5 space-y-4">
                    <div x-show="mode === 'files'">
                        <label for="title" class="block text-sm font-semibold text-slate-800">Judul materi opsional</label>
                        <input id="title" name="title" value="{{ old('title') }}" placeholder="Contoh: IPA - Sistem Pernapasan" class="mt-2">
                    </div>

                    <div x-show="mode === 'folder'" x-cloak class="space-y-4">
                        <label class="block text-sm font-semibold text-slate-800">Nama folder gabungan
                            <input id="folder_name" name="folder_name" value="{{ old('folder_name') }}" placeholder="Contoh: Matematika - Persamaan Linear" class="mt-2">
                        </label>
                        <label class="block text-sm font-semibold text-slate-800">Catatan opsional
                            <textarea id="folder_description" name="folder_description" rows="4" placeholder="Contoh: Materi bab 1 sampai 3" class="mt-2 resize-y leading-7 placeholder:text-slate-400">{{ old('folder_description') }}</textarea>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800">File materi</label>
                        <label class="mt-2 flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-[1.25rem] border border-dashed border-campus-200 bg-campus-50/60 px-5 py-8 text-center transition hover:bg-campus-50">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-campus-700 shadow-sm">
                                <i data-lucide="upload-cloud" class="h-6 w-6"></i>
                            </span>
                            <span class="mt-4 text-sm font-semibold text-slate-900">Klik untuk pilih file</span>
                            <span class="mt-1 text-xs leading-5 text-slate-500" x-text="mode === 'folder' ? 'Semua file akan masuk ke satu folder gabungan.' : 'Setiap file akan dibuat sebagai materi terpisah.'"></span>
                            <input name="files[]" type="file" accept=".pdf,.docx,.pptx" multiple required class="sr-only" x-on:change="files = Array.from($event.target.files)">
                        </label>
                    </div>

                    <div x-show="files.length" x-cloak>
                        <div class="flex min-w-0 items-center justify-between gap-3">
                            <p class="min-w-0 text-sm font-semibold text-slate-800"><span x-text="files.length"></span> file dipilih</p>
                            <p class="shrink-0 text-xs text-slate-500" x-text="mode === 'folder' ? 'Akan digabung' : 'Akan dipisah'"></p>
                        </div>
                        <div class="mt-3 max-h-48 min-w-0 space-y-2 overflow-y-auto overflow-x-hidden rounded-2xl bg-slate-50 p-2">
                            <template x-for="file in files" :key="file.name">
                                <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-xl bg-white px-3 py-2 shadow-sm">
                                    <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-campus-700"></i>
                                    <span class="block min-w-0 truncate text-left text-sm text-slate-700" x-bind:title="file.name" x-text="file.name"></span>
                                    <span class="shrink-0 text-xs text-slate-500" x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('documents.index') }}" class="inline-flex justify-center rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</a>
                    <button class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Sekarang
                    </button>
                </div>
            </section>
        </form>
    </div>
</x-app-layout>
