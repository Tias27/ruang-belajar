<x-app-layout title="Tambah Materi">
    <div class="mx-auto max-w-5xl">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
            <p class="text-sm font-semibold text-campus-700">Tambah Materi</p>
            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-campus-900">Tambah Materi Belajar</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Unggah sebagai dokumen terpisah, atau buat folder agar semua file dibaca AI sebagai satu paket materi.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-lg bg-campus-50 px-3 py-2 text-xs font-semibold text-campus-700">
                    <i data-lucide="layers-3" class="h-4 w-4"></i> Maks. 30 file
                </span>
            </div>
        </section>

        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" x-data="{ mode: '{{ old('upload_mode', 'files') }}', files: [] }">
            @csrf
            <input type="hidden" name="upload_mode" x-bind:value="mode">

            <div class="border-b border-slate-200 bg-slate-50 p-3">
                <div class="grid gap-2 sm:grid-cols-2">
                    <button type="button" x-on:click="mode = 'files'" class="flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition" x-bind:class="mode === 'files' ? 'border-campus-200 bg-white text-campus-900 shadow-sm' : 'border-transparent text-slate-600 hover:bg-white'">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-campus-50 text-campus-700"><i data-lucide="files" class="h-5 w-5"></i></span>
                        <span><span class="block text-sm font-semibold">Unggah Per File</span><span class="mt-1 block text-xs">Setiap file jadi materi terpisah.</span></span>
                    </button>
                    <button type="button" x-on:click="mode = 'folder'" class="flex items-center gap-3 rounded-lg border px-4 py-3 text-left transition" x-bind:class="mode === 'folder' ? 'border-campus-200 bg-white text-campus-900 shadow-sm' : 'border-transparent text-slate-600 hover:bg-white'">
                        <span class="grid h-10 w-10 place-items-center rounded-lg bg-accent-50 text-accent-700"><i data-lucide="folder-plus" class="h-5 w-5"></i></span>
                        <span><span class="block text-sm font-semibold">Buat Folder Materi</span><span class="mt-1 block text-xs">Semua file diringkas dan ditanya sekaligus.</span></span>
                    </button>
                </div>
            </div>

            <div class="grid gap-0 lg:grid-cols-[.95fr_1.05fr]">
                <div class="border-b border-slate-200 bg-white p-5 sm:p-6 lg:border-b-0 lg:border-r">
                    <div x-show="mode === 'files'" class="rounded-lg border border-slate-200 bg-slate-50/70 p-4">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-campus-700 shadow-sm">
                                <i data-lucide="text-cursor-input" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <label for="title" class="block text-sm font-semibold text-slate-900">Judul seri opsional</label>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Dipakai kalau kamu mengunggah beberapa file sebagai seri materi.</p>
                            </div>
                        </div>
                        <input id="title" name="title" value="{{ old('title') }}" placeholder="Contoh: IPA - Sistem Pernapasan" class="mt-4 bg-white">
                        <p class="mt-2 text-xs leading-5 text-slate-500">Jika kosong, nama file akan dipakai sebagai judul dokumen.</p>
                    </div>

                    <div x-show="mode === 'folder'" x-cloak class="rounded-lg border border-accent-100 bg-accent-50/60 p-4">
                        <div class="flex items-start gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white text-accent-700 shadow-sm">
                                <i data-lucide="folder-pen" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <label for="folder_name" class="block text-sm font-semibold text-slate-900">Nama folder</label>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Buat nama yang mewakili paket materi, kelas, atau pertemuan.</p>
                            </div>
                        </div>
                        <input id="folder_name" name="folder_name" value="{{ old('folder_name') }}" placeholder="Contoh: Matematika - Persamaan Linear" class="mt-4 bg-white">

                        <div class="mt-5">
                            <label for="folder_description" class="block text-sm font-semibold text-slate-900">Catatan opsional</label>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Boleh isi rentang bab, tujuan belajar, atau konteks materi.</p>
                            <textarea id="folder_description" name="folder_description" rows="5" placeholder="Contoh: Materi bab 1 sampai 3" class="mt-3 min-h-32 resize-y bg-white px-4 py-3 leading-7 placeholder:text-slate-400">{{ old('folder_description') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="badge-info" class="h-4 w-4 text-campus-700"></i>
                            <p class="text-sm font-semibold text-slate-800">Format yang diterima</p>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-slate-600">PDF</span>
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-slate-600">DOCX</span>
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-slate-600">PPTX</span>
                            <span class="rounded-md bg-campus-50 px-2.5 py-1 text-campus-700">20 MB / file</span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <label class="block text-sm font-semibold text-slate-800">File materi</label>
                    <label class="mt-2 flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center transition hover:border-campus-300 hover:bg-campus-50">
                        <span class="grid h-12 w-12 place-items-center rounded-lg bg-white text-campus-700 shadow-sm">
                            <i data-lucide="upload-cloud" class="h-6 w-6"></i>
                        </span>
                        <span class="mt-4 text-sm font-semibold text-slate-800">Klik untuk memilih banyak file</span>
                        <span class="mt-1 text-xs text-slate-500" x-text="mode === 'folder' ? 'File akan masuk ke satu folder materi.' : 'File akan dibuat sebagai dokumen terpisah.'"></span>
                        <input name="files[]" type="file" accept=".pdf,.docx,.pptx" multiple required class="sr-only" x-on:change="files = Array.from($event.target.files)">
                    </label>

                    <div class="mt-4" x-show="files.length" x-cloak>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-800"><span x-text="files.length"></span> file dipilih</p>
                            <p class="text-xs text-slate-500" x-text="mode === 'folder' ? 'Akan digabung dalam folder' : 'Akan dibuat terpisah'"></p>
                        </div>
                        <div class="mt-3 max-h-48 space-y-2 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2">
                            <template x-for="file in files" :key="file.name">
                                <div class="flex items-center gap-3 rounded-md bg-slate-50 px-3 py-2">
                                    <i data-lucide="file-text" class="h-4 w-4 text-campus-700"></i>
                                    <span class="min-w-0 flex-1 truncate text-sm text-slate-700" x-text="file.name"></span>
                                    <span class="text-xs text-slate-500" x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                <a href="{{ route('documents.index') }}" class="inline-flex justify-center rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Batal</a>
                <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i> Simpan Materi
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
