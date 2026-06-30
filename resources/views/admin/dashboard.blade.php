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

    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        @foreach([
            ['label' => 'Pembelajar', 'key' => 'students', 'icon' => 'graduation-cap'],
            ['label' => 'Percakapan AI', 'key' => 'conversations', 'icon' => 'messages-square'],
            ['label' => 'Total Pendapatan dari Website dan Iklan', 'key' => 'total_revenue', 'icon' => 'wallet'],
        ] as $item)
            <div class="flex flex-col justify-between rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-slate-500">{{ $item['label'] }}</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-campus-50 text-campus-700"><i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i></span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $stats[$item['key']] }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-slate-900">Grafik Penggunaan Harian</h2>
                <p class="mt-1 text-sm text-slate-500">Aktivitas ringkasan, soal, kartu belajar, dan tanya materi.</p>
            </div>
        </div>
        <div class="mt-5 flex h-52 items-end gap-2 rounded-lg bg-slate-50 p-4">
            @forelse($dailyUsage as $day)
                <div class="flex h-full flex-1 flex-col justify-end gap-2">
                    <div class="w-full rounded-t bg-campus-500" style="height: {{ min(100, max(8, $day->total * 14)) }}%"></div>
                    <span class="text-center text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($day->date)->format('d/m') }}</span>
                </div>
            @empty
                <div class="grid h-full w-full place-items-center text-sm text-slate-500">Belum ada penggunaan AI.</div>
            @endforelse
        </div>
    </section>

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
