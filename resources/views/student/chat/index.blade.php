<x-app-layout title="Riwayat" subtitle="Buka ulang percakapan belajar">
    <div class="min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.75rem] bg-campus-50 p-5 sm:p-7">
            <div class="flex min-w-0 flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="mx-auto min-w-0 max-w-xs text-center sm:mx-0 sm:max-w-2xl sm:text-left">
                    <span class="inline-flex rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-campus-700 shadow-sm">Riwayat</span>
                    <h1 class="mx-auto mt-4 max-w-xs text-center text-2xl font-semibold leading-tight tracking-tight text-campus-900 sm:mx-0 sm:max-w-none sm:text-left sm:text-3xl">Percakapan belajar kamu</h1>
                    <p class="mx-auto mt-2 max-w-[17rem] text-center text-sm leading-6 text-slate-600 sm:mx-0 sm:max-w-2xl sm:text-left">Lanjutkan tanya jawab dari file atau folder yang pernah kamu pakai.</p>
                </div>
            </div>

            <div class="mx-auto mt-5 grid w-full justify-items-center gap-3 sm:mx-0 sm:max-w-3xl sm:justify-items-start">
                <a href="{{ route('documents.index') }}" class="relative inline-flex h-12 w-[18.5rem] max-w-full items-center justify-center rounded-full bg-campus-700 px-12 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-campus-900 sm:w-auto">
                    <i data-lucide="messages-square-plus" class="absolute left-5 h-4 w-4"></i>
                    <span class="block w-full text-center">Tanya materi lagi</span>
                </a>

                <form method="GET" action="{{ route('chat.index') }}" class="grid w-[18.5rem] max-w-full min-w-0 gap-3 rounded-[1.25rem] p-0 sm:w-full sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:bg-white sm:p-3 sm:shadow-sm">
                    <div class="relative min-w-0 flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                        <input name="q" value="{{ $search }}" placeholder="Cari pertanyaan, jawaban, atau nama materi..." class="h-12 rounded-full border-slate-200" style="padding-left: 2.75rem;">
                    </div>
                    <button class="relative inline-flex h-12 w-full shrink-0 items-center justify-center rounded-full bg-campus-700 px-12 text-center text-sm font-semibold text-white shadow-sm hover:bg-campus-900 sm:w-auto sm:min-w-28">
                        <i data-lucide="search" class="absolute left-5 h-4 w-4 shrink-0"></i>
                        <span class="block w-full text-center">Cari</span>
                    </button>
                    @if($search !== '')
                        <a href="{{ route('chat.index') }}" class="inline-flex h-12 w-full shrink-0 items-center justify-center gap-2 rounded-full bg-slate-100 px-5 text-sm font-semibold text-slate-700 hover:bg-slate-200 sm:w-auto sm:min-w-28">
                            <i data-lucide="x" class="h-4 w-4 shrink-0"></i> Bersihkan
                        </a>
                    @endif
                </form>
            </div>
        </section>

        <section
            class="mt-5 min-w-0"
            x-data="{
                selected: [],
                bulkMode: false,
                allIds: @js($sessions->pluck('public_id')->values()),
                get allSelected() {
                    return this.allIds.length > 0 && this.selected.length === this.allIds.length;
                },
                toggleAll() {
                    this.selected = this.allSelected ? [] : [...this.allIds];
                },
            }"
        >
            <form method="POST" action="{{ route('chat.bulk-destroy') }}" onsubmit="return selected.length > 0 && confirm('Hapus ' + selected.length + ' riwayat? Pesan di dalamnya ikut terhapus.')">
                @csrf
                @method('DELETE')

                <div class="mb-4 flex min-w-0 flex-col gap-3 rounded-[1.25rem] bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 text-center sm:text-left">
                        <h2 class="text-base font-semibold text-slate-900">Daftar percakapan</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $sessions->total() }} sesi tersimpan<span x-show="selected.length > 0" x-cloak> · <span x-text="selected.length"></span> dipilih</span>
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        @if($sessions->count() > 0)
                            <button type="button" x-on:click="bulkMode = ! bulkMode; selected = []" class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-slate-100 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-200 sm:min-w-32">
                                <i data-lucide="list-checks" class="h-3.5 w-3.5"></i>
                                <span x-text="bulkMode ? 'Selesai pilih' : 'Pilih riwayat'"></span>
                            </button>
                            <button type="button" x-show="bulkMode" x-cloak x-on:click="toggleAll()" class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-slate-100 px-4 text-xs font-semibold text-slate-700 hover:bg-slate-200 sm:min-w-32">
                                <i data-lucide="check-square" class="h-3.5 w-3.5"></i>
                                <span x-text="allSelected ? 'Batal semua' : 'Pilih semua'"></span>
                            </button>
                        @endif
                        <button
                            type="submit"
                            x-show="bulkMode"
                            x-cloak
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-full bg-rose-600 px-4 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300 sm:min-w-32"
                            x-bind:disabled="selected.length === 0"
                        >
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                            Hapus terpilih
                        </button>
                    </div>
                </div>

                <div class="grid min-w-0 gap-3">
                    @forelse($sessions as $session)
                        @php
                            $source = $session->folder ?: $session->document;
                            $sourceType = $session->folder ? 'Folder' : 'File';
                            $sourceUrl = $source ? ($session->folder ? route('folders.show', $session->folder) : route('documents.show', $session->document)) : null;
                            $lastMessage = $session->latestMessage;
                        @endphp
                        <article class="min-w-0 overflow-hidden rounded-[1.25rem] bg-white p-4 shadow-sm transition hover:bg-campus-50" x-bind:class="selected.includes('{{ $session->public_id }}') ? 'ring-2 ring-rose-200 bg-rose-50/50' : ''">
                            <div class="flex min-w-0 items-start gap-3">
                                <label x-show="bulkMode" x-cloak class="mt-1 grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-2xl bg-slate-50 shadow-sm hover:bg-white">
                                    <input
                                        type="checkbox"
                                        name="session_ids[]"
                                        value="{{ $session->public_id }}"
                                        x-model="selected"
                                        aria-label="Pilih {{ $session->title }}"
                                    >
                                </label>

                                <a href="{{ route('chat.show', $session) }}" class="flex min-w-0 flex-1 items-start gap-3">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl {{ $session->folder ? 'bg-accent-50 text-accent-700' : 'bg-campus-50 text-campus-700' }}">
                                        <i data-lucide="{{ $session->folder ? 'folder' : 'messages-square' }}" class="h-5 w-5"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex min-w-0 flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $sourceType }}</span>
                                            <span class="rounded-full bg-campus-50 px-2.5 py-1 text-xs font-semibold text-campus-700">{{ $session->messages_count }} pesan</span>
                                            <span class="text-xs font-medium text-slate-400">{{ $session->updated_at->diffForHumans() }}</span>
                                        </span>
                                        <span class="mt-2 block truncate text-sm font-semibold text-slate-900">{{ $session->title }}</span>
                                        <span class="mt-1 block truncate text-xs text-slate-500">{{ $source?->title ?? 'Materi sudah tidak tersedia' }}</span>

                                        @if($lastMessage)
                                            <span class="mt-3 block rounded-2xl bg-slate-50 px-3 py-2">
                                                <span class="block text-xs font-semibold text-campus-700">{{ $lastMessage->role === 'user' ? 'Kamu' : 'RuangBelajar AI' }}</span>
                                                <span class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600">{{ $lastMessage->content }}</span>
                                            </span>
                                        @endif
                                    </span>
                                    <i data-lucide="chevron-right" class="hidden h-4 w-4 shrink-0 text-slate-400 sm:block"></i>
                                </a>
                            </div>

                            <div class="mt-3 flex min-w-0 flex-col gap-2 border-t border-slate-100 pt-3 text-xs sm:flex-row sm:items-center sm:justify-between">
                                <span class="min-w-0 truncate text-slate-500">Dibuat {{ $session->created_at->format('d M Y H:i') }}</span>
                                @if($sourceUrl)
                                    <a href="{{ $sourceUrl }}" class="inline-flex w-fit items-center gap-1 rounded-full bg-slate-100 px-3 py-2 font-semibold text-campus-700 hover:bg-campus-100">
                                        Buka materi <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                    </a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] bg-white p-8 text-center shadow-sm">
                            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-campus-50 text-campus-700">
                                <i data-lucide="message-circle-question" class="h-7 w-7"></i>
                            </span>
                            <p class="mt-4 text-sm font-semibold text-slate-800">Belum ada riwayat.</p>
                            <p class="mt-1 text-sm text-slate-500">Buka materi, lalu pilih Tanya materi untuk mulai.</p>
                            <a href="{{ route('documents.index') }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-full bg-campus-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-campus-900">
                                Pilih materi
                            </a>
                        </div>
                    @endforelse
                </div>
            </form>

            @if($sessions->hasPages())
                <div class="mt-5">{{ $sessions->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
