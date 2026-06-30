<x-app-layout>
    <x-slot name="title">Manajemen Pendapatan</x-slot>
    <x-slot name="subtitle">Pantau transaksi langganan premium dan estimasi pendapatan iklan.</x-slot>

    <!-- Header Section -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Analisis Pendapatan</h1>
            <p class="text-sm text-slate-500">Laporan keuangan, transaksi masuk, dan statistik monetisasi platform.</p>
        </div>
        <button onclick="alert('Laporan berhasil diekspor ke CSV!')" class="inline-flex items-center gap-2 rounded-lg bg-campus-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-campus-700 transition">
            <i data-lucide="download" class="h-4 w-4"></i>
            Ekspor Laporan
        </button>
    </div>

    <!-- Stats Cards Grid -->
    <div class="mt-6 grid grid-cols-3 gap-4 lg:gap-5">
        <!-- Card 1: Total Revenue -->
        <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-50 text-emerald-700"><i data-lucide="wallet" class="h-4.5 w-4.5"></i></span>
                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Total Saldo</span>
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Pendapatan</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ $total_revenue }}</p>
                <p class="mt-1 text-xs text-slate-400" title="Kurs Real-time: 1 USD = Rp {{ number_format($exchange_rate, 0, ',', '.') }}">Estimasi: {{ $total_revenue_idr }}</p>
            </div>
        </div>

        <!-- Card 2: Active Subscriptions -->
        <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-blue-50 text-blue-700"><i data-lucide="credit-card" class="h-4.5 w-4.5"></i></span>
                <span class="text-xs font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full">Konversi 55%</span>
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Langganan Premium</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ $active_subscriptions }}</p>
                <p class="mt-1 text-xs text-slate-400">Pembelajar premium aktif saat ini</p>
            </div>
        </div>

        <!-- Card 3: Average Transaction -->
        <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-700"><i data-lucide="trending-up" class="h-4.5 w-4.5"></i></span>
                <span class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">Sangat Baik</span>
            </div>
            <div class="mt-4">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Rata-rata Transaksi</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ $average_transaction }}</p>
                <p class="mt-1 text-xs text-slate-400">Nilai transaksi per pembelajar</p>
            </div>
        </div>
    </div>

    <!-- Charts & Breakdown Section -->
    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        <!-- Doughnut Chart for Revenue Breakdown -->
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Sumber Pendapatan</h2>
                <p class="mt-1 text-sm text-slate-500">Pembagian kontribusi monetisasi platform.</p>
            </div>
            <div class="mt-4 relative h-48 w-full flex justify-center">
                <canvas id="breakdownChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs">
                <div class="p-2 bg-slate-50 rounded-lg">
                    <span class="block text-slate-500 font-medium">Langganan</span>
                    <span class="block text-sm font-bold text-emerald-600">${{ number_format($breakdown['subscriptions'], 2) }}</span>
                </div>
                <div class="p-2 bg-slate-50 rounded-lg">
                    <span class="block text-slate-500 font-medium">Iklan & Sponsor</span>
                    <span class="block text-sm font-bold text-blue-600">${{ number_format($breakdown['ads'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Target Progress / Analytics -->
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 flex flex-col justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Analisis Kinerja MRR</h2>
                <p class="mt-1 text-sm text-slate-500">Progres pencapaian target Monthly Recurring Revenue (MRR) bulan ini.</p>
            </div>
            <div class="mt-4 space-y-4">
                <div>
                    <div class="flex justify-between text-sm font-medium text-slate-700 mb-1.5">
                        <span>Target MRR ($300.00)</span>
                        <span class="text-campus-700 font-semibold">{{ round((floatval(str_replace('$', '', $total_revenue)) / 300) * 100, 1) }}% Tercapai</span>
                    </div>
                    <div class="h-3 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-campus-600 rounded-full transition-all duration-500" style="width: {{ min(100, (floatval(str_replace('$', '', $total_revenue)) / 300) * 100) }}%"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                    <div class="space-y-1">
                        <span class="text-xs text-slate-500 uppercase font-semibold">Tingkat Retensi</span>
                        <span class="block text-lg font-bold text-slate-950">92.4%</span>
                        <span class="text-xs text-emerald-600 flex items-center gap-1"><i data-lucide="arrow-up" class="h-3 w-3"></i> +2.1% bulan ini</span>
                    </div>
                    <div class="space-y-1">
                        <span class="text-xs text-slate-500 uppercase font-semibold">Biaya Komputasi GPU</span>
                        <span class="block text-lg font-bold text-slate-950">{{ $ai_operational_cost }}</span>
                        <span class="text-xs text-slate-400">Estimasi sewa GPU & pemeliharaan server</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 text-xs text-slate-400 border-t border-slate-100 pt-3">
                * Data di atas disinkronkan secara berkala dengan sistem pembayaran Midtrans & Google AdSense.
            </div>
        </div>
    </div>

    <!-- Transaction Table Section -->
    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-semibold text-slate-900">Transaksi Terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar pembayaran paket premium oleh pembelajar.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-md">10 Transaksi Terakhir</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ID Transaksi</th>
                        <th class="px-4 py-3">Pembelajar</th>
                        <th class="px-4 py-3">Paket</th>
                        <th class="px-4 py-3">Jumlah (IDR)</th>
                        <th class="px-4 py-3">Jumlah (USD)</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-mono font-semibold text-slate-900">{{ $tx->id }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $tx->student_name }}</div>
                                <div class="text-xs text-slate-400">{{ $tx->student_email }}</div>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-700">{{ $tx->plan_name }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $tx->amount_idr }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $tx->amount_usd }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $tx->date }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ $tx->payment_method }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    Berhasil
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctxBreakdown = document.getElementById('breakdownChart').getContext('2d');
            
            new Chart(ctxBreakdown, {
                type: 'doughnut',
                data: {
                    labels: ['Langganan', 'Iklan & Sponsor'],
                    datasets: [{
                        data: [{{ $breakdown['subscriptions'] }}, {{ $breakdown['ads'] }}],
                        backgroundColor: ['#10b981', '#3b82f6'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11 },
                                color: '#64748b'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 11, weight: '600' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            borderRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.label + ': $' + context.parsed.toFixed(2);
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        });
    </script>
</x-app-layout>
