<x-app-layout title="Belajar Bareng" subtitle="Kolaborasi belajar real-time bersama teman dan AI">
    <div class="space-y-8 min-w-0" x-data="{ 
        activeTab: 'choose-existing',
        uploading: false,
        files: [],
        folderSelection: '',
        handleFileSelect(e) {
            const newFiles = Array.from(e.target.files);
            if (newFiles.length > 0) {
                this.files = newFiles;
            }
        },
        getFilesLabel() {
            if (this.files.length === 0) return '';
            if (this.files.length === 1) return this.files[0].name;
            return this.files.length + ' file terpilih';
        }
    }">
        
        <!-- Header Banner -->
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-campus-600 via-campus-700 to-accent-700 p-6 sm:p-8 text-white shadow-lg">
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none transform rotate-12">
                <i data-lucide="users" class="w-64 h-64"></i>
            </div>
            <div class="relative z-10 max-w-3xl">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Multiplayer Social Learning
                </span>
                <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl leading-tight">
                    Ruang Belajar Bareng
                </h1>
                <p class="mt-2 text-sm sm:text-base text-campus-100/90 leading-relaxed">
                    Diskusikan materi kuliah secara real-time bersama teman-temanmu. Tanyakan bagian yang sulit, 
                    dan biarkan asisten AI RuangBelajar membantu memberikan penjelasan terbaik secara langsung di dalam room.
                </p>
            </div>
        </div>

        <!-- Upper Grid: Join Room & Active Rooms -->
        <div class="grid gap-6 grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] w-full min-w-0">
            
            <!-- Left: Join Room Card -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between min-w-0 w-full">
                <div>
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="key-round" class="h-5 w-5 text-accent-500"></i>
                        Gabung Sesi Teman
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Masukkan 4 digit PIN yang dibagikan oleh pembuat room untuk masuk ke ruang diskusi.
                    </p>
                </div>
                
                <form method="POST" action="{{ route('study-rooms.join') }}" class="mt-6">
                    @csrf
                    <div class="space-y-4">
                        <div class="relative">
                            <input type="text" name="pin" id="pin_code" maxlength="4" placeholder="Contoh: 1234" required
                                   class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-lg font-black tracking-widest text-center focus:bg-white focus:border-campus-500 focus:ring-2 focus:ring-campus-100 outline-none transition-all">
                        </div>
                        <button type="submit" class="w-full bg-campus-700 hover:bg-campus-850 text-white rounded-2xl py-3.5 text-sm font-bold shadow-md transition flex items-center justify-center gap-2">
                            <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                            Gabung Sekarang
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Active Rooms List -->
            <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col min-w-0 w-full">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="door-open" class="h-5 w-5 text-campus-650"></i>
                    Sesi Belajar Aktif Anda
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar room diskusi aktif yang kamu buat atau yang telah kamu ikuti.
                </p>

                <div class="mt-6 flex-1 overflow-y-auto max-h-[190px] pr-1 space-y-3 min-w-0 w-full">
                    @php
                        $hasActiveRooms = $myActiveRooms->isNotEmpty() || $joinedActiveRooms->isNotEmpty();
                    @endphp

                    @if(!$hasActiveRooms)
                        <div class="flex flex-col items-center justify-center py-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200/80">
                            <i data-lucide="message-square-dashed" class="h-8 w-8 text-slate-400"></i>
                            <span class="block text-xs font-semibold text-slate-700 mt-2">Belum ada room aktif</span>
                            <span class="block text-[11px] text-slate-400 mt-0.5">Mulai buat room baru di bawah ini.</span>
                        </div>
                    @else
                        <!-- Hosted Rooms -->
                        @foreach($myActiveRooms as $room)
                            <div class="flex items-center justify-between p-3 rounded-2xl border border-campus-100 bg-campus-50/20 hover:bg-campus-50/40 transition min-w-0 w-full">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="shrink-0 p-2 rounded-xl bg-campus-100 text-campus-700">
                                        <i data-lucide="{{ ($room->target && $room->target instanceof \App\Models\DocumentFolder) ? 'folder' : 'file-text' }}" class="h-4.5 w-4.5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center rounded bg-campus-100 px-1 py-0.5 text-[9px] font-bold text-campus-700 uppercase">Host</span>
                                            <span class="text-xs font-bold text-slate-800 truncate">{{ $room->target ? ($room->target->name ?? $room->target->title) : 'Materi Terhapus' }}</span>
                                        </div>
                                        <span class="block text-[10px] text-slate-500 mt-0.5">PIN: {{ $room->pin }} • {{ $room->users->count() + 1 }} Anggota</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('study-rooms.show', $room) }}" class="inline-flex h-8 items-center justify-center rounded-xl bg-campus-700 px-3 text-xs font-bold text-white hover:bg-campus-850 transition shadow-sm">
                                        Masuk
                                    </a>
                                </div>
                            </div>
                        @endforeach

                        <!-- Joined Rooms -->
                        @foreach($joinedActiveRooms as $room)
                            <div class="flex items-center justify-between p-3 rounded-2xl border border-slate-100 bg-white hover:bg-slate-50 transition min-w-0 w-full">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="shrink-0 p-2 rounded-xl bg-slate-100 text-slate-650">
                                        <i data-lucide="{{ ($room->target && $room->target instanceof \App\Models\DocumentFolder) ? 'folder' : 'file-text' }}" class="h-4.5 w-4.5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center rounded bg-slate-100 px-1 py-0.5 text-[9px] font-semibold text-slate-600 uppercase">Teman</span>
                                            <span class="text-xs font-bold text-slate-700 truncate">{{ $room->target ? ($room->target->name ?? $room->target->title) : 'Materi Terhapus' }}</span>
                                        </div>
                                        <span class="block text-[10px] text-slate-500 mt-0.5">PIN: {{ $room->pin }} • Host: {{ $room->host->name }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('study-rooms.show', $room) }}" class="inline-flex h-8 items-center justify-center rounded-xl bg-slate-100 px-3 text-xs font-bold text-slate-700 hover:bg-slate-200 transition">
                                    Masuk
                                </a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>

        <!-- Lower Section: Create a Study Room -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm min-w-0 w-full">
            <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="plus-circle" class="h-5 w-5 text-emerald-500"></i>
                Buat Room Belajar Baru
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Pilih salah satu materi yang sudah diunggah sebelumnya atau langsung unggah file baru untuk segera membuat ruang diskusi.
            </p>

            <!-- Nav Tabs -->
            <div class="mt-6 flex border-b border-slate-100">
                <button @click="activeTab = 'choose-existing'" 
                        :class="activeTab === 'choose-existing' ? 'border-campus-600 text-campus-700 font-bold' : 'border-transparent text-slate-400 hover:text-slate-600 font-medium'"
                        class="border-b-2 px-4 py-2.5 text-sm transition-all focus:outline-none flex items-center gap-1.5">
                    <i data-lucide="library" class="h-4 w-4"></i>
                    Pilih Materi Saya
                </button>
                <button @click="activeTab = 'upload-new'" 
                        :class="activeTab === 'upload-new' ? 'border-campus-600 text-campus-700 font-bold' : 'border-transparent text-slate-400 hover:text-slate-600 font-medium'"
                        class="border-b-2 px-4 py-2.5 text-sm transition-all focus:outline-none flex items-center gap-1.5">
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                    Upload Baru
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="mt-6 min-w-0 w-full">
                
                <!-- Tab 1: Choose Existing Materials -->
                <div x-show="activeTab === 'choose-existing'" class="space-y-6 min-w-0 w-full">
                    @if($documents->isEmpty() && $folders->isEmpty())
                        <div class="flex flex-col items-center justify-center py-12 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                            <i data-lucide="library" class="h-10 w-10 text-slate-300"></i>
                            <h4 class="mt-4 text-sm font-bold text-slate-700">Materi belum tersedia</h4>
                            <p class="mt-1.5 text-xs text-slate-500 max-w-sm">
                                Kamu belum memiliki folder atau dokumen yang diunggah. Silakan klik tab "Upload Baru" untuk mengunggah materi pertamamu.
                            </p>
                        </div>
                    @else
                        <!-- Folders List -->
                        @if($folders->isNotEmpty())
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <i data-lucide="folder" class="h-3.5 w-3.5"></i>
                                    Folder Materi Saya
                                </h3>
                                <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 w-full min-w-0">
                                    @foreach($folders as $folder)
                                        <div class="rounded-2xl border border-slate-200/80 p-4 hover:border-campus-200 hover:shadow-sm transition flex flex-col justify-between min-w-0 w-full">
                                            <div class="flex items-start gap-3">
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-50 text-accent-700">
                                                    <i data-lucide="folder" class="h-5 w-5"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <span class="block text-sm font-bold text-slate-800 truncate leading-snug">{{ $folder->name }}</span>
                                                    <span class="block text-[11px] text-slate-500 mt-1">{{ $folder->documents()->count() }} Dokumen</span>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('study-rooms.store') }}" class="mt-4">
                                                @csrf
                                                <input type="hidden" name="source_type" value="folder">
                                                <input type="hidden" name="folder_id" value="{{ $folder->id }}">
                                                <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-campus-50 text-xs font-bold text-campus-700 border border-campus-100 hover:bg-campus-100 transition">
                                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                                    Buat Room
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Documents List -->
                        @if($documents->isNotEmpty())
                            <div class="pt-4 border-t border-slate-100">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                    Dokumen Mandiri Saya
                                </h3>
                                <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 w-full min-w-0">
                                    @foreach($documents as $doc)
                                        <div class="rounded-2xl border border-slate-200/80 p-4 hover:border-campus-200 hover:shadow-sm transition flex flex-col justify-between min-w-0 w-full">
                                            <div class="flex items-start gap-3">
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-campus-50 text-campus-700">
                                                    <i data-lucide="file-text" class="h-5 w-5"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <span class="block text-sm font-bold text-slate-800 truncate leading-snug" title="{{ $doc->title }}">{{ $doc->title }}</span>
                                                    <span class="block text-[11px] text-slate-500 mt-1">{{ strtoupper($doc->extension) }} • {{ number_format($doc->size / 1024 / 1024, 2) }} MB</span>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('study-rooms.store') }}" class="mt-4">
                                                @csrf
                                                <input type="hidden" name="source_type" value="document">
                                                <input type="hidden" name="document_id" value="{{ $doc->id }}">
                                                <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-campus-50 text-xs font-bold text-campus-700 border border-campus-100 hover:bg-campus-100 transition">
                                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                                    Buat Room
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <!-- Tab 2: Upload New Material -->
                <div x-show="activeTab === 'upload-new'" class="max-w-2xl">
                    <form method="POST" action="{{ route('study-rooms.store') }}" enctype="multipart/form-data" @submit.prevent="submitForm($event)">
                        @csrf
                        <input type="hidden" name="source_type" value="upload">
                        
                        <div class="space-y-6">
                            <!-- Premium Drag Drop Area -->
                            <div class="relative rounded-2xl border-2 border-dashed border-slate-350 hover:border-campus-500 bg-slate-50/50 hover:bg-campus-50/10 p-8 text-center transition group">
                                <input type="file" name="files[]" id="file_upload" required multiple @change="handleFileSelect"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="p-3 rounded-2xl bg-white shadow-sm text-slate-500 group-hover:text-campus-600 transition duration-200">
                                        <i data-lucide="upload-cloud" class="h-7 w-7"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">
                                            Seret file atau klik untuk memilih dokumen
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Mendukung PDF, DOCX, PPTX, atau Gambar (Bisa pilih banyak sekaligus, maks. 20 MB/file)
                                        </p>
                                    </div>
                                </div>

                                <!-- Selected File Label -->
                                <template x-if="files.length > 0">
                                    <div class="mt-4 flex flex-col gap-2 w-fit mx-auto">
                                        <div class="flex items-center justify-center gap-2 text-xs font-bold text-campus-700 bg-white border border-campus-200 rounded-xl px-3.5 py-2 shadow-sm">
                                            <i data-lucide="file-check" class="h-4 w-4 text-emerald-500"></i>
                                            <span x-text="getFilesLabel()"></span>
                                        </div>
                                        
                                        <!-- Mini list of files -->
                                        <div class="max-h-24 overflow-y-auto space-y-1 text-[10px] text-slate-500 text-left bg-white/95 p-2.5 rounded-xl border border-slate-200/60 shadow-inner">
                                            <template x-for="file in files" :key="file.name">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="h-1 w-1 bg-campus-500 rounded-full"></span>
                                                    <span class="truncate max-w-[200px]" x-text="file.name"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Beautiful Folder Selection Dropdown -->
                            <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50/50 p-5" x-data="{ dropdownOpen: false, selectedFolderText: '-- Tanpa Folder (Dokumen Mandiri) --' }">
                                <div class="flex items-center gap-2 text-slate-700">
                                    <i data-lucide="folder" class="h-4.5 w-4.5 text-slate-500"></i>
                                    <span class="text-sm font-bold">Simpan ke Folder (Opsional)</span>
                                </div>
                                
                                <!-- Hidden select input to submit value -->
                                <input type="hidden" name="folder_id" :value="folderSelection">

                                <!-- Custom Select Trigger -->
                                <div class="relative">
                                    <button type="button" @click="dropdownOpen = !dropdownOpen" @click.outside="dropdownOpen = false"
                                            class="flex w-full items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-campus-100 focus:border-campus-500 transition shadow-sm">
                                        <span class="truncate" x-text="selectedFolderText"></span>
                                        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition-transform" :class="dropdownOpen ? 'rotate-180' : ''"></i>
                                    </button>

                                    <!-- Dropdown Options List -->
                                    <div x-show="dropdownOpen" x-cloak
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute z-20 mt-1.5 w-full max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg outline-none">
                                        
                                        <!-- Option 1: None -->
                                        <button type="button" @click="folderSelection = ''; selectedFolderText = '-- Tanpa Folder (Dokumen Mandiri) --'; dropdownOpen = false"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-left transition hover:bg-slate-50 text-slate-700 font-medium"
                                                :class="folderSelection === '' ? 'bg-campus-50/60 text-campus-700 font-bold' : ''">
                                            <i data-lucide="file" class="h-4 w-4 text-slate-400"></i>
                                            <span>Tanpa Folder (Dokumen Mandiri)</span>
                                        </button>
                                        
                                        <!-- Option 2: New Folder -->
                                        <button type="button" @click="folderSelection = 'new'; selectedFolderText = '+ Buat Folder Baru...'; dropdownOpen = false"
                                                class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-left transition hover:bg-emerald-50 text-emerald-700 font-bold border-t border-slate-100"
                                                :class="folderSelection === 'new' ? 'bg-emerald-50 text-emerald-700' : ''">
                                            <i data-lucide="folder-plus" class="h-4 w-4 text-emerald-500"></i>
                                            <span>Buat Folder Baru...</span>
                                        </button>

                                        <!-- Divider -->
                                        <div class="h-px bg-slate-100 my-1"></div>

                                        <!-- Existing Folders -->
                                        @foreach($folders as $folder)
                                            <button type="button" @click="folderSelection = '{{ $folder->id }}'; selectedFolderText = '{{ addslashes($folder->name) }}'; dropdownOpen = false"
                                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-left transition hover:bg-slate-50 text-slate-700"
                                                    :class="folderSelection === '{{ $folder->id }}' ? 'bg-campus-50/60 text-campus-700 font-bold' : ''">
                                                <i data-lucide="folder" class="h-4 w-4 text-slate-400"></i>
                                                <span class="truncate">{{ $folder->name }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- New Folder Name Input -->
                                <div x-show="folderSelection === 'new'" x-cloak class="mt-3.5 space-y-2">
                                    <label for="new_folder_name" class="block text-xs font-bold text-slate-500">Nama Folder Baru</label>
                                    <input type="text" name="new_folder_name" id="new_folder_name" :required="folderSelection === 'new'" placeholder="Contoh: Matematika - Bab 3" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-campus-500 focus:ring-1 focus:ring-campus-500 outline-none transition shadow-sm">
                                </div>
                            </div>

                            <!-- Error Alert -->
                            <div x-show="errorMessage" x-cloak class="p-4 mb-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-700 text-sm flex gap-3 text-left">
                                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 mt-0.5"></i>
                                <div>
                                    <span class="font-bold">Gagal mengunggah:</span>
                                    <p class="mt-1" x-text="errorMessage"></p>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" :disabled="uploading"
                                        class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-campus-700 px-6 text-sm font-bold text-white shadow-md hover:bg-campus-850 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                                    <span>Mulai Belajar Bareng</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </div>

        <!-- Progress Overlay Modal -->
        <div x-show="uploading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="w-full max-w-md transform overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-2xl border border-slate-100 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-campus-50 text-campus-700 shadow-inner mb-4">
                    <i data-lucide="upload-cloud" class="h-7 w-7 animate-bounce"></i>
                </div>

                <h3 class="text-lg font-bold text-slate-800 tracking-tight" x-text="statusText">Mengunggah file...</h3>
                
                <!-- Progress Bar -->
                <div class="mt-4 w-full bg-slate-100 rounded-full h-3 overflow-hidden shadow-inner border border-slate-200/50">
                    <div class="bg-gradient-to-r from-campus-500 to-campus-700 h-full rounded-full transition-all duration-300 ease-out" :style="`width: ${progress}%`"></div>
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
