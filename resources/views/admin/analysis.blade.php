<x-app-layout title="Analisa Sistem & AI" subtitle="Analisis mendalam aktivitas penggunaan AI dan dokumen">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <p class="text-sm font-semibold text-campus-700">Pusat Pengelolaan</p>
        <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">Analisa Aktivitas & AI</h1>
                <p class="mt-1 text-sm text-slate-500">Pelajari perilaku belajar mahasiswa dan efektivitas fitur kecerdasan buatan.</p>
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </section>

    <!-- Stats Cards -->
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Interaksi AI</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($totalAiActions, 0, ',', '.') }}</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-campus-50 text-campus-700"><i data-lucide="sparkles" class="h-5 w-5"></i></span>
            </div>
            <p class="mt-4 text-xs text-slate-500">Total ringkasan, kartu belajar, kuis, dan tanya jawab yang diproses.</p>
        </div>

        <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-slate-500">Keaktifan Pembelajar</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $avgAiPerStudent }}x</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="trending-up" class="h-5 w-5"></i></span>
            </div>
            <p class="mt-4 text-xs text-slate-500">Rata-rata jumlah interaksi AI yang dilakukan oleh setiap pembelajar.</p>
        </div>

        <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-sm font-medium text-slate-500">Rasio Materi</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $avgDocPerStudent }}</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700"><i data-lucide="book-open" class="h-5 w-5"></i></span>
            </div>
            <p class="mt-4 text-xs text-slate-500">Rata-rata jumlah dokumen materi yang diunggah oleh setiap pembelajar.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <!-- AI Feature Distribution -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="font-semibold text-slate-900">Distribusi Penggunaan Fitur AI</h2>
            <p class="mt-1 text-sm text-slate-500">Perbandingan popularitas setiap fitur bantuan belajar AI di kalangan pengguna.</p>
            
            <!-- Stacked Progress Bar Chart -->
            <div class="mt-6">
                <div class="flex h-5 w-full overflow-hidden rounded-full bg-slate-100">
                    @if($totalAiActions > 0)
                        @foreach($pieData as $name => $data)
                            <div class="{{ $data['color'] }} transition-all" style="width: {{ $data['percentage'] }}%" title="{{ $name }}: {{ $data['percentage'] }}%"></div>
                        @endforeach
                    @else
                        <div class="w-full bg-slate-200"></div>
                    @endif
                </div>
            </div>

            <!-- Legend and Details -->
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach($pieData as $name => $data)
                    <div class="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full {{ $data['color'] }}"></span>
                            <span class="text-sm font-medium text-slate-700">{{ $name }}</span>
                        </div>
                        <div class="text-right">
                            <span class="block text-sm font-semibold text-slate-900">{{ number_format($data['count'], 0, ',', '.') }}</span>
                            <span class="block text-xs text-slate-500">{{ $data['percentage'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Insights -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Analisa & Insight Sistem</h2>
            <p class="mt-1 text-sm text-slate-500">Rekomendasi otomatis berbasis data aktivitas sistem.</p>
            
            <div class="mt-5 space-y-4">
                @forelse($insights as $insight)
                    <div class="flex gap-3 rounded-lg p-4 text-sm {{ 
                        $insight['type'] === 'success' ? 'bg-emerald-50 border border-emerald-100 text-emerald-800' : (
                        $insight['type'] === 'warning' ? 'bg-amber-50 border border-amber-100 text-amber-800' : 
                        'bg-blue-50 border border-blue-100 text-blue-800')
                    }}">
                        <div class="mt-0.5 shrink-0">
                            @if($insight['type'] === 'success')
                                <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-600"></i>
                            @elseif($insight['type'] === 'warning')
                                <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-600"></i>
                            @else
                                <i data-lucide="info" class="h-5 w-5 text-blue-600"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-semibold">{{ $insight['title'] }}</p>
                            <p class="mt-1 text-xs leading-relaxed opacity-90">{!! $insight['description'] !!}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada insight yang terkumpul.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
