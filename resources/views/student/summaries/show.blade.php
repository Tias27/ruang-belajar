<x-app-layout title="Ringkasan">
    @php
        $source = $summary->folder ?: $summary->document;
        $sourceUrl = $summary->folder ? route('folders.show', $summary->folder) : route('documents.show', $summary->document);
        $sourceType = $summary->folder ? 'Folder' : 'File';
        $sourceTitle = $source?->title ?? 'Materi';
        $sourceSnippets = $summary->raw_response['source_snippets'] ?? [];
    @endphp

    <div class="min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm hover:bg-campus-100" href="{{ $sourceUrl }}">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $summary->folder ? 'folder' : 'materi' }}
            </a>

            <div class="mt-5 flex min-w-0 flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-campus-700">Ringkasan materi</p>
                    <h1 class="mt-1 break-words text-2xl font-semibold tracking-tight text-campus-900 sm:text-3xl">{{ $sourceTitle }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Hasil ringkasan AI berisi inti materi, penjelasan lengkap, poin penting, dan kesimpulan.</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 shadow-sm">{{ $sourceType }}</span>
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 shadow-sm">{{ $summary->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>
        </section>

        @if($summary->status === 'processing')
            <section class="mt-5 rounded-[1.5rem] bg-white p-5 text-center shadow-sm">
                <script>
                    setTimeout(() => window.location.reload(), 5000);
                </script>
                <span class="mx-auto block h-11 w-11 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                <h2 class="mt-4 text-lg font-semibold text-campus-900">Ringkasan sedang dibuat</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">AI sedang membaca materi dan menyusun ringkasan. Kamu boleh tinggalkan halaman ini, lalu buka lagi beberapa saat lagi.</p>
                <a href="{{ request()->fullUrl() }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-full bg-campus-700 px-5 text-sm font-semibold text-white">Cek lagi</a>
            </section>
        @elseif($summary->status === 'failed')
            <section class="mt-5 rounded-[1.5rem] bg-white p-5 text-center shadow-sm">
                <span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-rose-50 text-rose-700">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </span>
                <h2 class="mt-4 text-lg font-semibold text-campus-900">Ringkasan belum berhasil dibuat</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">Layanan AI belum berhasil merespons. Kembali ke materi lalu coba buat ringkasan lagi.</p>
                <a href="{{ $sourceUrl }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-full bg-campus-700 px-5 text-sm font-semibold text-white">Kembali ke materi</a>
            </section>
        @else

        <section class="mt-5 min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
            <div class="flex min-w-0 items-start gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                    <i data-lucide="sparkles" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900">Ringkasan singkat</h2>
                    <p class="mt-2 break-words text-sm leading-7 text-slate-700">{{ $summary->short_summary }}</p>
                </div>
            </div>
        </section>

        <div class="mt-5 grid min-w-0 gap-5 lg:grid-cols-[minmax(0,.85fr)_minmax(0,1.15fr)]">
            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-slate-900">Poin penting</h2>
                    <span class="rounded-full bg-campus-50 px-2.5 py-1 text-xs font-semibold text-campus-700">{{ count($summary->key_points ?? []) }} poin</span>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse($summary->key_points ?? [] as $index => $point)
                        <div class="flex min-w-0 gap-3 rounded-[1.1rem] bg-slate-50 p-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-white text-xs font-semibold text-campus-700 shadow-sm">{{ $index + 1 }}</span>
                            <p class="min-w-0 break-words text-sm leading-6 text-slate-700">{{ $point }}</p>
                        </div>
                    @empty
                        <p class="rounded-[1.1rem] bg-slate-50 p-4 text-sm text-slate-500">Poin penting belum tersedia.</p>
                    @endforelse
                </div>

                @if($summary->conclusion)
                    <div class="mt-5 rounded-[1.25rem] bg-campus-50 p-4 text-sm leading-7 text-campus-900">
                        <p class="font-semibold">Kesimpulan</p>
                        <p class="mt-1 break-words">{{ $summary->conclusion }}</p>
                    </div>
                @endif
            </section>

            <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Ringkasan lengkap</h2>
                <div class="mt-4 whitespace-pre-line break-words rounded-[1.25rem] bg-slate-50 p-4 text-sm leading-8 text-slate-700">{{ $summary->full_summary }}</div>

                @if(! empty($sourceSnippets))
                    <div class="mt-5 rounded-[1.25rem] bg-white">
                        <h3 class="text-sm font-semibold text-slate-900">Sumber dari materi</h3>
                        <div class="mt-3 space-y-2">
                            @foreach($sourceSnippets as $snippet)
                                <div class="min-w-0 rounded-[1.1rem] bg-slate-50 p-3 text-sm leading-6 text-slate-600">
                                    <p class="truncate font-semibold text-campus-700">{{ $snippet['title'] ?? 'Materi' }}</p>
                                    <p class="mt-1 break-words">{{ $snippet['snippet'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        </div>
        @endif
    </div>
</x-app-layout>
