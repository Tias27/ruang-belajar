<x-app-layout title="Ringkasan">
    @php
        $source = $summary->folder ?: $summary->document;
        $sourceUrl = $summary->folder ? route('folders.show', $summary->folder) : route('documents.show', $summary->document);
        $sourceType = $summary->folder ? 'Folder' : 'File';
        $sourceTitle = $source?->title ?? 'Materi';
        $sourceSnippets = $summary->raw_response['source_snippets'] ?? [];
    @endphp

    <div class="min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.75rem] bg-gradient-to-r from-campus-50 to-white p-5 sm:p-8 shadow-sm border border-campus-100 relative">
            <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm border border-slate-100 transition-colors hover:bg-campus-100" href="{{ $sourceUrl }}">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $summary->folder ? 'folder' : 'materi' }}
            </a>

            <div class="mt-5 flex min-w-0 flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <span class="inline-flex rounded-full bg-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-campus-700 shadow-sm ring-1 ring-slate-100">Ringkasan Materi</span>
                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-campus-950 sm:text-4xl">{{ $sourceTitle }}</h1>
                    <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-slate-600">Hasil ringkasan AI berisi inti materi, penjelasan lengkap, poin penting, dan kesimpulan.</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 shadow-sm border border-slate-100">{{ $sourceType }}</span>
                    <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 shadow-sm border border-slate-100">{{ $summary->created_at->format('d M Y H:i') }}</span>
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

        <div class="mt-6 grid min-w-0 gap-6 lg:grid-cols-[minmax(0,.85fr)_minmax(0,1.15fr)]">
            <section class="min-w-0 overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="list-checks" class="h-5 w-5 text-campus-600"></i> Poin Penting
                    </h2>
                    <span class="rounded-full bg-campus-50 px-3 py-1 text-xs font-bold text-campus-700 ring-1 ring-campus-200/50">{{ count($summary->key_points ?? []) }} Poin</span>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($summary->key_points ?? [] as $index => $point)
                        <div class="group flex min-w-0 gap-4 rounded-[1.25rem] bg-slate-50 p-4 transition-all hover:bg-white hover:shadow-md hover:ring-1 hover:ring-slate-200">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-white text-sm font-bold text-campus-700 shadow-sm ring-1 ring-slate-100 group-hover:bg-campus-50">{{ $index + 1 }}</span>
                            <p class="min-w-0 break-words text-[14px] leading-relaxed text-slate-700">{{ $point }}</p>
                        </div>
                    @empty
                        <div class="flex items-center justify-center rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-500">
                            Poin penting belum tersedia.
                        </div>
                    @endforelse
                </div>

                @if($summary->conclusion)
                    <div class="mt-6 rounded-[1.5rem] bg-gradient-to-br from-campus-50 to-campus-100 p-5 shadow-sm border border-campus-200/50">
                        <div class="flex items-center gap-2 mb-2">
                            <i data-lucide="lightbulb" class="h-5 w-5 text-campus-700"></i>
                            <h3 class="font-bold text-campus-900">Kesimpulan</h3>
                        </div>
                        <p class="break-words text-[14px] leading-relaxed text-campus-900/80">{{ $summary->conclusion }}</p>
                    </div>
                @endif
            </section>

            <section class="min-w-0 overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-sm border border-slate-100">
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="align-left" class="h-5 w-5 text-campus-600"></i> Ringkasan Lengkap
                </h2>
                <div class="mt-5 rounded-[1.5rem] bg-slate-50 p-6 text-[15px] leading-relaxed text-slate-700 ring-1 ring-slate-100 [&>h1]:mt-6 [&>h1]:mb-4 [&>h1]:text-xl [&>h1]:font-bold [&>h1]:text-slate-900 [&>h2]:mt-6 [&>h2]:mb-3 [&>h2]:text-lg [&>h2]:font-bold [&>h2]:text-slate-900 [&>h3]:mt-5 [&>h3]:mb-2 [&>h3]:text-[16px] [&>h3]:font-bold [&>h3]:text-slate-900 [&>p]:mb-4 [&>ul]:mb-4 [&>ul]:list-disc [&>ul]:pl-5 [&>ol]:mb-4 [&>ol]:list-decimal [&>ol]:pl-5 [&>li]:mb-1 [&>strong]:text-slate-900 [&>strong]:font-bold [&>a]:text-campus-600 [&>a]:underline hover:[&>a]:text-campus-700 break-words">{!! Str::markdown($summary->full_summary ?? '') !!}</div>

                @if(! empty($sourceSnippets))
                    <div class="mt-6" x-data="{ showSource: false }">
                        <button type="button" @click="showSource = !showSource" class="flex w-full items-center justify-between rounded-[1.25rem] bg-white p-4 text-sm font-bold text-slate-700 shadow-sm border border-slate-200 transition-colors hover:bg-slate-50">
                            <span class="flex items-center gap-2">
                                <i data-lucide="book-open" class="h-4 w-4 text-slate-400"></i> Lihat Sumber Referensi
                            </span>
                            <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition-transform" :class="showSource ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="showSource" x-cloak x-transition.opacity class="mt-4 space-y-3">
                            @foreach($sourceSnippets as $snippet)
                                <div class="min-w-0 rounded-[1.25rem] bg-white p-4 text-[14px] leading-relaxed text-slate-600 shadow-sm border border-slate-100 transition-colors hover:border-campus-200">
                                    <p class="truncate font-bold text-campus-700 mb-2 flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        {{ $snippet['title'] ?? 'Materi' }}
                                    </p>
                                    <p class="break-words">"... {{ $snippet['snippet'] ?? '' }} ..."</p>
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
