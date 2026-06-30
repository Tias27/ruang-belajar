<x-app-layout title="Beranda Pengelola" subtitle="Statistik sistem dan penggunaan AI">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <p class="text-sm font-semibold text-campus-700">Pusat Pengelolaan</p>
        <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">Beranda Pengelola</h1>
                <p class="mt-1 text-sm text-slate-500">Pantau aktivitas platform, dokumen, dan penggunaan AI harian.</p>
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-900">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </section>

    <div class="mt-5 grid grid-cols-3 gap-3 sm:gap-4">
        @foreach([
            ['label' => 'Pembelajar', 'key' => 'students', 'icon' => 'graduation-cap'],
            ['label' => 'Total Pendapatan', 'key' => 'total_revenue', 'icon' => 'wallet'],
            ['label' => 'Percakapan AI', 'key' => 'conversations', 'icon' => 'messages-square'],
        ] as $item)
            <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-campus-50 text-campus-700 sm:h-9 sm:w-9"><i data-lucide="{{ $item['icon'] }}" class="h-3.5 w-3.5 sm:h-4 sm:w-4"></i></span>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 sm:text-xs" title="{{ $item['key'] === 'total_revenue' ? 'Total Pendapatan dari Website dan Iklan' : $item['label'] }}">
                        {{ $item['label'] }}
                    </p>
                </div>
                <p class="mt-3 text-lg font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $stats[$item['key']] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <!-- Grafik Pendapatan Harian -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="font-semibold text-slate-900">Grafik Pendapatan Harian</h2>
                <p class="mt-1 text-sm text-slate-500">Estimasi pendapatan harian dari langganan website dan iklan.</p>
            </div>
            <div class="overflow-x-auto pb-2">
                <div class="mt-5 flex h-52 items-end gap-1 p-1 min-w-[420px] sm:min-w-0 sm:gap-2 sm:p-2">
                    @foreach($dailyRevenue as $day)
                        <div class="group relative flex h-full flex-1 flex-col justify-end gap-2">
                            <!-- Tooltip Analysis -->
                            <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 w-44 -translate-x-1/2 rounded-lg bg-slate-900 p-3 text-[11px] text-white shadow-xl opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <p class="font-semibold text-slate-300 border-b border-slate-800 pb-1.5 mb-1.5">{{ \Illuminate\Support\Carbon::parse($day->date)->format('d M Y') }}</p>
                                <div class="space-y-1">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400">Pendapatan:</span>
                                        <span class="font-bold text-emerald-400">${{ number_format($day->revenue, 2) }}</span>
                                    </div>
                                </div>
                                <!-- Tooltip arrow -->
                                <div class="absolute top-full left-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1 rotate-45 bg-slate-900"></div>
                            </div>

                            <!-- Bar -->
                            <div class="w-full rounded-t cursor-pointer transition-all hover:opacity-90" style="height: {{ min(100, max(12, ($day->revenue / 60) * 100)) }}%; background: linear-gradient(to top, #10b981, #34d399);"></div>
                            <span class="text-center text-[10px] text-slate-500 sm:text-xs">{{ $day->formatted_date }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Grafik Penggunaan Member (Dummy) -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <h2 class="font-semibold text-slate-900">Grafik Penggunaan Member</h2>
                <p class="mt-1 text-sm text-slate-500">Jumlah pengguna aktif harian yang belajar di platform.</p>
            </div>
            <div class="overflow-x-auto pb-2">
                <div class="mt-5 flex h-52 items-end gap-1 p-1 min-w-[420px] sm:min-w-0 sm:gap-2 sm:p-2">
                    @foreach($memberUsage as $day)
                        <div class="group relative flex h-full flex-1 flex-col justify-end gap-2">
                            <!-- Tooltip Analysis -->
                            <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 w-44 -translate-x-1/2 rounded-lg bg-slate-900 p-3 text-[11px] text-white shadow-xl opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <p class="font-semibold text-slate-300 border-b border-slate-800 pb-1.5 mb-1.5">{{ \Illuminate\Support\Carbon::parse($day->date)->format('d M Y') }}</p>
                                <div class="space-y-1">
                                    <div class="flex justify-between gap-2">
                                        <span class="text-slate-400">Pengguna Aktif:</span>
                                        <span class="font-bold text-blue-400">{{ $day->total }} siswa</span>
                                    </div>
                                </div>
                                <!-- Tooltip arrow -->
                                <div class="absolute top-full left-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1 rotate-45 bg-slate-900"></div>
                            </div>

                            <!-- Bar -->
                            <div class="w-full rounded-t cursor-pointer transition-all hover:opacity-90" style="height: {{ min(100, max(12, ($day->total / 80) * 100)) }}%; background: linear-gradient(to top, #3b82f6, #60a5fa);"></div>
                            <span class="text-center text-[10px] text-slate-500 sm:text-xs">{{ $day->formatted_date }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Pengguna Terbaru</h2>
                <a class="text-sm font-semibold text-campus-700" href="{{ route('admin.users.index') }}">Lihat semua</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($users as $user)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $user->username }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                        <span class="rounded-md bg-white px-2 py-1 text-xs font-semibold capitalize text-slate-600 shadow-sm">{{ $user->role === 'admin' ? 'pengelola' : 'pembelajar' }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada pengguna.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Dokumen Terbaru</h2>
                <a class="text-sm font-semibold text-campus-700" href="{{ route('admin.documents.index') }}">Lihat semua</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($documents as $document)
                    <div class="rounded-lg bg-slate-50 p-3 text-sm">
                        <p class="font-semibold text-slate-900">{{ $document->title }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $document->user?->username }} | {{ $document->status }} | {{ $document->created_at->format('d M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada dokumen.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
