<x-app-layout title="Latihan Soal">
    @php
        $attemptAnswers = $latestAttempt?->answers ?? [];
        $source = $quiz->folder ?: $quiz->document;
    @endphp

    <a class="inline-flex items-center gap-1 text-sm font-semibold text-campus-700" href="{{ $quiz->folder ? route('folders.show', $quiz->folder) : route('documents.show', $quiz->document) }}">
        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $quiz->folder ? 'folder' : 'materi' }}
    </a>

    <section class="mt-4 rounded-lg border border-slate-200 bg-white p-5 shadow-panel">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-campus-700">Latihan Soal</p>
                <h1 class="mt-2 text-2xl font-semibold text-campus-900">{{ $quiz->title }}</h1>                <p class="mt-1 text-sm text-slate-500">{{ $source->title }} - {{ $quiz->questions->count() }} soal pilihan ganda</p></p>
            </div>
            @if($latestAttempt)
                <div class="rounded-lg bg-campus-50 px-4 py-3 text-campus-900">
                    <p class="text-xs font-semibold uppercase text-campus-700">Skor terakhir</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $latestAttempt->score }}/{{ $latestAttempt->total }}</p>
                    <p class="text-xs text-slate-500">{{ $latestAttempt->submitted_at?->format('d M Y H:i') }}</p>
                </div>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ route('quizzes.attempts.store', $quiz) }}" class="mt-6 space-y-4">
        @csrf
        @foreach($quiz->questions as $question)
            @php
                $options = $question->options ?? [];
                $correct = trim((string) $question->correct_answer);
                $correctOption = $correct;
                if (preg_match('/^[A-D]$/i', $correct)) {
                    $correctIndex = ord(strtoupper($correct)) - ord('A');
                    $correctOption = $options[$correctIndex] ?? $correct;
                }
                $selected = $attemptAnswers[$question->id] ?? null;
                $selectedClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $selected)));
                $correctClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $correctOption)));
            @endphp
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="font-semibold leading-7 text-slate-900">{{ $question->position }}. {{ $question->question }}</p>
                <div class="mt-4 grid gap-2">
                    @foreach($options as $option)
                        @php
                            $optionClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $option)));
                            $isSelected = $selected !== null && $selectedClean === $optionClean;
                            $isCorrect = $latestAttempt && $optionClean === $correctClean;
                            $isWrongSelection = $latestAttempt && $isSelected && ! $isCorrect;
                        @endphp
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition {{ $isCorrect ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : ($isWrongSelection ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-campus-50') }}">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" {{ $selected !== null && $isSelected ? 'checked' : '' }} required>
                            <span class="leading-6">{{ $option }}</span>
                        </label>
                    @endforeach
                </div>

                @if($latestAttempt)
                    <div class="mt-4 rounded-lg bg-campus-50 p-3 text-sm text-campus-900">
                        Jawaban benar: <strong>{{ $correctOption }}</strong>
                    </div>
                    @if($question->explanation)
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $question->explanation }}</p>
                    @endif
                @endif
            </section>
        @endforeach

        <div class="sticky bottom-4 z-10 rounded-lg border border-slate-200 bg-white/95 p-3 shadow-panel backdrop-blur">
            <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-campus-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-campus-900 sm:w-auto">
                <i data-lucide="check-circle" class="h-4 w-4"></i>
                {{ $latestAttempt ? 'Kerjakan ulang' : 'Koreksi jawaban' }}
            </button>
        </div>
    </form>
</x-app-layout>
