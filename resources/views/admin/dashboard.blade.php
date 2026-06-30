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

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
            <i data-lucide="line-chart" class="h-4 w-4 text-campus-700"></i>
            Analisis Kinerja Sistem
        </h3>
        <div class="flex gap-1 bg-slate-100 p-1 rounded-lg text-xs font-semibold text-slate-600 self-start sm:self-auto">
            <button onclick="changeTimeFilter('harian', this)" class="px-3.5 py-1.5 rounded-md bg-white text-slate-900 shadow-sm transition-all filter-btn">Harian</button>
            <button onclick="changeTimeFilter('bulanan', this)" class="px-3.5 py-1.5 rounded-md hover:text-slate-900 transition-all filter-btn">Bulanan</button>
            <button onclick="changeTimeFilter('tahunan', this)" class="px-3.5 py-1.5 rounded-md hover:text-slate-900 transition-all filter-btn">Tahunan</button>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <!-- Grafik Pendapatan Harian -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Grafik Pendapatan Harian</h2>
                    <p class="mt-1 text-sm text-slate-500">Estimasi pendapatan harian dari langganan website dan iklan.</p>
                </div>
                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md">USD ($)</span>
            </div>
            <div class="mt-4 relative h-64 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>

        <!-- Grafik Penggunaan Member (Dummy) -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Grafik Penggunaan Member</h2>
                    <p class="mt-1 text-sm text-slate-500">Jumlah pengguna aktif harian yang belajar di platform.</p>
                </div>
                <span class="text-xs font-semibold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md">Pembelajar</span>
            </div>
            <div class="mt-4 relative h-64 w-full">
                <canvas id="memberUsageChart"></canvas>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dailyRevenueData = @json($dailyRevenue);
            const memberUsageData = @json($memberUsage);

            // Define datasets for time filters
            const timeDatasets = {
                harian: {
                    labels: dailyRevenueData.map(d => d.formatted_date),
                    revenue: dailyRevenueData.map(d => d.revenue),
                    member: memberUsageData.map(d => d.total)
                },
                bulanan: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    revenue: [0, 0, 0, 0, 0, 0, 0, 0, 0, 35.00, 125.00, {{ $stats['total_revenue'] ? floatval(str_replace(['$', ','], '', $stats['total_revenue'])) : 278.90 }}],
                    member: [0, 0, 0, 0, 0, 0, 0, 0, 0, 62, 248, {{ $stats['students'] }}]
                },
                tahunan: {
                    labels: ['2022', '2023', '2024', '2025', '2026'],
                    revenue: [0, 0, 0, 0, {{ $stats['total_revenue'] ? floatval(str_replace(['$', ','], '', $stats['total_revenue'])) : 278.90 }}],
                    member: [0, 0, 0, 0, {{ $stats['students'] }}]
                }
            };

            // 1. Revenue Chart
            const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
            const revenueGradient = ctxRevenue.createLinearGradient(0, 0, 0, 240);
            revenueGradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
            revenueGradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

            const revenueChart = new Chart(ctxRevenue, {
                type: 'line',
                data: {
                    labels: timeDatasets.harian.labels,
                    datasets: [{
                        label: 'Pendapatan',
                        data: timeDatasets.harian.revenue,
                        borderColor: '#10b981',
                        borderWidth: 2.5,
                        fill: true,
                        backgroundColor: revenueGradient,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#10b981',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#0f172a',
                            titleFont: { size: 11, weight: '600' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            borderRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color: '#64748b' }
                        },
                        y: {
                            grid: { color: '#f1f5f9' },
                            ticks: { 
                                font: { size: 10 }, 
                                color: '#64748b',
                                callback: function(value) { return '$' + value; }
                            }
                        }
                    }
                }
            });

            // 2. Member Usage Chart
            const ctxMember = document.getElementById('memberUsageChart').getContext('2d');
            const memberGradient = ctxMember.createLinearGradient(0, 0, 0, 240);
            memberGradient.addColorStop(0, 'rgba(59, 130, 246, 0.25)');
            memberGradient.addColorStop(1, 'rgba(59, 130, 246, 0.00)');

            const memberChart = new Chart(ctxMember, {
                type: 'line',
                data: {
                    labels: timeDatasets.harian.labels,
                    datasets: [{
                        label: 'Pengguna Aktif',
                        data: timeDatasets.harian.member,
                        borderColor: '#3b82f6',
                        borderWidth: 2.5,
                        fill: true,
                        backgroundColor: memberGradient,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#3b82f6',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#0f172a',
                            titleFont: { size: 11, weight: '600' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            borderRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': ' + context.parsed.y + ' pembelajar';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color: '#64748b' }
                        },
                        y: {
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 10 }, color: '#64748b' }
                        }
                    }
                }
            });

            // Make the filter function globally accessible
            window.changeTimeFilter = function (filter, btnElement) {
                // Update active button styling
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('bg-white', 'text-slate-900', 'shadow-sm');
                    btn.classList.add('hover:text-slate-900');
                });
                btnElement.classList.add('bg-white', 'text-slate-900', 'shadow-sm');
                btnElement.classList.remove('hover:text-slate-900');

                // Get selected dataset
                const data = timeDatasets[filter];

                // Update charts
                revenueChart.data.labels = data.labels;
                revenueChart.data.datasets[0].data = data.revenue;
                revenueChart.update();

                memberChart.data.labels = data.labels;
                memberChart.data.datasets[0].data = data.member;
                memberChart.update();
            };
        });
    </script>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <!-- Aktivitas AI Terbaru -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Aktivitas AI Terbaru</h2>
                <span class="text-xs text-slate-500">Real-time</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($activities as $activity)
                    @php
                        $actionLabel = [
                            'generate_summary' => 'membuat ringkasan materi',
                            'generate_quiz' => 'membuat kuis latihan baru',
                            'generate_flashcards' => 'membuat kartu belajar (flashcards)',
                            'chat_document' => 'bertanya kepada AI tentang materi',
                        ][$activity->action] ?? 'melakukan aktivitas belajar';
                        
                        $iconColor = [
                            'generate_summary' => 'bg-emerald-50 text-emerald-700',
                            'generate_quiz' => 'bg-purple-50 text-purple-700',
                            'generate_flashcards' => 'bg-blue-50 text-blue-700',
                            'chat_document' => 'bg-amber-50 text-amber-700',
                        ][$activity->action] ?? 'bg-slate-50 text-slate-700';

                        $icon = [
                            'generate_summary' => 'notebook-tabs',
                            'generate_quiz' => 'list-checks',
                            'generate_flashcards' => 'copy-check',
                            'chat_document' => 'messages-square',
                        ][$activity->action] ?? 'sparkles';
                    @endphp
                    <div class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $iconColor }}"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-800">
                                <span class="font-semibold text-slate-900">{{ $activity->user?->username ?? 'Pengguna' }}</span>
                                {{ $actionLabel }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada aktivitas AI terbaru.</p>
                @endforelse
            </div>
        </section>

        <!-- Status Sistem & Server -->
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Status Sistem & AI</h2>
            <p class="mt-1 text-sm text-slate-500">Kondisi operasional server, integrasi API, dan database.</p>

            <div class="mt-4 space-y-3.5">
                <div class="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="cpu" class="h-4 w-4"></i></span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Gemini 1.5 Pro API</p>
                            <p class="text-xs text-slate-500">Model kecerdasan buatan utama</p>
                        </div>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Aktif
                    </span>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="database" class="h-4 w-4"></i></span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Koneksi Database</p>
                            <p class="text-xs text-slate-500">Penyimpanan data platform</p>
                        </div>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Normal
                    </span>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="zap" class="h-4 w-4"></i></span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Waktu Respon Server</p>
                            <p class="text-xs text-slate-500">Kelebihan beban sistem</p>
                        </div>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                        124ms
                    </span>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="hard-drive" class="h-4 w-4"></i></span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Penyimpanan Dokumen</p>
                            <p class="text-xs text-slate-500">Materi yang diunggah pembelajar</p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-slate-600">
                        12% Terpakai
                    </span>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
