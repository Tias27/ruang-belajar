<x-app-layout title="Upload Materi">
    <div class="mx-auto max-w-5xl min-w-0 overflow-x-hidden" x-data="{ 
        mode: '{{ old('upload_mode', 'files') }}', 
        files: [],
        uploading: false,
        progress: 0,
        statusText: '',
        errorMessage: '',
        processingInterval: null,
        statusMessages: [
            'Membaca isi dokumen...',
            'Mengekstrak teks kuliah...',
            'Menganalisis materi dengan AI...',
            'Mengoptimalkan bahan belajar...',
            'Hampir selesai, sedang menyimpan...'
        ],
        statusIndex: 0,

        startProcessingSimulation() {
            if (this.processingInterval) return;
            this.statusIndex = 0;
            this.statusText = this.statusMessages[0];
            
            this.processingInterval = setInterval(() => {
                this.statusIndex = (this.statusIndex + 1) % this.statusMessages.length;
                this.statusText = this.statusMessages[this.statusIndex];

                // Slowly creep progress up to 99%
                if (this.progress < 99) {
                    this.progress += 1;
                }
            }, 2000);
        },

        stopProcessingSimulation() {
            if (this.processingInterval) {
                clearInterval(this.processingInterval);
                this.processingInterval = null;
            }
        },

        handleFileSelect(e) {
            let newFiles = [];
            if (this.mode === 'folder') {
                newFiles = [...this.files];
                Array.from(e.target.files).forEach(f => {
                    if (!newFiles.find(existing => existing.name === f.name && existing.size === f.size)) {
                        newFiles.push(f);
                    }
                });
            } else {
                newFiles = Array.from(e.target.files);
            }

            // Enforce max 30 files limit
            if (newFiles.length > 30) {
                alert('Maksimal 30 file yang diperbolehkan dalam sekali upload. Hanya 30 file pertama yang akan dimasukkan.');
                newFiles = newFiles.slice(0, 30);
            }

            this.files = newFiles;

            // Sync back to file input
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f));
            
            // Sync to the input element that triggered the event
            e.target.files = dt.files;
            // Also sync to $refs.fileInput just in case
            if (this.$refs.fileInput) {
                this.$refs.fileInput.files = dt.files;
            }
        },
        removeFile(index) {
            const dt = new DataTransfer();
            this.files.forEach((f, i) => {
                if (i !== index) dt.items.add(f);
            });
            this.files = Array.from(dt.files);
            this.$refs.fileInput.files = dt.files;
        },
        submitForm(e) {
            if (this.files.length === 0) return;
            this.uploading = true;
            this.progress = 0;
            this.statusText = 'Menghubungkan ke server...';
            this.errorMessage = '';

            const form = e.target;
            const formData = new FormData(form);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Progress listener
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    const percent = Math.round((event.loaded / event.total) * 100);
                    // Map actual upload progress to 0 - 90%
                    this.progress = Math.round(percent * 0.9);
                    if (percent < 100) {
                        this.statusText = `Mengunggah file... ${percent}%`;
                    } else {
                        this.statusText = 'Upload selesai. Sedang membaca isi materi...';
                        this.startProcessingSimulation();
                    }
                }
            });

            // Completion listener
            xhr.onload = () => {
                this.stopProcessingSimulation();
                if (xhr.status >= 200 && xhr.status < 300) {
                    this.progress = 100;
                    this.statusText = 'Selesai! Mengalihkan halaman...';
                    window.location.href = xhr.responseURL || '{{ route('documents.index') }}';
                } else {
                    this.uploading = false;
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.errors) {
                            const firstErrKey = Object.keys(response.errors)[0];
                            this.errorMessage = response.errors[firstErrKey][0];
                        } else {
                            this.errorMessage = response.message || 'Gagal mengunggah file. Silakan coba lagi.';
                        }
                    } catch (err) {
                        this.errorMessage = 'Gagal mengunggah file. Silakan periksa ukuran file atau coba lagi.';
                    }
                }
            };

            xhr.onerror = () => {
                this.stopProcessingSimulation();
                this.uploading = false;
                this.errorMessage = 'Koneksi ke server terputus. Harap periksa jaringan internet Anda.';
            };

            xhr.send(formData);
        }
    }">
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

        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" @submit.prevent="submitForm($event)" class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
            @csrf
            <input type="hidden" name="upload_mode" x-bind:value="mode">

            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">1. Pilih jenis upload</h2>
                <p class="mt-1 text-sm text-slate-500">Ini menentukan cara AI membaca file kamu.</p>

                <div class="mt-4 grid gap-3">
                    <button type="button" x-on:click="mode = 'files'; files = []; $refs.fileInput.value = ''" class="flex w-full min-w-0 items-start gap-3 rounded-2xl border p-4 text-left transition" x-bind:class="mode === 'files' ? 'border-campus-200 bg-campus-50 text-campus-900' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-campus-700 shadow-sm">
                            <i data-lucide="files" class="h-5 w-5"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">File satuan</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">Satu file per upload untuk dijadikan materi terpisah.</span>
                        </span>
                    </button>

                    <button type="button" x-on:click="mode = 'folder'; files = []; $refs.fileInput.value = ''" class="flex w-full min-w-0 items-start gap-3 rounded-2xl border p-4 text-left transition" x-bind:class="mode === 'folder' ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'">
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
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-600 shadow-sm">PNG</span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-600 shadow-sm">JPG</span>
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
                            <span class="mt-1 text-xs leading-5 text-slate-500" x-text="mode === 'folder' ? 'Pilih beberapa file sekaligus (tahan Ctrl/Shift). Anda juga bisa klik lagi untuk menambahkan file lain.' : 'Pilih satu file materi.'"></span>
                            <input x-ref="fileInput" name="files[]" type="file" accept=".pdf,.docx,.pptx,.jpg,.jpeg,.png,.gif,.webp" x-bind:multiple="mode === 'folder'" required class="sr-only" x-on:change="handleFileSelect($event)">
                        </label>
                    </div>

                    <div x-show="files.length" x-cloak>
                        <div class="flex min-w-0 items-center justify-between gap-3">
                            <p class="min-w-0 text-sm font-semibold text-slate-800"><span x-text="files.length"></span> file dipilih</p>
                            <p class="shrink-0 text-xs text-slate-500" x-text="mode === 'folder' ? 'Akan digabung' : 'Akan dipisah'"></p>
                        </div>
                        <div class="mt-3 max-h-48 min-w-0 space-y-2 overflow-y-auto overflow-x-hidden rounded-2xl bg-slate-50 p-2">
                            <template x-for="(file, index) in files" :key="file.name + file.size">
                                <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto_auto] items-center gap-3 rounded-xl bg-white px-3 py-2 shadow-sm">
                                    <i data-lucide="file-text" class="h-4 w-4 shrink-0 text-campus-700"></i>
                                    <span class="block min-w-0 truncate text-left text-sm text-slate-700" x-bind:title="file.name" x-text="file.name"></span>
                                    <span class="shrink-0 text-xs text-slate-500" x-text="(file.size / 1024 / 1024).toFixed(2) + ' MB'"></span>
                                    <button type="button" @click="removeFile(index)" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-600 transition" title="Hapus file ini">
                                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Error Alert -->
                <div x-show="errorMessage" x-cloak class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-700 text-sm flex gap-3 text-left">
                    <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold">Gagal mengunggah:</span>
                        <p class="mt-1" x-text="errorMessage"></p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('documents.index') }}" class="inline-flex justify-center rounded-full bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                        <i data-lucide="upload-cloud" class="h-4 w-4"></i> Upload Sekarang
                    </button>
                </div>
            </section>
        </form>

        <!-- Progress Overlay Modal -->
        <div x-show="uploading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="w-full max-w-md transform overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-2xl border border-slate-100 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-campus-50 text-campus-700 shadow-inner mb-4">
                    <i data-lucide="upload-cloud" class="h-7 w-7 animate-bounce"></i>
                </div>

                <h3 class="text-lg font-bold text-slate-800 tracking-tight" x-text="statusText">Mengunggah file...</h3>
                
                <!-- Progress Bar -->
                <div class="mt-4 w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner border border-slate-200/50">
                    <div class="bg-gradient-to-r from-campus-500 to-campus-700 h-full rounded-full transition-all duration-300 ease-out" :class="progress >= 90 ? 'animate-pulse' : ''" :style="`width: ${progress}%`"></div>
                </div>

                <!-- Progress Percentage -->
                <div class="mt-2 text-sm font-bold text-campus-700" x-text="`${progress}%`">0%</div>

                <p class="text-xs text-slate-500 mt-4 leading-relaxed">
                    Proses upload tergantung cepat atau lambatnya jaringan internet Anda.<br>
                    Harap tunggu dan jangan menutup halaman ini.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
