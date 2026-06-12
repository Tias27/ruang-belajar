<x-app-layout title="Latihan Soal">
    @php
        $attemptAnswers = $latestAttempt?->answers ?? [];
        $essayGrades = $latestAttempt?->metadata['items'] ?? [];
        $source = $quiz->folder ?: $quiz->document;
        $isEssay = $quiz->question_type === 'essay';
    @endphp

    <div class="min-w-0 overflow-x-hidden">
        <section class="overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm">
            <a class="inline-flex items-center gap-1 rounded-full bg-campus-50 px-3 py-2 text-sm font-semibold text-campus-700 hover:bg-campus-100" href="{{ $quiz->folder ? route('folders.show', $quiz->folder) : route('documents.show', $quiz->document) }}">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $quiz->folder ? 'folder' : 'materi' }}
            </a>

            <div class="mt-5 flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-campus-700">{{ $isEssay ? 'Latihan esai' : 'Latihan pilihan ganda' }}</p>
                    <h1 class="mt-1 break-words text-xl font-semibold leading-tight text-campus-900 sm:text-2xl">{{ $quiz->title }}</h1>
                    <div class="mt-3 flex min-w-0 flex-wrap gap-2 text-xs font-semibold">
                        <span class="max-w-full truncate rounded-full bg-slate-50 px-3 py-1.5 text-slate-600">{{ $source->title }}</span>
                        <span class="rounded-full bg-campus-50 px-3 py-1.5 text-campus-700">{{ $quiz->questions->count() }} soal</span>
                        <span class="rounded-full bg-accent-50 px-3 py-1.5 text-accent-700">{{ $isEssay ? 'Esai' : 'Pilihan ganda' }}</span>
                    </div>
                </div>

                @if($latestAttempt)
                    <div class="w-fit shrink-0 rounded-[1.1rem] bg-campus-50 px-3 py-2 text-campus-900">
                        <p class="text-xs font-semibold text-campus-700">{{ $isEssay ? 'Skor AI' : 'Skor' }}</p>
                        <p class="text-lg font-semibold">{{ $latestAttempt->score.'/'.$latestAttempt->total }}</p>
                    </div>
                @endif
            </div>

            @if($isEssay)
                <div class="mt-4 flex min-w-0 items-start gap-3 rounded-[1.1rem] bg-slate-50 p-3 text-sm text-slate-600">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-white text-campus-700 shadow-sm">
                        <i data-lucide="pen-line" class="h-4 w-4"></i>
                    </span>
                    <p class="min-w-0 leading-6">Jawaban esai dinilai AI dengan skor parsial. Semakin lengkap dan sesuai materi, semakin tinggi skornya.</p>
                </div>
            @endif
        </section>

        @if($quiz->status === 'processing')
            <section class="mt-5 rounded-[1.5rem] bg-white p-5 text-center shadow-sm">
                <script>
                    setTimeout(() => window.location.reload(), 5000);
                </script>
                <span class="mx-auto block h-11 w-11 animate-spin rounded-full border-2 border-campus-100 border-t-campus-700"></span>
                <h2 class="mt-4 text-lg font-semibold text-campus-900">Soal sedang dibuat</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">AI sedang membaca materi dan menyusun soal. Kamu boleh tinggalkan halaman ini, lalu buka lagi beberapa saat lagi.</p>
                <a href="{{ request()->fullUrl() }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-full bg-campus-700 px-5 text-sm font-semibold text-white">Cek lagi</a>
            </section>
        @elseif($quiz->status === 'failed')
            <section class="mt-5 rounded-[1.5rem] bg-white p-5 text-center shadow-sm">
                <span class="mx-auto grid h-11 w-11 place-items-center rounded-full bg-rose-50 text-rose-700">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                </span>
                <h2 class="mt-4 text-lg font-semibold text-campus-900">Soal belum berhasil dibuat</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">Layanan AI belum berhasil merespons. Kembali ke materi lalu coba buat soal lagi.</p>
                <a href="{{ $quiz->folder ? route('folders.show', $quiz->folder) : route('documents.show', $quiz->document) }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-full bg-campus-700 px-5 text-sm font-semibold text-white">Kembali ke materi</a>
            </section>
        @else

        <form method="POST" action="{{ route('quizzes.attempts.store', $quiz) }}" class="mt-5 space-y-4">
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
                    $grade = $essayGrades[$question->id] ?? null;
                    $selectedClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $selected)));
                    $correctClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $correctOption)));
                @endphp

                <section class="min-w-0 overflow-hidden rounded-[1.35rem] bg-white p-4 shadow-sm sm:p-5" x-data="{detail:false}">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-campus-50 text-sm font-semibold text-campus-700">{{ $question->position }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="break-words text-sm font-semibold leading-6 text-slate-900">{{ $question->question }}</p>
                            @if($isEssay)
                                <p class="mt-1 text-xs leading-5 text-slate-500">Jawab singkat dan sesuai materi.</p>
                            @endif
                        </div>
                        @if($isEssay && $grade)
                            <span class="shrink-0 rounded-full bg-campus-50 px-2.5 py-1 text-xs font-semibold text-campus-700">{{ (int) ($grade['score'] ?? 0) }}/100</span>
                        @endif
                    </div>

                    @if($isEssay)
                        <div class="mt-3 rounded-[1rem] bg-slate-50 p-3">
                            <label class="block text-xs font-semibold uppercase text-slate-500">Jawaban kamu</label>
                            <textarea name="answers[{{ $question->id }}]" rows="{{ $latestAttempt ? 2 : 3 }}" required placeholder="Tulis jawaban esai kamu di sini..." class="mt-2 resize-y rounded-[.9rem] border-slate-200 bg-white px-3 py-2 text-sm leading-6 placeholder:text-slate-400">{{ $selected }}</textarea>
                        </div>
                    @else
                        <div class="mt-4 grid gap-2">
                            @foreach($options as $option)
                                @php
                                    $optionClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $option)));
                                    $isSelected = $selected !== null && $selectedClean === $optionClean;
                                    $isCorrect = $latestAttempt && $optionClean === $correctClean;
                                    $isWrongSelection = $latestAttempt && $isSelected && ! $isCorrect;
                                @endphp
                                <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-[1.1rem] p-3 text-sm transition {{ $isCorrect ? 'bg-emerald-50 text-emerald-800' : ($isWrongSelection ? 'bg-rose-50 text-rose-800' : 'bg-slate-50 text-slate-700 hover:bg-campus-50') }}">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" {{ $selected !== null && $isSelected ? 'checked' : '' }} required class="mt-1 shrink-0">
                                    <span class="min-w-0 break-words leading-6">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @if($latestAttempt)
                        @if($isEssay)
                            <button type="button" x-on:click="detail=!detail" class="mt-3 inline-flex h-9 items-center gap-2 rounded-full bg-campus-50 px-3 text-xs font-semibold text-campus-700 hover:bg-campus-100">
                                <i data-lucide="message-square-text" class="h-3.5 w-3.5"></i>
                                <span x-text="detail ? 'Tutup koreksi' : 'Lihat koreksi'"></span>
                            </button>
                            <div x-show="detail" x-cloak class="mt-3 space-y-2">
                                <div class="rounded-[1rem] bg-campus-50 p-3 text-sm leading-6 text-campus-900">
                                    <p class="mb-1 text-xs font-semibold uppercase text-campus-700">Jawaban yang seharusnya</p>
                                    <p class="break-words">{{ $grade['suggested_answer'] ?? $correctOption }}</p>
                                </div>
                                @if(! empty($grade['feedback'] ?? null))
                                    <div class="rounded-[1rem] bg-slate-50 p-3 text-sm leading-6 text-slate-600">
                                        <p class="mb-1 text-xs font-semibold uppercase text-slate-400">Feedback AI</p>
                                        <p class="break-words">{{ $grade['feedback'] }}</p>
                                    </div>
                                @endif
                                @if($question->explanation)
                                    <div class="rounded-[1rem] bg-slate-50 p-3 text-sm leading-6 text-slate-600">
                                        <p class="mb-1 text-xs font-semibold uppercase text-slate-400">Pembahasan</p>
                                        <p class="break-words">{{ $question->explanation }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="mt-4 rounded-[1.1rem] bg-campus-50 p-3 text-sm text-campus-900">
                                Jawaban benar: <strong>{{ $correctOption }}</strong>
                            </div>

                            @if($question->explanation)
                                <div class="mt-3 rounded-[1.1rem] bg-slate-50 p-3 text-sm leading-7 text-slate-600">
                                    <p class="mb-1 text-xs font-semibold uppercase text-slate-400">Pembahasan</p>
                                    <p class="break-words">{{ $question->explanation }}</p>
                                </div>
                            @endif
                        @endif
                    @endif
                </section>
            @endforeach

            <div class="sticky bottom-4 z-10 rounded-[1.25rem] bg-white/95 p-3 shadow-panel backdrop-blur">
                <button class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-full bg-campus-700 px-5 text-sm font-semibold text-white shadow-sm hover:bg-campus-900 sm:w-auto">
                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                    {{ $latestAttempt ? 'Kerjakan ulang' : ($isEssay ? 'Simpan jawaban' : 'Koreksi jawaban') }}
                </button>
            </div>
        </form>
        @endif
    </div>
</x-app-layout>
