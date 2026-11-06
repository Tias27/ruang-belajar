<x-app-layout title="Beranda">
    @php
        $firstName = str(auth()->user()->name ?: auth()->user()->username)->before(' ');
    @endphp
    <section class="screen" id="screen-home" aria-labelledby="home-title">
        <div class="page-heading">
            <div><p class="greeting">Hai, {{ $firstName }}</p><h1 id="home-title">Mau belajar apa hari ini?</h1><p class="subtitle">Satu materi dulu, pelan-pelan sampai paham</p></div>
            <a href="{{ route('documents.create') }}" class="button button-primary"><span data-icon="plus"></span>Tambah materi</a>
        </div>
        <div class="dashboard-grid">
            <div class="dashboard-primary">
                <section class="continue-panel" aria-labelledby="continue-title">
                    <div class="garden-scene workspace-garden" data-garden-scene data-garden-compact aria-hidden="true"></div>
                    <div class="continue-content">
                        <p class="eyebrow">{{ $latestMaterial ? 'Dari materi terbarumu' : 'Ruang belajarmu mulai di sini' }}</p>
                        <span class="file-meta">{{ $latestMaterial['meta'] ?? 'PDF, Word, slide, atau foto catatan' }}</span>
                        <h2 id="continue-title">{{ $latestMaterial['title'] ?? 'Mulai dari satu materi' }}</h2>
                        <p>{{ $latestMaterial ? 'Ambil intinya, bahas yang bingung, lalu coba latihan.' : 'Upload materi yang ingin kamu pahami. Pilih cara belajar yang paling cocok buatmu.' }}</p>
                        <a class="button button-primary" href="{{ $latestMaterial['url'] ?? route('documents.create') }}">{{ $latestMaterial ? 'Buka materi' : 'Tambah materi pertama' }}<span data-icon="arrow-right"></span></a>
                    </div>
                    <button class="motion-toggle" type="button" data-motion-toggle aria-pressed="false" aria-label="Jeda animasi"><span data-icon="pause"></span></button>
                </section>
                <section class="learning-start" aria-labelledby="learning-title">
                    <div class="section-heading"><div><h2 id="learning-title">Lagi butuh bantuan apa?</h2><p>{{ $latestMaterial ? 'Mulai dari materi terbaru, atau pilih materi lain di bawah.' : 'Tambah materi, lalu pilih salah satu cara ini.' }}</p></div></div>
                    <div class="learning-options">
                        @foreach([
                            ['summary', 'align-left', 'Ringkasan', 'Ambil poin pentingnya.'],
                            ['chat', 'message', 'Tanya AI', 'Bahas yang belum paham.'],
                            ['quiz', 'check-square', 'Latihan soal', 'Cek pemahamanmu.'],
                            ['flashcard', 'layers', 'Kartu belajar', 'Biar makin ingat.'],
                        ] as [$feature, $icon, $label, $description])
                            <a href="{{ $latestMaterial ? $latestMaterial['url'].'#learn-'.$feature : route('documents.create') }}"><span data-icon="{{ $icon }}"></span><strong>{{ $label }}</strong><span>{{ $description }}</span><span data-icon="arrow-up-right"></span></a>
                        @endforeach
                    </div>
                </section>
                <section class="materials-section" data-pagination-region="materials_page" aria-labelledby="materials-title">
                    <div class="section-heading"><div><h2 id="materials-title">Rak materimu</h2><p>{{ $stats['materials'] }} materi tersimpan</p></div><a class="text-link" href="{{ route('documents.index') }}">Lihat semua<span data-icon="arrow-right"></span></a></div>
                    <div class="material-list">
                        @forelse($recentMaterials as $material)
                            <a class="material-row" href="{{ $material['url'] }}"><span class="file-icon" data-icon="{{ $material['type'] === 'folder' ? 'folder' : 'file' }}"></span><div class="material-name"><strong>{{ $material['title'] }}</strong><span>{{ $material['meta'] }}</span></div><time class="row-date" datetime="{{ $material['created_at']->toIso8601String() }}">{{ $material['created_at']->diffForHumans() }}</time><span data-icon="arrow-up-right"></span></a>
                        @empty
                            <div class="dashboard-empty"><p>Rak ini masih kosong. Materi pertamamu bakal muncul di sini.</p><a class="text-link" href="{{ route('documents.create') }}">Yuk, tambah materi<span data-icon="arrow-right"></span></a></div>
                        @endforelse
                    </div>
                    {{ $recentMaterials->links() }}
                </section>
            </div>
            <aside class="dashboard-secondary" aria-label="Lanjut belajar">
                <section class="review-note">
                    <div class="note-top"><span data-icon="layers"></span><span>Sempatkan sebentar</span></div>
                    <div class="review-card-peek"><strong>{{ $reviewCard ? str($reviewCard->front)->limit(100) : 'Ulang satu konsep hari ini' }}</strong></div>
                    <h2>{{ $stats['due_flashcards'] ? $stats['due_flashcards'].' kartu buat diulang' : 'Belajar sedikit, tapi rutin' }}</h2>
                    <a class="button button-secondary" href="{{ $reviewUrl }}">{{ $reviewCard ? 'Ulangi kartu' : 'Pilih materi' }}<span data-icon="arrow-right"></span></a>
                    @if($reviewSource)<div class="note-bottom">{{ $reviewSource->title }}</div>@endif
                </section>
                <section class="activity-section" data-pagination-region="activity_page"><h2 id="activity-title">Belakangan ini</h2>
                    @forelse($activities as $activity)
                        @php($activityLabel = [
                            'upload_document' => 'Menambah materi', 'create_document_folder' => 'Membuat folder',
                            'generate_summary' => 'Membuat ringkasan', 'generate_folder_summary' => 'Meringkas folder',
                            'queue_summary' => 'Memproses ringkasan', 'queue_folder_summary' => 'Memproses ringkasan folder',
                            'generate_quiz' => 'Membuat latihan soal', 'generate_folder_quiz' => 'Membuat latihan folder',
                            'queue_quiz' => 'Memproses latihan soal', 'queue_folder_quiz' => 'Memproses latihan folder',
                            'generate_flashcards' => 'Membuat kartu belajar', 'generate_folder_flashcards' => 'Membuat kartu folder',
                            'queue_flashcards' => 'Memproses kartu belajar', 'queue_folder_flashcards' => 'Memproses kartu folder',
                            'chat_document' => 'Bertanya ke AI', 'submit_quiz_attempt' => 'Mengerjakan latihan',
                            'review_flashcard' => 'Mengulang kartu', 'save_study_note' => 'Menyimpan catatan',
                            'save_folder_note' => 'Menyimpan catatan folder',
                        ][$activity->action] ?? ucwords(str_replace('_', ' ', $activity->action)))
                        <div class="activity-item"><span class="activity-dot"></span><div><strong>{{ $activityLabel }}</strong><span>{{ $activity->created_at->diffForHumans() }}</span></div></div>
                    @empty
                        <p class="dashboard-empty">Aktivitasmu muncul setelah mulai belajar.</p>
                    @endforelse
                    {{ $activities->links() }}
                    @foreach($focusItems->where('active', true)->where('icon', '!=', 'repeat-2') as $focus)
                        <a class="activity-item" href="{{ $focus['url'] }}"><span data-icon="{{ $focus['icon'] === 'badge-check' ? 'check-square' : 'edit' }}"></span><div><strong>{{ $focus['title'] }}</strong><p>{{ $focus['meta'] }}</p></div></a>
                    @endforeach
                </section>
                <div class="quiet-help"><p>Baru pertama mampir?</p><a href="{{ route('student.guide') }}" class="text-link">Lihat cara pakainya<span data-icon="arrow-up-right"></span></a></div>
            </aside>
        </div>
    </section>
</x-app-layout>
