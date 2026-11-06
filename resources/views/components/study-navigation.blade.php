@props(['source', 'active' => '', 'room' => null, 'selectedDocumentIds' => null])

@if($source && !$room && (int) $source->user_id === (int) auth()->id())
    @php
        $isFolder = $source instanceof \App\Models\DocumentFolder;
        $materialUrl = route($isFolder ? 'folders.show' : 'documents.show', $source);
        $normalizeScope = fn ($ids) => collect($ids ?? [])->map(fn ($id) => (string) $id)->unique()->sort()->values()->all();
        $selectedScope = $isFolder ? $normalizeScope($selectedDocumentIds) : [];
        if ($selectedScope) {
            $materialUrl .= '?'.http_build_query(['document_ids' => $selectedScope]);
        }
        $latestInScope = function ($query) use ($isFolder, $normalizeScope, $selectedScope) {
            $query->where('user_id', auth()->id())->latest();
            if (!$isFolder) {
                return $query->first();
            }
            // Folder results can represent different selections of the same files.
            return $query->get(['id', 'public_id', 'selected_document_ids'])->first(
                fn ($result) => $normalizeScope($result->selected_document_ids) === $selectedScope
            );
        };
        $latestSummary = $latestInScope($source->summaries());
        $latestChat = $latestInScope($source->chatSessions());
        $latestQuiz = $latestInScope($source->quizzes()->whereNull('study_room_id'));
        $hasCards = $source->flashcards()->where('user_id', auth()->id())->exists();
        $learningLinks = [
            ['key' => 'summary', 'label' => 'Ringkasan', 'hint' => 'Baca inti materi', 'icon' => 'notebook-tabs', 'url' => $latestSummary ? route('summaries.show', $latestSummary) : $materialUrl.'#learn-summary'],
            ['key' => 'chat', 'label' => 'Tanya AI', 'hint' => 'Bahas yang belum paham', 'icon' => 'messages-square', 'url' => $latestChat ? route('chat.show', $latestChat) : $materialUrl.'#learn-chat'],
            ['key' => 'quiz', 'label' => 'Latihan soal', 'hint' => 'Coba pemahamanmu', 'icon' => 'list-checks', 'url' => $latestQuiz ? route('quizzes.show', $latestQuiz) : $materialUrl.'#learn-quiz'],
            ['key' => 'flashcard', 'label' => 'Kartu belajar', 'hint' => $selectedScope && $hasCards ? 'Semua kartu dalam folder' : 'Ingat sedikit demi sedikit', 'icon' => 'copy-check', 'url' => $hasCards ? route($isFolder ? 'folders.flashcards.index' : 'flashcards.index', $source) : $materialUrl.'#learn-flashcard'],
        ];
    @endphp
    <nav class="live-study-navigation" aria-label="Cara belajar dari materi ini">
        @foreach($learningLinks as $learningLink)
            <a href="{{ $active === $learningLink['key'] ? request()->fullUrl() : $learningLink['url'] }}"
               class="live-study-link {{ $active === $learningLink['key'] ? 'is-active' : '' }}"
               @if($active === $learningLink['key']) aria-current="page" @endif>
                <i data-lucide="{{ $learningLink['icon'] }}" aria-hidden="true"></i>
                <span><strong>{{ $learningLink['label'] }}</strong><small>{{ $learningLink['hint'] }}</small></span>
            </a>
        @endforeach
    </nav>
@endif
