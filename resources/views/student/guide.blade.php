<x-app-layout title="Panduan Belajar" subtitle="Cara mudah menggunakan fitur AI dan Belajar Bareng">
    <div class="max-w-4xl mx-auto space-y-10 pb-12">
        
        <!-- Header Banner Section -->
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-tr from-campus-600 to-accent-600 p-6 sm:p-10 text-white shadow-md">
            <!-- Subtle background lights -->
            <div class="absolute -right-16 -top-16 h-36 w-36 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white border border-white/10">
                    <i data-lucide="sparkles" class="h-3 w-3 text-amber-300"></i>
                    Panduan Singkat
                </span>
                <h1 class="mt-4 text-2xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                    Mulai Belajar dengan Bantuan AI
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-campus-50 leading-relaxed max-w-2xl font-medium opacity-90">
                    Ruang Belajar bekerja berdasarkan materi kuliah yang kamu unggah. Kamu bisa merangkum, melakukan tanya jawab interaktif, membuat soal kuis, kartu belajar, atau berdiskusi kelompok dengan temanmu secara real-time.
                </p>
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('documents.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-white px-6 text-sm font-bold shadow-md transition hover:bg-slate-50 active:scale-[0.98] w-full sm:w-auto" style="color: #1456a3 !important;">
                        <i data-lucide="upload-cloud" class="h-4 w-4" style="color: #1f73c7 !important;"></i>
                        Upload Materi
                    </a>
                    <a href="{{ route('documents.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-white/15 border border-white/20 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-white/25 active:scale-[0.98] w-full sm:w-auto">
                        <i data-lucide="folder-open" class="h-4 w-4 text-white"></i>
                        Materi Saya
                    </a>
                </div>
            </div>
        </section>

        <!-- Steps Timeline (Roadmap) -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm">
            <div class="mb-8">
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="map" class="h-4.5 w-4.5 text-campus-600"></i>
                    Alur Belajar Ideal
                </h2>
                <p class="text-xs text-slate-500 mt-1">Ikuti langkah terstruktur berikut untuk mendapatkan hasil belajar maksimal</p>
            </div>

            <!-- Vertical Timeline -->
            <div class="space-y-0">
                
                <!-- Step 1 -->
                <div class="relative pl-10 sm:pl-12 pb-8">
                    <!-- Line connecting to next step -->
                    <div class="absolute left-[13px] sm:left-[15px] top-[14px] sm:top-[16px] bottom-0 w-px bg-slate-200"></div>
                    <!-- Circle -->
                    <span class="absolute left-0 top-0 flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-blue-50 text-blue-600 border border-blue-100 font-bold text-xs sm:text-sm shadow-sm z-10">
                        1
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            Unggah Materi Kuliah
                            <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold text-slate-500">Langkah Awal</span>
                        </h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500 max-w-2xl">
                            Upload file PDF, Word (DOCX), PPTX, atau Gambar catatan pelajaran ke sistem. Kamu juga bisa mengelompokkan beberapa file ke dalam satu **Folder** agar AI memahami konteks materi secara menyeluruh.
                        </p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="relative pl-10 sm:pl-12 pb-8">
                    <!-- Line connecting to next step -->
                    <div class="absolute left-[13px] sm:left-[15px] top-[14px] sm:top-[16px] bottom-0 w-px bg-slate-200"></div>
                    <!-- Circle -->
                    <span class="absolute left-0 top-0 flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-bold text-xs sm:text-sm shadow-sm z-10">
                        2
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-800 text-sm">Buka Halaman Detail File / Folder</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500 max-w-2xl">
                            Fitur-fitur AI tidak dijalankan dari dashboard utama, melainkan dari dalam halaman detail file atau folder materi yang ingin kamu pelajari. Klik file atau folder tersebut dari menu **Materi Saya**.
                        </p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative pl-10 sm:pl-12 pb-8">
                    <!-- Line connecting to next step -->
                    <div class="absolute left-[13px] sm:left-[15px] top-[14px] sm:top-[16px] bottom-0 w-px bg-slate-200"></div>
                    <!-- Circle -->
                    <span class="absolute left-0 top-0 flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-purple-50 text-purple-600 border border-purple-100 font-bold text-xs sm:text-sm shadow-sm z-10">
                        3
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-800 text-sm">Gunakan Asisten AI & Buat Latihan</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500 max-w-2xl">
                            Mulai dengan membaca **Ringkasan** materi untuk gambaran besar. Jika ada yang belum dipahami, gunakan **Tanya AI** untuk chat interaktif. Uji ingatanmu dengan membuat **Kartu Belajar** (flashcard) atau **Latihan Soal** kuis.
                        </p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="relative pl-10 sm:pl-12">
                    <!-- Circle -->
                    <span class="absolute left-0 top-0 flex h-7 w-7 sm:h-8 sm:w-8 items-center justify-center rounded-full bg-violet-50 text-violet-600 border border-violet-100 font-bold text-xs sm:text-sm shadow-sm z-10">
                        4
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-bold text-slate-800 text-sm">Belajar Bersama Teman (Study Room)</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500 max-w-2xl">
                            Buat **Belajar Bareng (Study Room)** dan bagikan PIN 4-digit kepada teman-teman sekelasmu. Kalian bisa berdiskusi bersama, berlatih kuis kelompok secara real-time, dan didampingi AI Co-Pilot untuk memecahkan pertanyaan di room.
                        </p>
                    </div>
                </div>

            </div>
        </section>

        <!-- Core Features Breakdown -->
        <section class="space-y-6">
            <div>
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="sparkles" class="h-4.5 w-4.5 text-campus-600"></i>
                    Fungsi & Fitur Utama
                </h2>
                <p class="text-xs text-slate-500 mt-1">Pahami peran masing-masing fitur AI untuk mendukung gaya belajarmu</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                
                <!-- Ringkas -->
                <div class="rounded-2xl border-l-4 border-l-blue-500 border-y border-r border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i data-lucide="file-text" class="h-4 w-4"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Ringkas Materi</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500">
                        Merangkum materi menjadi poin terstruktur, konsep utama, dan kesimpulan. Cocok untuk menghemat waktu membaca dokumen yang panjang.
                    </p>
                </div>

                <!-- Tanya AI -->
                <div class="rounded-2xl border-l-4 border-l-amber-500 border-y border-r border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <i data-lucide="bot" class="h-4 w-4"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Tanya AI (Chat)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500">
                        Chat interaktif real-time dengan referensi langsung ke materi asli. Mendukung unggah gambar/foto soal untuk dianalisis bersama AI.
                    </p>
                </div>

                <!-- Latihan Soal -->
                <div class="rounded-2xl border-l-4 border-l-emerald-500 border-y border-r border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i data-lucide="list-checks" class="h-4 w-4"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Latihan Soal</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500">
                        Membuat kuis pilihan ganda atau esai secara otomatis. Menghasilkan skor kelulusan beserta pembahasan lengkap di setiap pertanyaan.
                    </p>
                </div>

                <!-- Kartu Belajar -->
                <div class="rounded-2xl border-l-4 border-l-purple-500 border-y border-r border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                            <i data-lucide="copy-check" class="h-4 w-4"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Kartu Belajar</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500">
                        Metode *flashcard* digital bolak-balik untuk mengingat definisi penting dan rumus kuliah menggunakan sistem tinjauan berkala.
                    </p>
                </div>

                <!-- Belajar Bareng -->
                <div class="rounded-2xl border-l-4 border-l-violet-500 border-y border-r border-slate-200 bg-white p-5 shadow-sm space-y-3 sm:col-span-2 lg:col-span-2">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                            <i data-lucide="users" class="h-4 w-4"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-xs sm:text-sm">Belajar Bareng (Collab Room)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500">
                        Diskusi kelompok secara real-time via PIN. Kamu bisa berlatih kuis kelompok bersama, melihat anggota yang online, serta berinteraksi langsung dengan AI pendamping di dalam obrolan kelompok.
                    </p>
                </div>

            </div>
        </section>

        <!-- Pro Tips & FAQ -->
        <section class="rounded-2xl bg-white border border-slate-200/80 p-5 sm:p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="lightbulb" class="h-4 w-4 text-amber-500"></i>
                Tips Tambahan
            </h3>
            <ul class="space-y-3 text-xs text-slate-600">
                <li class="flex items-start gap-2 leading-relaxed">
                    <span class="h-1.5 w-1.5 rounded-full bg-campus-500 mt-1.5 shrink-0"></span>
                    <span><strong>Gunakan Folder:</strong> Jika dokumen kuliah memiliki kaitan satu sama lain, kumpulkan dalam satu folder agar asisten AI dapat merangkum seluruh file secara menyeluruh.</span>
                </li>
                <li class="flex items-start gap-2 leading-relaxed">
                    <span class="h-1.5 w-1.5 rounded-full bg-campus-500 mt-1.5 shrink-0"></span>
                    <span><strong>Riwayat Percakapan:</strong> Kamu bisa membuka riwayat obrolan AI lamamu kapan saja melalui menu **Riwayat AI** tanpa harus mengulangnya dari awal.</span>
                </li>
                <li class="flex items-start gap-2 leading-relaxed">
                    <span class="h-1.5 w-1.5 rounded-full bg-campus-500 mt-1.5 shrink-0"></span>
                    <span><strong>Kuis di Study Room:</strong> Semua latihan soal yang dibuat di dalam Study Room akan tersimpan secara kolektif untuk dicoba oleh teman kelompokmu.</span>
                </li>
            </ul>
        </section>

    </div>
</x-app-layout>
