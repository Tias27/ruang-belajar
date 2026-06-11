<x-app-layout title="Ringkasan">
    @php($source = $summary->folder ?: $summary->document)
    <a class="text-sm text-campus-700" href="{{ $summary->folder ? route('folders.show', $summary->folder) : route('documents.show', $summary->document) }}">
        Kembali ke {{ $summary->folder ? 'folder' : 'dokumen' }}
    </a>
    <h1 class="mt-3 text-2xl font-semibold text-campus-900">Ringkasan {{ $source->title }}</h1>
    <div class="mt-6 grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Ringkasan Singkat</h2>
            <p class="mt-3 text-sm leading-6 text-slate-700">{{ $summary->short_summary }}</p>
            <h2 class="mt-6 font-semibold">Poin Penting</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                @foreach($summary->key_points ?? [] as $point)
                    <li class="rounded-lg bg-slate-50 p-3">{{ $point }}</li>
                @endforeach
            </ul>
        </section>
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold">Ringkasan Lengkap</h2>
            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $summary->full_summary }}</p>
            @if($summary->conclusion)
                <div class="mt-6 rounded-lg bg-campus-50 p-4 text-sm text-campus-900"><strong>Kesimpulan:</strong> {{ $summary->conclusion }}</div>
            @endif
            @if(! empty($summary->raw_response['source_snippets'] ?? []))
                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Sumber dari materi</h3>
                    <div class="mt-3 space-y-2">
                        @foreach($summary->raw_response['source_snippets'] as $snippet)
                            <div class="rounded-lg bg-white p-3 text-sm leading-6 text-slate-600">
                                <p class="font-semibold text-campus-700">{{ $snippet['title'] ?? 'Materi' }}</p>
                                <p class="mt-1">{{ $snippet['snippet'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
