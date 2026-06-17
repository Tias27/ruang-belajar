<x-app-layout title="Riwayat" subtitle="Buka ulang percakapan belajar">
    <div class="min-w-0 overflow-x-hidden">
        <!-- Header Page -->
        <section class="overflow-hidden rounded-[1.75rem] bg-gradient-to-r from-campus-50 to-white p-5 sm:p-8 shadow-sm border border-campus-100 relative">
            <div class="relative grid gap-6 lg:grid-cols-[1fr_auto] lg:items-start">
                <div>
                    <span class="inline-flex rounded-full bg-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-campus-700 shadow-sm ring-1 ring-slate-100">
                        Riwayat AI
                    </span>
                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-campus-950 sm:text-4xl">
                        Percakapan Anda
                    </h1>
                    <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-slate-600">
                        Lanjutkan sesi belajar atau ulas kembali jawaban AI dari file dan folder yang pernah Anda pelajari sebelumnya.
                    </p>

                    <!-- Realtime Search -->
                    <form method="GET" action="{{ route('chat.index') }}" x-data x-ref="searchForm" class="mt-6 max-w-md relative">
                        <div class="relative flex items-center">
                            <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                            <input name="q" value="{{ $search }}" 
                                   @input.debounce.700ms="$refs.searchForm.submit()" 
                                   placeholder="Cari isi percakapan..." 
                                   class="h-12 w-full rounded-full border border-slate-200 bg-white pr-10 text-[15px] shadow-sm transition-all focus:border-campus-400 focus:outline-none focus:ring-4 focus:ring-campus-100/50"
                                   style="padding-left: 2.75rem;">
                            @if($search !== '')
                                <a href="{{ route('chat.index') }}" class="absolute right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-rose-100 hover:text-rose-600 transition-colors" title="Bersihkan Pencarian">
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                
                <div class="flex flex-col sm:flex-row lg:flex-col gap-3 mt-2 lg:mt-0">
                    <a href="{{ route('documents.index', ['intent' => 'chat']) }}" class="inline-flex h-12 w-full shrink-0 items-center justify-center gap-2 rounded-full bg-campus-700 px-6 text-[14px] font-bold text-white shadow-sm transition hover:bg-campus-800 lg:w-auto">
                        <i data-lucide="messages-square-plus" class="h-4 w-4"></i> Tanya Materi Baru
                    </a>
                </div>
            </div>
        </section>

        <section
            class="mt-8 min-w-0"
            x-data="{
                selected: [],
                allIds: @js($sessions->pluck('public_id')->values()),
                get allSelected() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
                toggleAll() { this.selected = this.allSelected ? [] : [...this.allIds]; },
                showModal: false,
                modalTitle: '',
                modalMessage: '',
                formToSubmit: null,
                confirmDelete(e, title, message) {
                    e.preventDefault();
                    this.formToSubmit = e.target;
                    this.modalTitle = title;
                    this.modalMessage = message;
                    this.showModal = true;
                },
                submitForm() {
                    if(this.formToSubmit) this.formToSubmit.submit();
                }
            }"
        >
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="history" class="h-5 w-5 text-slate-500"></i>
                        Daftar Percakapan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $sessions->total() }} sesi tersimpan dalam riwayat.</p>
                </div>

                @if($sessions->count() > 0)
                    <div class="flex items-center gap-2 sm:justify-end">
                        <form method="POST" action="{{ route('chat.bulk-destroy') }}" @submit.prevent="if(selected.length > 0) confirmDelete($event, 'Hapus ' + selected.length + ' Riwayat?', 'Hapus permanen ' + selected.length + ' sesi percakapan terpilih? Tindakan ini tidak dapat dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <template x-for="id in selected" :key="id">
                                <input type="hidden" name="session_ids[]" :value="id">
                            </template>
                            <button type="submit" x-bind:disabled="selected.length === 0" class="inline-flex h-9 items-center justify-center gap-2 rounded-full px-4 text-xs font-bold shadow-sm transition" :class="selected.length > 0 ? 'bg-rose-600 text-white hover:bg-rose-700' : 'bg-slate-100 text-slate-400 cursor-not-allowed'">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                <span x-text="selected.length > 0 ? 'Hapus ' + selected.length : 'Hapus'"></span>
                            </button>
                        </form>

                        <button type="button" x-on:click="toggleAll()" class="inline-flex h-9 w-[120px] items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                            <i data-lucide="check-square" class="h-4 w-4 text-slate-400" x-show="!allSelected"></i>
                            <i data-lucide="x-square" class="h-4 w-4 text-slate-400" x-show="allSelected" x-cloak></i>
                            <span x-text="allSelected ? 'Batal Pilih' : 'Pilih Semua'"></span>
                        </button>
                    </div>
                @endif
            </div>

            <div class="space-y-4 min-w-0">
                @forelse($sessions as $session)
                    @php
                        $source = $session->folder ?: $session->document;
                        $sourceType = $session->folder ? 'Folder' : 'File';
                        $sourceUrl = $source ? ($session->folder ? route('folders.show', $session->folder) : route('documents.show', $session->document)) : null;
                        $lastMessage = $session->latestMessage;
                    @endphp
                    <article class="group flex flex-col xl:flex-row xl:items-center gap-4 xl:gap-6 rounded-[1.5rem] bg-white p-5 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-md relative" x-bind:class="selected.includes('{{ $session->public_id }}') ? 'ring-2 ring-campus-200 bg-campus-50' : ''">
                        <div class="flex min-w-0 flex-1 items-start gap-4">
                            <label class="mt-3.5 flex h-6 w-6 shrink-0 items-center justify-center">
                                <input type="checkbox" value="{{ $session->public_id }}" x-model="selected" aria-label="Pilih {{ $session->title }}" class="h-5 w-5 rounded border-slate-300 text-campus-600 focus:ring-campus-500">
                            </label>

                            <a href="{{ route('chat.show', $session) }}" class="flex min-w-0 flex-1 items-center gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-sm transition-colors {{ $session->folder ? 'bg-amber-50 text-amber-500 group-hover:bg-amber-100 group-hover:text-amber-600' : 'bg-slate-50 text-slate-500 group-hover:bg-campus-50 group-hover:text-campus-600' }}">
                                    <i data-lucide="{{ $session->folder ? 'folder' : 'file-text' }}" class="h-6 w-6"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                        <h3 class="block truncate text-[15px] font-bold text-slate-900 group-hover:text-campus-700 transition-colors" title="{{ $session->title }}">{{ $session->title }}</h3>
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 w-max">{{ $session->created_at->format('d M Y') }}</span>
                                    </div>
                                    <p class="mt-0.5 block truncate text-[12px] font-medium text-slate-500" title="{{ $source?->title ?? 'Materi sudah tidak tersedia' }}"><span class="text-slate-400">Sumber:</span> {{ $source?->title ?? 'Materi sudah tidak tersedia' }}</p>
                                    
                                    @if($lastMessage)
                                        <div class="mt-3 block rounded-xl bg-slate-50 p-3 sm:px-4 sm:py-3 border border-slate-100/50 relative">
                                            <div class="flex items-center gap-2 mb-1">
                                                @if($lastMessage->role === 'user')
                                                    <div class="h-4 w-4 rounded bg-slate-200 flex items-center justify-center text-slate-600"><i data-lucide="user" class="h-2.5 w-2.5"></i></div>
                                                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wide">Kamu bertanya</span>
                                                @else
                                                    <div class="h-4 w-4 rounded bg-campus-600 flex items-center justify-center text-white"><i data-lucide="bot" class="h-2.5 w-2.5"></i></div>
                                                    <span class="text-[11px] font-bold text-campus-700 uppercase tracking-wide">Ruang Belajar menjawab</span>
                                                @endif
                                            </div>
                                            <p class="line-clamp-2 text-[13px] leading-snug text-slate-700 font-medium">{{ $lastMessage->content }}</p>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </div>

                        <!-- Actions row -->
                        <div class="flex items-center justify-end gap-2 pt-4 xl:pt-0 border-t border-slate-50 xl:border-0 mt-2 xl:mt-0 xl:shrink-0">
                            @if($sourceUrl)
                                <a href="{{ $sourceUrl }}" class="flex h-9 items-center justify-center gap-2 rounded-xl bg-slate-50 px-3 text-[12px] font-bold text-slate-600 shadow-sm transition-colors hover:bg-slate-100 hover:text-slate-900" title="Lihat Materi">
                                    Buka Materi <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('chat.bulk-destroy') }}" @submit.prevent="confirmDelete($event, 'Hapus Riwayat?', 'Hapus permanen riwayat percakapan ini? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="session_ids[]" value="{{ $session->public_id }}">
                                <button class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-[12px] font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700" title="Hapus">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center rounded-[2rem] bg-white p-12 text-center shadow-sm border border-slate-100 border-dashed">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-400 mb-4 shadow-inner">
                            <i data-lucide="message-square-off" class="h-10 w-10"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800">Belum Ada Riwayat</h3>
                        <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto">Anda belum memulai percakapan apa pun. Buka materi dan pilih "Tanya AI" untuk memulai.</p>
                        <a href="{{ route('documents.index') }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-campus-700 px-5 py-2.5 text-[14px] font-bold text-white shadow-sm hover:bg-campus-800 transition-transform active:scale-[0.98]">
                            Mulai Belajar Sekarang
                        </a>
                    </div>
                @endforelse
            </div>

            @if($sessions->hasPages())
                <div class="mt-5">{{ $sessions->links() }}</div>
            @endif

        <!-- Custom Confirm Modal -->
        <div x-cloak x-show="showModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showModal" @click.outside="showModal = false" x-transition.scale.origin.bottom class="relative transform overflow-hidden rounded-[2rem] bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100">
                        <div class="bg-white px-6 pb-6 pt-8 sm:p-8 sm:pb-6">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-rose-100 sm:mx-0 sm:h-12 sm:w-12">
                                    <i data-lucide="alert-triangle" class="h-6 w-6 text-rose-600"></i>
                                </div>
                                <div class="mt-4 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                    <h3 class="text-xl font-bold leading-6 text-slate-900" id="modal-title" x-text="modalTitle"></h3>
                                    <div class="mt-3">
                                        <p class="text-[14px] text-slate-500" x-text="modalMessage"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-6 py-5 flex flex-col-reverse sm:flex-row sm:justify-end sm:px-8 gap-3">
                            <button type="button" @click="showModal = false" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-[14px] font-bold text-slate-700 shadow-sm border border-slate-200 hover:bg-slate-50 sm:w-auto transition-all">Batal</button>
                            <button type="button" @click="submitForm()" class="inline-flex w-full justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-[14px] font-bold text-white shadow-sm hover:bg-rose-700 sm:w-auto transition-all">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </section>
    </div>
</x-app-layout>
