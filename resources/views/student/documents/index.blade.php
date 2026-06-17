@php
    $intent = request()->query('intent');
    $isSelectionMode = in_array($intent, ['chat', 'summary', 'quiz', 'flashcard']);
    
    $title = match($intent) {
        'chat' => 'Pilih Materi Tanya AI',
        'summary' => 'Pilih Materi Ringkasan',
        'quiz' => 'Pilih Materi Latihan Soal',
        'flashcard' => 'Pilih Materi Flashcard',
        default => 'Materi Saya',
    };
    
    $subtitle = match($intent) {
        'chat' => 'Pilih materi (folder/file) untuk mulai chat baru dengan AI',
        'summary' => 'Pilih materi (folder/file) untuk dibuatkan ringkasan oleh AI',
        'quiz' => 'Pilih materi (folder/file) untuk mulai latihan soal',
        'flashcard' => 'Pilih materi (folder/file) untuk dibuatkan flashcard belajar',
        default => 'Buka materi untuk ringkas, tanya AI, latihan, atau flashcard',
    };
@endphp

<x-app-layout 
    :title="$title" 
    :subtitle="$subtitle"
>
    <!-- Header Page -->
    <section class="overflow-hidden rounded-[1.75rem] bg-gradient-to-r from-campus-50 to-white p-5 sm:p-8 shadow-sm border border-campus-100 relative">
        <div class="relative grid gap-6 lg:grid-cols-[1fr_auto] lg:items-start">
            <div>
                <span class="inline-flex rounded-full bg-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-campus-700 shadow-sm ring-1 ring-slate-100">
                    {{ $isSelectionMode ? $title : 'Materi Saya' }}
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-campus-950 sm:text-4xl">
                    {{ $isSelectionMode ? 'Pilih materi untuk dipelajari' : 'Pusat Materi Pembelajaran' }}
                </h1>
                <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-slate-600">
                    {{ $isSelectionMode ? 'Klik pada folder atau file di bawah ini untuk langsung memproses aktivitas AI pilihanmu.' : 'Unggah dokumen satuan atau kumpulkan dalam folder agar AI dapat membacanya sekaligus sebagai satu materi.' }}
                </p>

                <!-- Realtime Search -->
                <form method="GET" action="{{ route('documents.index') }}" x-data x-ref="searchForm" class="mt-6 max-w-md relative">
                    @if($intent)
                        <input type="hidden" name="intent" value="{{ $intent }}">
                    @endif
                    <div class="relative flex items-center">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                        <input name="q" value="{{ $search }}" 
                               @input.debounce.700ms="$refs.searchForm.submit()" 
                               placeholder="Cari nama materi atau folder..." 
                               class="h-12 w-full rounded-full border border-slate-200 bg-white pr-10 text-[15px] shadow-sm transition-all focus:border-campus-400 focus:outline-none focus:ring-4 focus:ring-campus-100/50"
                               style="padding-left: 2.75rem;">
                        @if($search !== '')
                            <a href="{{ route('documents.index', $intent ? ['intent' => $intent] : []) }}" class="absolute right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-rose-100 hover:text-rose-600 transition-colors" title="Bersihkan Pencarian">
                                <i data-lucide="x" class="h-3.5 w-3.5"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
            @if(! $isSelectionMode)
                <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-start">
                    <a href="{{ route('documents.create') }}" class="group relative inline-flex w-full sm:w-auto items-center justify-center gap-2 overflow-hidden rounded-full bg-campus-700 px-7 py-3.5 text-[15px] font-bold text-white shadow-lg shadow-campus-700/20 transition-all hover:-translate-y-0.5 hover:bg-campus-800 hover:shadow-campus-700/40">
                        <i data-lucide="upload-cloud" class="h-4 w-4 transition-transform group-hover:-translate-y-0.5"></i> 
                        <span>Upload Materi Baru</span>
                    </a>
                </div>
            @endif
        </div>
    </section>

    <!-- Folder Section -->
    @if($folders->count() > 0)
        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="folder-open" class="h-5 w-5 text-campus-600"></i>
                        Folder Gabungan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">AI akan membaca seluruh file dalam folder sebagai satu kesatuan.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-accent-50 px-3 py-1 text-xs font-bold text-accent-700 shadow-sm border border-accent-100">{{ $folders->total() }} Folder</span>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($folders as $folder)
                    @if($isSelectionMode)
                        @php
                            $actionUrl = match($intent) {
                                'chat' => route('folders.chat.create', $folder),
                                'summary' => route('folders.summaries.store', $folder),
                                'quiz' => route('folders.quizzes.store', $folder),
                                'flashcard' => route('folders.flashcards.store', $folder),
                                default => '#',
                            };
                            $icon = match($intent) {
                                'chat' => 'messages-square',
                                'summary' => 'notebook-tabs',
                                'quiz' => 'list-checks',
                                'flashcard' => 'copy-check',
                                default => 'folder',
                            };
                            $tone = in_array($intent, ['chat', 'flashcard']) ? 'accent' : 'campus';
                        @endphp
                        
                        @if($intent === 'quiz')
                            <div class="group flex flex-col justify-between min-h-[14rem] rounded-[1.5rem] bg-white p-5 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-md min-w-0">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-campus-50 to-campus-100 text-campus-700 shadow-sm">
                                        <i data-lucide="folder" class="h-6 w-6"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="block truncate text-base font-bold text-slate-900 group-hover:text-campus-700 transition-colors">{{ $folder->name }}</h3>
                                        <p class="mt-1 text-xs text-slate-500">{{ $folder->documents_count }} file dalam folder</p>
                                    </div>
                                </div>
                                
                                <form method="POST" action="{{ $actionUrl }}" x-data="{ loading: false, type: 'multiple_choice', count: 10 }" x-on:submit="loading = true" class="mt-5 border-t border-slate-50 pt-4">
                                    @csrf
                                    <input type="hidden" name="question_type" x-bind:value="type">
                                    <input type="hidden" name="question_count" x-bind:value="count">
                                    
                                    <div class="flex items-center justify-between gap-3 bg-slate-50 p-1.5 rounded-xl">
                                        <div class="flex gap-1 rounded-lg bg-slate-200/50 p-1">
                                            <button type="button" x-on:click="type='multiple_choice'" class="px-3 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-colors" x-bind:class="type === 'multiple_choice' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">PG</button>
                                            <button type="button" x-on:click="type='essay'" class="px-3 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-colors" x-bind:class="type === 'essay' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">Esai</button>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 pr-1">
                                            <button type="button" x-on:click="count = Math.max(1, Number(count) - 1)" class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm hover:bg-slate-100 hover:text-campus-600 transition"><i data-lucide="minus" class="h-3 w-3"></i></button>
                                            <span x-text="count" class="w-5 text-center text-xs font-bold text-slate-700"></span>
                                            <button type="button" x-on:click="count = Math.min(30, Number(count) + 1)" class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm hover:bg-slate-100 hover:text-campus-600 transition"><i data-lucide="plus" class="h-3 w-3"></i></button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" x-bind:disabled="loading" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-campus-700 py-2.5 text-[13px] font-bold text-white shadow-sm transition-all hover:bg-campus-800 active:scale-[0.98] disabled:cursor-wait disabled:opacity-75 disabled:hover:scale-100">
                                        <span x-show="!loading"><i data-lucide="list-checks" class="h-4 w-4"></i></span>
                                        <span x-show="loading" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                        <span x-text="loading ? 'Menyusun Soal...' : 'Mulai Latihan'"></span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ $actionUrl }}" x-data="{ loading: false }" x-on:submit="loading = true" class="w-full h-full min-w-0">
                                @csrf
                                <button type="submit" x-bind:disabled="loading" class="group flex h-full w-full flex-col justify-between rounded-[1.5rem] bg-white p-5 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:border-{{ $tone }}-200 hover:shadow-md text-left disabled:cursor-wait relative overflow-hidden">
                                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-{{ $tone }}-50/50 transition-transform group-hover:scale-150"></div>
                                    
                                    <div class="relative flex min-w-0 items-start gap-4">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-{{ $tone }}-50 to-{{ $tone }}-100 text-{{ $tone }}-700 shadow-sm group-hover:from-{{ $tone }}-100 group-hover:to-{{ $tone }}-200 transition-colors">
                                            <i data-lucide="folder" class="h-6 w-6" x-show="!loading"></i>
                                            <span x-show="loading" x-cloak class="h-5 w-5 animate-spin rounded-full border-2 border-{{ $tone }}-400 border-t-{{ $tone }}-700"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="block truncate text-base font-bold text-slate-900 group-hover:text-{{ $tone }}-800 transition-colors">{{ $folder->name }}</h3>
                                            <p class="mt-1 text-xs text-slate-500">{{ $folder->documents_count }} file dalam folder</p>
                                        </div>
                                    </div>

                                    <div class="relative mt-5 flex justify-end">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-{{ $tone }}-50 px-3.5 py-1.5 text-xs font-bold text-{{ $tone }}-700 shadow-sm transition-colors group-hover:bg-{{ $tone }}-600 group-hover:text-white">
                                            <span x-text="loading ? 'Memproses...' : '{{ $intent === 'chat' ? 'Mulai Chat' : ($intent === 'summary' ? 'Buat Ringkasan' : 'Buat Flashcard') }}'"></span>
                                            <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5" x-show="!loading"></i>
                                        </span>
                                    </div>
                                </button>
                            </form>
                        @endif
                    @else
                        <!-- Normal Mode Folder Card -->
                        <a href="{{ route('folders.show', $folder) }}" class="group flex flex-col justify-between rounded-[1.25rem] bg-white p-5 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:border-accent-200 hover:shadow-md relative overflow-hidden">
                            <div class="absolute right-0 top-0 h-24 w-24 -translate-y-6 translate-x-6 rounded-full bg-accent-50/50 transition-transform group-hover:scale-125"></div>
                            <div class="relative flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-accent-50 to-accent-100 text-accent-700 shadow-sm transition-colors group-hover:from-accent-100 group-hover:to-accent-200">
                                    <i data-lucide="folder" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0 flex-1 pr-8">
                                    <h3 class="block truncate text-[15px] font-bold text-slate-900 group-hover:text-accent-800 transition-colors">{{ $folder->name }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $folder->documents_count }} file dalam folder</p>
                                </div>
                            </div>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 flex h-8 w-8 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-all group-hover:bg-accent-600 group-hover:text-white group-hover:shadow-sm group-hover:scale-110">
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
            @if($folders->hasPages())
                <div class="mt-5">{{ $folders->links() }}</div>
            @endif
        </section>
    @endif

    <!-- Document Section -->
    <section class="mt-10" x-data="{
        selected: [],
        allIds: @js($documents->pluck('public_id')->values()),
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
    }">
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="file-text" class="h-5 w-5 text-slate-500"></i>
                    Dokumen Satuan
                </h2>
                <p class="mt-1 text-sm text-slate-500">{{ $documents->total() }} file individu di luar folder.</p>
            </div>
            
            @if(! $isSelectionMode && $documents->count() > 0)
                <div class="flex items-center gap-2 sm:justify-end">
                    <form method="POST" action="{{ route('documents.bulk-destroy') }}" @submit.prevent="if(selected.length > 0) confirmDelete($event, 'Hapus ' + selected.length + ' File?', 'Hapus permanen ' + selected.length + ' file terpilih beserta seluruh riwayat AI terkait? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="document_ids[]" :value="id">
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

        <div class="{{ $isSelectionMode ? 'grid gap-4 md:grid-cols-2 xl:grid-cols-3' : 'space-y-4' }}">
            @forelse($documents as $document)
                @if($isSelectionMode)
                    <!-- Selection Mode Document Card -->
                    @php
                        $actionUrl = match($intent) {
                            'chat' => route('chat.create', $document),
                            'summary' => route('summaries.store', $document),
                            'quiz' => route('quizzes.store', $document),
                            'flashcard' => route('flashcards.store', $document),
                            default => '#',
                        };
                        $icon = match($intent) {
                            'chat' => 'messages-square',
                            'summary' => 'notebook-tabs',
                            'quiz' => 'list-checks',
                            'flashcard' => 'copy-check',
                            default => 'file-text',
                        };
                        $tone = in_array($intent, ['chat', 'flashcard']) ? 'accent' : 'campus';
                    @endphp
                    
                    @if($intent === 'quiz')
                        <div class="group flex flex-col justify-between min-h-[15rem] rounded-[1.5rem] bg-white p-5 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-md min-w-0">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-campus-50 to-campus-100 text-campus-700 shadow-sm">
                                    <i data-lucide="file-text" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="block truncate text-sm font-bold text-slate-900 group-hover:text-campus-700 transition-colors" title="{{ $document->title }}">{{ $document->title }}</h3>
                                    <p class="mt-1 block truncate text-xs text-slate-500" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase">{{ $document->extension }}</span>
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ number_format($document->size / 1024 / 1024, 2) }} MB</span>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" action="{{ $actionUrl }}" x-data="{ loading: false, type: 'multiple_choice', count: 10 }" x-on:submit="loading = true" class="mt-5 border-t border-slate-50 pt-4">
                                @csrf
                                <input type="hidden" name="question_type" x-bind:value="type">
                                <input type="hidden" name="question_count" x-bind:value="count">
                                
                                <div class="flex items-center justify-between gap-3 bg-slate-50 p-1.5 rounded-xl">
                                    <div class="flex gap-1 rounded-lg bg-slate-200/50 p-1">
                                        <button type="button" x-on:click="type='multiple_choice'" class="px-3 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-colors" x-bind:class="type === 'multiple_choice' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">PG</button>
                                        <button type="button" x-on:click="type='essay'" class="px-3 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wide transition-colors" x-bind:class="type === 'essay' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">Esai</button>
                                    </div>
                                    <div class="flex items-center gap-2 pr-1">
                                        <button type="button" x-on:click="count = Math.max(1, Number(count) - 1)" class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm hover:bg-slate-100 hover:text-campus-600 transition"><i data-lucide="minus" class="h-3 w-3"></i></button>
                                        <span x-text="count" class="w-5 text-center text-xs font-bold text-slate-700"></span>
                                        <button type="button" x-on:click="count = Math.min(30, Number(count) + 1)" class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-slate-600 shadow-sm hover:bg-slate-100 hover:text-campus-600 transition"><i data-lucide="plus" class="h-3 w-3"></i></button>
                                    </div>
                                </div>
                                
                                <button type="submit" x-bind:disabled="loading" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-campus-700 py-2.5 text-[13px] font-bold text-white shadow-sm transition-all hover:bg-campus-800 active:scale-[0.98] disabled:cursor-wait disabled:opacity-75 disabled:hover:scale-100">
                                    <span x-show="!loading"><i data-lucide="list-checks" class="h-4 w-4"></i></span>
                                    <span x-show="loading" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                    <span x-text="loading ? 'Menyusun...' : 'Mulai Latihan'"></span>
                                </button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ $actionUrl }}" x-data="{ loading: false }" x-on:submit="loading = true" class="w-full min-w-0">
                            @csrf
                            <button type="submit" x-bind:disabled="loading" class="group flex w-full min-w-0 items-center gap-4 rounded-[1.25rem] bg-white p-4 shadow-sm border border-slate-100 transition-all hover:-translate-y-0.5 hover:border-{{ $tone }}-200 hover:shadow-md text-left disabled:cursor-wait">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 transition-colors group-hover:bg-{{ $tone }}-50 group-hover:text-{{ $tone }}-600">
                                    <i data-lucide="file-text" class="h-5 w-5" x-show="!loading"></i>
                                    <span x-show="loading" x-cloak class="h-5 w-5 animate-spin rounded-full border-2 border-{{ $tone }}-400 border-t-{{ $tone }}-700"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="block truncate text-sm font-bold text-slate-900 group-hover:text-{{ $tone }}-700 transition-colors">{{ $document->title }}</h3>
                                    <div class="mt-1.5 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase">{{ $document->extension }}</span>
                                        <span class="rounded-md {{ $document->status === 'processed' ? 'bg-campus-50 text-campus-700' : ($document->status === 'processing' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }} px-2 py-0.5 text-[10px] font-bold">
                                            {{ $document->status === 'processed' ? 'AI Ready' : ($document->status === 'processing' ? 'Membaca...' : 'Pending') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 pl-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm transition-colors group-hover:border-transparent group-hover:bg-{{ $tone }}-600 group-hover:text-white">
                                        <span x-text="loading ? 'Memproses...' : 'Pilih'"></span>
                                        <i data-lucide="arrow-right" class="h-3 w-3" x-show="!loading"></i>
                                    </span>
                                </div>
                            </button>
                        </form>
                    @endif
                @else
                    <!-- Normal Mode Document Card (Horizontal List) -->
                    <article class="group flex flex-col xl:flex-row xl:items-center gap-4 xl:gap-6 rounded-[1.5rem] bg-white p-5 shadow-sm border border-slate-100 transition-all hover:-translate-y-1 hover:shadow-md relative min-w-0">
                        <div class="flex min-w-0 flex-1 items-start gap-4">
                            <label class="mt-3.5 flex h-6 w-6 shrink-0 items-center justify-center">
                                <input type="checkbox" value="{{ $document->public_id }}" x-model="selected" aria-label="Pilih {{ $document->title }}" class="h-5 w-5 rounded border-slate-300 text-campus-600 focus:ring-campus-500">
                            </label>
                            <a href="{{ route('documents.show', $document) }}" class="flex min-w-0 flex-1 items-center gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-50 text-slate-500 shadow-sm transition-colors group-hover:bg-campus-50 group-hover:text-campus-600">
                                    <i data-lucide="file-text" class="h-6 w-6"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="block truncate text-[15px] font-bold text-slate-900 group-hover:text-campus-700 transition-colors" title="{{ $document->title }}">{{ $document->title }}</h3>
                                    <p class="mt-0.5 block truncate text-[12px] text-slate-500" title="{{ $document->original_name }}">{{ $document->original_name }}</p>
                                    <div class="mt-2.5 flex flex-wrap gap-2">
                                        <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 uppercase">{{ $document->extension }}</span>
                                        <span class="rounded-md {{ $document->status === 'processed' ? 'bg-campus-50 text-campus-700' : ($document->status === 'processing' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500') }} px-2.5 py-0.5 text-[11px] font-bold">
                                            {{ $document->status === 'processed' ? 'AI Ready' : ($document->status === 'processing' ? 'Membaca...' : 'Pending') }}
                                        </span>
                                        <span class="rounded-md bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">{{ number_format($document->size / 1024 / 1024, 2) }} MB</span>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Actions row -->
                        <div class="flex flex-wrap items-center gap-2 pt-4 xl:pt-0 border-t border-slate-50 xl:border-0 mt-2 xl:mt-0 xl:shrink-0">
                            <form method="POST" action="{{ route('chat.create', $document) }}">@csrf
                                <button class="flex h-9 items-center justify-center gap-2 rounded-xl bg-campus-50 px-4 text-[12px] font-bold text-campus-700 shadow-sm transition-colors hover:bg-campus-600 hover:text-white">
                                    <i data-lucide="messages-square" class="h-4 w-4"></i> Tanya AI
                                </button>
                            </form>
                            <form method="POST" action="{{ route('summaries.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">@csrf
                                <button x-bind:disabled="loading" class="flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-[12px] font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-300 disabled:opacity-50">
                                    <i data-lucide="notebook-tabs" class="h-4 w-4 text-slate-500" x-show="!loading"></i>
                                    <span x-show="loading" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-campus-700"></span>
                                    <span>Ringkas</span>
                                </button>
                            </form>
                            <div x-data="{loading:false, open:false, type:'multiple_choice', count:10}" class="relative">
                                <button type="button" @click="open=!open; $event.preventDefault()" class="flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-[12px] font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-300">
                                    <i data-lucide="list-checks" class="h-4 w-4 text-slate-500"></i> <span>Latihan</span>
                                </button>
                                <!-- Quiz Dropdown Popover -->
                                <div x-show="open" @click.outside="open=false" x-transition.opacity x-cloak class="absolute bottom-full right-0 mb-2 w-[240px] rounded-2xl border border-slate-100 bg-white p-4 shadow-xl z-50">
                                    <form method="POST" action="{{ route('quizzes.store', $document) }}" x-on:submit="loading=true">@csrf
                                        <input type="hidden" name="question_type" x-bind:value="type">
                                        <input type="hidden" name="question_count" x-bind:value="count">
                                        <p class="mb-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pengaturan Quiz</p>
                                        <div class="flex gap-1 rounded-lg bg-slate-100 p-1 mb-3">
                                            <button type="button" @click="type='multiple_choice'" class="flex-1 py-1.5 rounded text-[11px] font-bold uppercase transition" :class="type === 'multiple_choice' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">PG</button>
                                            <button type="button" @click="type='essay'" class="flex-1 py-1.5 rounded text-[11px] font-bold uppercase transition" :class="type === 'essay' ? 'bg-white text-campus-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">Esai</button>
                                        </div>
                                        <div class="flex items-center justify-between bg-slate-50 rounded-xl px-3 py-2 mb-4">
                                            <span class="text-xs font-semibold text-slate-600">Jumlah Soal</span>
                                            <div class="flex items-center gap-1">
                                                <button type="button" @click="count = Math.max(1, Number(count) - 1)" class="h-6 w-6 rounded-full bg-white flex items-center justify-center text-slate-500 shadow-sm hover:bg-slate-100"><i data-lucide="minus" class="h-3 w-3"></i></button>
                                                <span x-text="count" class="w-6 text-center text-[13px] font-bold text-slate-800"></span>
                                                <button type="button" @click="count = Math.min(30, Number(count) + 1)" class="h-6 w-6 rounded-full bg-white flex items-center justify-center text-slate-500 shadow-sm hover:bg-slate-100"><i data-lucide="plus" class="h-3 w-3"></i></button>
                                            </div>
                                        </div>
                                        <button x-bind:disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-xl bg-campus-700 py-2.5 text-[13px] font-bold text-white shadow-sm transition hover:bg-campus-800 active:scale-[0.98] disabled:opacity-75">
                                            <span x-show="loading" x-cloak class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                            <span x-text="loading ? 'Menyiapkan...' : 'Buat Quiz Sekarang'"></span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('flashcards.store', $document) }}" x-data="{loading:false}" x-on:submit="loading=true">@csrf
                                <button x-bind:disabled="loading" class="flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-[12px] font-bold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 disabled:opacity-50">
                                    <i data-lucide="copy-check" class="h-4 w-4" x-show="!loading"></i>
                                    <span x-text="loading ? '...' : 'Flashcard'" x-show="loading" x-cloak></span>
                                    <span x-show="!loading">Flashcard</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('documents.destroy', $document) }}" @submit.prevent="confirmDelete($event, 'Hapus File?', 'Hapus permanen file ini beserta seluruh riwayat AI terkait? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
                                <button class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-[12px] font-bold text-rose-600 transition hover:bg-rose-100 hover:text-rose-700" title="Hapus">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </article>
                @endif
            @empty
                <div class="col-span-full flex flex-col items-center justify-center rounded-[2rem] bg-white p-12 text-center shadow-sm border border-slate-100 border-dashed">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-400 mb-4 shadow-inner">
                        <i data-lucide="file-question" class="h-10 w-10"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Belum Ada Materi</h3>
                    <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto">Silakan klik "Upload Materi Baru" untuk mulai belajar dengan AI atau kumpulkan beberapa file dalam satu folder.</p>
                </div>
            @endforelse
        </div>

        @if($documents->hasPages())
            <div class="mt-6 border-t border-slate-100 pt-5">
                {{ $documents->links() }}
            </div>
        @endif

        <!-- Custom Confirm Modal -->
        <div x-cloak x-show="showModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="showModal" x-transition.scale.origin.bottom class="relative transform overflow-hidden rounded-[2rem] bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100">
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
</x-app-layout>