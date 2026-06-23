<x-app-layout title="Panduan Belajar" subtitle="Cara mudah memaksimalkan fitur AI dan Belajar Bareng">
    <div class="space-y-10 pb-12">
        
        <!-- Header Banner Section -->
        <section class="relative overflow-hidden rounded-[2.25rem] bg-gradient-to-br from-campus-700 via-campus-800 to-accent-700 p-8 sm:p-12 text-white shadow-xl">
            <!-- Decorative blur shapes -->
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 h-60 w-60 rounded-full bg-accent-500/20 blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 backdrop-blur-md px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-white border border-white/10">
                    <i data-lucide="sparkles" class="h-3.5 w-3.5 text-amber-300"></i>
                    Panduan Cepat
                </span>
                <h1 class="mt-6 text-3xl sm:text-5xl font-black tracking-tight leading-[1.15]">
                    Mulai Belajar dengan Bantuan AI
                </h1>
                <p class="mt-4 text-base sm:text-lg text-campus-100 leading-relaxed max-w-2xl font-medium">
                    Ruang Belajar dirancang untuk membantumu memahami materi kuliah secara instan. 
                    Upload materi kuliahmu, lalu gunakan asisten AI untuk merangkum, bertanya jawab, latihan soal, atau belajar kelompok.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('documents.create') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-white px-6 font-bold text-campus-900 shadow-lg hover:bg-campus-50 transition active:scale-95">
                        <i data-lucide="upload-cloud" class="h-4.5 w-4.5 text-campus-700"></i>
                        Upload Materi Pertama
                    </a>
                    <a href="{{ route('documents.index') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-white/10 backdrop-blur-md border border-white/10 px-6 font-bold text-white hover:bg-white/20 transition active:scale-95">
                        <i data-lucide="library" class="h-4.5 w-4.5 text-campus-200"></i>
                        Buka Materi Saya
                    </a>
                </div>
            </div>
        </section>

        <!-- Visual Step-by-Step Roadmap -->
        <section class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-800">Alur Belajar Ideal</h2>
                    <p class="text-sm text-slate-500">Ikuti langkah sederhana ini untuk hasil pemahaman maksimal</p>
                </div>
            </div>
            
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                @foreach([
                    [
                        'step' => '01',
                        'title' => 'Upload Materi',
                        'desc' => 'Masukkan file PDF, Word (DOCX), PPTX, atau Gambar soal/catatan.',
                        'icon' => 'file-up',
                        'color' => 'from-blue-500 to-cyan-500',
                        'bg' => 'bg-blue-50/50 text-blue-600 border-blue-100'
                    ],
                    [
                        'step' => '02',
                        'title' => 'Buka Detail',
                        'desc' => 'Masuk ke halaman file/folder materi untuk mengakses menu asisten AI.',
                        'icon' => 'folder-open',
                        'color' => 'from-indigo-500 to-blue-500',
                        'bg' => 'bg-indigo-50/50 text-indigo-600 border-indigo-100'
                    ],
                    [
                        'step' => '03',
                        'title' => 'Gunakan AI',
                        'desc' => 'Mulai baca ringkasan, atau lakukan chat tanya jawab interaktif.',
                        'icon' => 'bot',
                        'color' => 'from-purple-500 to-indigo-500',
                        'bg' => 'bg-purple-50/50 text-purple-600 border-purple-100'
                    ],
                    [
                        'step' => '04',
                        'title' => 'Uji Kemampuan',
                        'desc' => 'Buat Latihan Soal (PG/Esai) & Kartu Belajar otomatis.',
                        'icon' => 'clipboard-check',
                        'color' => 'from-pink-500 to-purple-500',
                        'bg' => 'bg-pink-50/50 text-pink-600 border-pink-100'
                    ],
                    [
                        'step' => '05',
                        'title' => 'Belajar Bareng',
                        'desc' => 'Buat Room Belajar Bareng dan bagikan PIN ke teman kelompokmu.',
                        'icon' => 'users',
                        'color' => 'from-amber-500 to-pink-500',
                        'bg' => 'bg-amber-50/50 text-amber-600 border-amber-100'
                    ]
                ] as $item)
                    <div class="group relative rounded-3xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                        <!-- Step badge -->
                        <span class="absolute right-4 top-4 text-xs font-black tracking-widest text-slate-300 group-hover:text-slate-400 transition-colors">
                            {{ $item['step'] }}
                        </span>
                        
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl border {{ $item['bg'] }} mb-5 group-hover:scale-105 transition-transform duration-300 shadow-sm">
                            <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                        </div>
                        
                        <h3 class="font-bold text-slate-800 text-[15px]">{{ $item['title'] }}</h3>
                        <p class="mt-2.5 text-xs leading-relaxed text-slate-500">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Deep Dive: Core Features Breakdown -->
        <section class="space-y-6">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-slate-800">Bedah Detail Fitur</h2>
                <p class="text-sm text-slate-500">Ketahui kapan dan bagaimana menggunakan setiap fitur AI di Ruang Belajar</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                
                <!-- Ringkas Card -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <i data-lucide="file-text" class="h-5 w-5"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-[15px]">Ringkas Materi</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500 flex-1">
                        AI akan memilah dan menyusun intisari dokumen dalam poin-poin terstruktur, konsep kunci, dan kesimpulan. Sangat bagus dibaca pertama kali sebelum kamu mulai mendalami materi secara keseluruhan.
                    </p>
                    <div class="rounded-2xl bg-blue-50/50 px-4 py-3 border border-blue-100/50">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-blue-600 mb-1">Tips Penggunaan</span>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Gunakan ringkasan folder untuk mendapatkan gambaran besar dari gabungan beberapa dokumen kuliah sekaligus.
                        </p>
                    </div>
                </div>

                <!-- Tanya AI Card -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="bot" class="h-5 w-5"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-[15px]">Tanya AI (Chat/Streaming)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500 flex-1">
                        Asisten AI kami dapat menjawab semua pertanyaan berdasarkan konteks isi dokumenmu. Respons disajikan secara real-time (streaming), interaktif, serta dilengkapi dengan referensi file sumber asli.
                    </p>
                    <div class="rounded-2xl bg-amber-50/50 px-4 py-3 border border-amber-100/50">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-amber-600 mb-1">Fitur Gambar</span>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Kamu juga bisa mengunggah foto soal ujian atau coretan papan tulis, dan meminta AI menganalisisnya bersamamu.
                        </p>
                    </div>
                </div>

                <!-- Latihan Soal Card -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <i data-lucide="list-checks" class="h-5 w-5"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-[15px]">Latihan Soal (Quizzes)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500 flex-1">
                        Uji pemahamanmu secara berkala. AI akan menghasilkan soal-soal latihan (Pilihan Ganda atau Esai) yang disesuaikan secara dinamis dengan tingkat kesulitan materi kamu, lengkap dengan grading otomatis.
                    </p>
                    <div class="rounded-2xl bg-emerald-50/50 px-4 py-3 border border-emerald-100/50">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-600 mb-1">Pembahasan Detail</span>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Setelah menjawab, kamu akan mendapatkan penjelasan lengkap mengapa jawaban tersebut benar atau salah.
                        </p>
                    </div>
                </div>

                <!-- Kartu Belajar Card -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                            <i data-lucide="copy-check" class="h-5 w-5"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-[15px]">Kartu Belajar (Flashcards)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500 flex-1">
                        Sistem kartu bolak-balik digital untuk membantu mengingat rumus, definisi, dan poin krusial. Gunakan feedback review "Mudah", "Sedang", atau "Sulit" untuk mengoptimalkan ingatan jangka panjangmu (Active Recall).
                    </p>
                    <div class="rounded-2xl bg-purple-50/50 px-4 py-3 border border-purple-100/50">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-1">Spaced Repetition</span>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Ulangi kartu belajar secara berkala hingga semua kartu dirasa "Mudah" dikuasai.
                        </p>
                    </div>
                </div>

                <!-- Belajar Bareng Card -->
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col gap-4 md:col-span-2 lg:col-span-2">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                            <i data-lucide="users" class="h-5 w-5"></i>
                        </span>
                        <h3 class="font-bold text-slate-800 text-[15px]">Belajar Bareng (Study Room Collab)</h3>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500 flex-1">
                        Mau belajar bersama kelompok? Buat Study Room, lalu bagikan **4-digit PIN** room kepada teman-temanmu. Di dalam room kolaboratif ini, kamu bisa saling berdiskusi via chat kelompok secara real-time, membuat kuis kelompok, latihan kartu belajar bersama, dan didampingi langsung oleh asisten AI yang akan membantu menjawab pertanyaan di obrolan!
                    </p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-violet-50/50 px-4 py-3 border border-violet-100/50">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-violet-600 mb-1">Real-Time Sync</span>
                            <p class="text-[11px] text-slate-600 leading-relaxed">
                                Pesan obrolan disiarkan langsung ke semua anggota aktif secara real-time.
                            </p>
                        </div>
                        <div class="rounded-2xl bg-violet-50/50 px-4 py-3 border border-violet-100/50">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-violet-600 mb-1">AI Co-Pilot</span>
                            <p class="text-[11px] text-slate-600 leading-relaxed">
                                AI akan ikut menganalisis pertanyaan kelompok dan memberikan tanggapan secara real-time.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Tip & Recommendations -->
        <section class="rounded-[2rem] bg-white border border-slate-200/80 p-8 shadow-sm flex flex-col md:flex-row gap-8 items-center justify-between">
            <div class="max-w-xl">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="lightbulb" class="h-5 w-5 text-amber-500"></i>
                    Tips Tambahan Untukmu
                </h3>
                <ul class="mt-4 space-y-3.5 text-sm text-slate-600">
                    <li class="flex items-start gap-2.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-campus-600 mt-2 shrink-0"></span>
                        <span>Selalu kelompokkan file materi dari mata kuliah yang sama ke dalam **Satu Folder** agar ringkasan dan asisten AI dapat merangkum & menjawab secara komprehensif.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-campus-600 mt-2 shrink-0"></span>
                        <span>Jika kamu membuat Latihan Soal dari dalam Study Room, soal tersebut akan tersimpan untuk room tersebut sehingga teman kelompokmu juga bisa mencobanya.</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-campus-600 mt-2 shrink-0"></span>
                        <span>Gunakan riwayat di menu **Riwayat AI** untuk melihat obrolan lamamu tanpa perlu mengulang dari awal.</span>
                    </li>
                </ul>
            </div>
            
            <div class="shrink-0 p-6 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col items-center text-center max-w-xs">
                <div class="h-12 w-12 rounded-full bg-campus-50 text-campus-600 flex items-center justify-center mb-3.5 shadow-inner">
                    <i data-lucide="help-circle" class="h-6 w-6"></i>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">Ada Masalah/Pertanyaan?</h4>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Hubungi admin atau dosen pengampu jika ada kendala saat pemrosesan dokumen kuliahmu.</p>
                <a href="{{ route('student.dashboard') }}" class="mt-4 w-full inline-flex h-10 items-center justify-center rounded-xl bg-campus-700 text-white text-xs font-bold hover:bg-campus-800 transition">
                    Kembali ke Beranda
                </a>
            </div>
        </section>

    </div>
</x-app-layout>
