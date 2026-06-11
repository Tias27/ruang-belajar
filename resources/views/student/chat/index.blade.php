<x-app-layout title="Riwayat AI" subtitle="Buka ulang percakapan belajar">
    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-campus-700">Riwayat AI</p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-campus-900">Chat yang pernah kamu buat</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Semua tanya jawab dari file dan folder tersimpan di sini.</p>
            </div>
            <a href="{{ route('documents.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-campus-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                <i data-lucide="plus" class="h-4 w-4"></i> Tanya AI Lagi
            </a>
        </div>
    </section>

    <section class="mt-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('chat.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative min-w-0 flex-1">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input name="q" value="{{ $search }}" placeholder="Cari chat, jawaban, atau nama materi..." class="h-12" style="padding-left: 2.5rem;">
            </div>
            <button class="inline-flex h-12 min-w-24 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-campus-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-campus-900">
                <i data-lucide="search" class="h-4 w-4 shrink-0"></i> Cari
            </button>
            @if($search !== '')
                <a href="{{ route('chat.index') }}" class="inline-flex h-12 min-w-28 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="x" class="h-4 w-4 shrink-0"></i> Bersihkan
                </a>
            @endif
        </form>
    </section>

    <section
        class="mt-5"
        x-data="{
            selected: [],
            allIds: @js($sessions->pluck('public_id')->values()),
            get allSelected() {
                return this.allIds.length > 0 && this.selected.length === this.allIds.length;
            },
            toggleAll() {
                this.selected = this.allSelected ? [] : [...this.allIds];
            },
        }"
    >
        <form method="POST" action="{{ route('chat.bulk-destroy') }}" onsubmit="return confirm('Hapus riwayat yang dipilih? Pesan di dalamnya ikut terhapus.')">
            @csrf
            @method('DELETE')

            <div class="mb-3 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-800">
                        <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-campus-700 focus:ring-campus-500" x-bind:checked="allSelected" x-on:change="toggleAll()" @disabled($sessions->count() === 0)>
                        Pilih semua
                    </label>
                    <span class="text-xs font-medium text-slate-500">
                        <span x-text="selected.length"></span> dipilih dari {{ $sessions->total() }} sesi
                    </span>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    x-bind:disabled="selected.length === 0"
                >
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                    Hapus terpilih
                </button>
            </div>

            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Daftar percakapan</h2>
                <span class="text-xs font-medium text-slate-500">{{ $sessions->total() }} sesi</span>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
            @forelse($sessions as $session)
                @php
                    $source = $session->folder ?: $session->document;
                    $sourceType = $session->folder ? 'Folder' : 'Dokumen';
                    $sourceUrl = $source ? ($session->folder ? route('folders.show', $session->folder) : route('documents.show', $session->document)) : null;
                    $lastMessage = $session->latestMessage;
                @endphp
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-campus-100 hover:bg-campus-50" x-bind:class="selected.includes('{{ $session->public_id }}') ? 'border-rose-200 bg-rose-50/40' : ''">
                    <div class="flex items-start gap-3">
                        <label class="mt-1 inline-flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white hover:border-campus-200">
                            <input
                                type="checkbox"
                                name="session_ids[]"
                                value="{{ $session->public_id }}"
                                class="h-4 w-4 rounded border-slate-300 text-campus-700 focus:ring-campus-500"
                                x-model="selected"
                            >
                        </label>

                        <a href="{{ route('chat.show', $session) }}" class="min-w-0 flex-1">
                            <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg {{ $session->folder ? 'bg-accent-50 text-accent-700' : 'bg-campus-50 text-campus-700' }}">
                                <i data-lucide="{{ $session->folder ? 'folder' : 'messages-square' }}" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $sourceType }}</span>
                                    <span class="rounded-md bg-campus-50 px-2 py-1 text-xs font-semibold text-campus-700">{{ $session->messages_count }} pesan</span>
                                </div>
                                <h3 class="mt-2 truncate text-sm font-semibold text-slate-900">{{ $session->title }}</h3>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $source?->title ?? 'Materi sudah tidak tersedia' }}</p>
                            </div>
                            <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400"></i>
                            </div>

                            @if($lastMessage)
                                <div class="mt-4 rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-semibold text-campus-700">{{ $lastMessage->role === 'user' ? 'Kamu' : 'RuangBelajar AI' }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600">{{ $lastMessage->content }}</p>
                                </div>
                            @endif
                        </a>
                    </div>

                    <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <span>{{ $session->updated_at->diffForHumans() }}</span>
                        @if($sourceUrl)
                            <a href="{{ $sourceUrl }}" class="inline-flex items-center gap-1 font-semibold text-campus-700 hover:text-campus-900">
                                Buka materi <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center lg:col-span-2">
                    <i data-lucide="message-circle-question" class="mx-auto h-9 w-9 text-slate-400"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada riwayat.</p>
                    <p class="mt-1 text-sm text-slate-500">Buka materi, lalu pilih Tanya AI untuk mulai.</p>
                </div>
            @endforelse
            </div>
        </form>

        @if($sessions->hasPages())
            <div class="mt-5">{{ $sessions->links() }}</div>
        @endif
    </section>
</x-app-layout>
