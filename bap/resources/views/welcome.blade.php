<x-app-layout title="RuangBelajar AI">
    <section class="grid min-h-[calc(100vh-3rem)] items-center gap-10 lg:grid-cols-[1.05fr_.95fr]">
        <div>
            <p class="mb-4 text-sm font-semibold uppercase tracking-wide text-campus-700">Asisten belajar untuk semua jenjang</p>
            <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-campus-900 sm:text-5xl">RuangBelajar AI</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">Unggah materi pelajaran, rangkum isi penting, buat soal latihan, susun kartu belajar, dan tanya materi berdasarkan dokumenmu.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                    <i data-lucide="user-plus" class="h-4 w-4"></i> Mulai Belajar
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    <i data-lucide="log-in" class="h-4 w-4"></i> Masuk
                </a>
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Materi Sistem Pernapasan</p>
                    <p class="text-xs text-slate-500">Ringkasan siap dipelajari</p>
                </div>
                <span class="rounded-md bg-campus-50 px-2 py-1 text-xs font-medium text-campus-700">Selesai</span>
            </div>
            <div class="mt-5 space-y-4">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-semibold">Poin Penting</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        <li>Organ utama sistem pernapasan manusia</li>
                        <li>Proses pertukaran oksigen dan karbon dioksida</li>
                        <li>Contoh soal dan pembahasan singkat</li>
                    </ul>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-sm text-slate-500">Tanya materi</p>
                    <p class="mt-2 text-sm font-medium text-slate-900">Jelaskan fungsi alveolus dengan bahasa sederhana.</p>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
