<x-app-layout title="Latihan Soal">
    @php
        $attemptAnswers = $latestAttempt?->answers ?? [];
        $essayGrades = $latestAttempt?->metadata['items'] ?? [];
        $source = $quiz->folder ?: $quiz->document;
        $isEssay = $quiz->question_type === 'essay';
    @endphp

    <div class="min-w-0 overflow-x-hidden" x-data="{ showQuestions: {{ ($latestAttempt && $room) ? 'false' : 'true' }} }">
        <section class="overflow-hidden rounded-[1.75rem] bg-gradient-to-r from-campus-50 to-white p-5 sm:p-8 shadow-sm border border-campus-100 relative">
            @if(isset($room) && $room)
                <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm border border-slate-100 transition-colors hover:bg-campus-100" href="{{ route('study-rooms.show', $room) }}">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke Room Belajar Bareng
                </a>
            @else
                <a class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-2 text-sm font-semibold text-campus-700 shadow-sm border border-slate-100 transition-colors hover:bg-campus-100" href="{{ $quiz->folder ? route('folders.show', $quiz->folder) : route('documents.show', $quiz->document) }}">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali ke {{ $quiz->folder ? 'folder' : 'materi' }}
                </a>
            @endif

            <div class="mt-5 flex min-w-0 flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <span class="inline-flex rounded-full bg-white px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-campus-700 shadow-sm ring-1 ring-slate-100">{{ $isEssay ? 'Latihan Esai' : 'Pilihan Ganda' }}</span>
                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-campus-950 sm:text-3xl">{{ $quiz->title }}</h1>
                    <div class="mt-3 flex min-w-0 flex-wrap gap-2 text-xs font-semibold">
                        <span class="max-w-full truncate rounded-full bg-white px-3 py-1.5 text-slate-700 shadow-sm border border-slate-100">{{ $source->title }}</span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-slate-700 shadow-sm border border-slate-100">{{ $quiz->questions->count() }} soal</span>
                    </div>
                </div>

                @if($latestAttempt)
                    <div class="w-fit shrink-0 rounded-[1.25rem] bg-white p-4 shadow-sm ring-1 ring-slate-100 text-center min-w-[100px]">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ $isEssay ? 'Skor AI' : 'Nilai Kamu' }}</p>
                        <p class="mt-1 text-3xl font-black text-campus-700">{{ $latestAttempt->score }}<span class="text-lg text-slate-400">/{{ $latestAttempt->total }}</span></p>
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

        @if($latestAttempt && isset($room) && $room)
            <!-- Leaderboard / Skor Akhir Sesi -->
            <section class="mt-5 rounded-[2rem] bg-white border border-slate-200/80 p-6 sm:p-8 shadow-md relative overflow-hidden">
                <div class="absolute right-0 top-0 transform translate-x-8 -translate-y-8 opacity-[0.03] pointer-events-none">
                    <i data-lucide="award" class="w-64 h-64 text-campus-700"></i>
                </div>

                <div class="text-center max-w-xl mx-auto mb-8">
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-100 px-3 py-1 text-xs font-bold text-amber-700 shadow-sm mb-3">
                        <i data-lucide="trophy" class="h-3.5 w-3.5"></i>
                        Sesi Belajar Bareng Selesai
                    </span>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight sm:text-3xl">Peringkat & Skor Akhir</h2>
                    <p class="text-sm text-slate-500 mt-2">Hebat! Kamu dan teman-temanmu telah menyelesaikan kuis ini. Berikut adalah daftar skor tertinggi di room ini.</p>
                </div>

                <!-- Leaderboard Grid list -->
                <div class="max-w-2xl mx-auto space-y-3">
                    @foreach($roomMembersAttempts as $rankIndex => $item)
                        @php
                            $member = $item['user'];
                            $attempt = $item['attempt'];
                            $isMe = $member->id === auth()->id();
                            $isHost = $member->id === $room->host_id;
                            $rank = $rankIndex + 1;
                        @endphp
                        <div class="relative flex items-center justify-between p-4 rounded-2xl border transition duration-200 {{ $isMe ? 'bg-campus-50/50 border-campus-200 shadow-sm ring-2 ring-campus-100' : 'bg-slate-50/40 border-slate-100 hover:bg-slate-50' }}">
                            <div class="flex items-center gap-4 min-w-0">
                                <!-- Rank Badge / Icon -->
                                <div class="shrink-0 flex items-center justify-center h-8 w-8 rounded-full font-black text-sm
                                    {{ $rank === 1 ? 'bg-amber-100 text-amber-700 ring-2 ring-amber-300 shadow-sm' : 
                                       ($rank === 2 ? 'bg-slate-200 text-slate-700 ring-2 ring-slate-300 shadow-sm' : 
                                       ($rank === 3 ? 'bg-orange-100 text-orange-700 ring-2 ring-orange-200 shadow-sm' : 'bg-slate-100 text-slate-500')) }}">
                                    @if($rank === 1)
                                        <i data-lucide="crown" class="h-4 w-4"></i>
                                    @else
                                        {{ $rank }}
                                    @endif
                                </div>

                                <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}&background={{ $isHost ? '1456a3' : '7c5cff' }}&color=fff" 
                                     class="h-10 w-10 rounded-full shadow-sm shrink-0" 
                                     alt="{{ $member->name }}">
                                <div class="min-w-0">
                                    <span class="block text-sm font-extrabold text-slate-700 truncate leading-tight flex items-center gap-1.5">
                                        {{ $member->name }}
                                        @if($isMe)
                                            <span class="text-[9px] font-bold bg-campus-600 text-white px-2 py-0.5 rounded-full">Saya</span>
                                        @endif
                                    </span>
                                    <span class="block text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-wider">
                                        {{ $isHost ? 'Host / Pembuat' : 'Peserta' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="text-right shrink-0">
                                @if($attempt)
                                    <span class="text-base font-black text-campus-700 bg-white border border-slate-200/80 px-3.5 py-1.5 rounded-2xl shadow-sm">
                                        {{ $attempt->score }}<span class="text-xs text-slate-400 font-semibold">/{{ $attempt->total }}</span>
                                    </span>
                                    <span class="block text-[10px] text-slate-400 mt-1.5 font-medium">{{ $attempt->submitted_at->diffForHumans() }}</span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400 italic">Belum mengerjakan</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Action buttons below leaderboard -->
                <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3 border-t border-slate-100 pt-6">
                    <a href="{{ route('study-rooms.show', $room) }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-slate-900 hover:bg-slate-800 text-[14px] font-bold text-white px-6 shadow-md shadow-black/10 transition-all hover:-translate-y-0.5 w-full sm:w-auto">
                        <i data-lucide="arrow-left" class="h-4.5 w-4.5"></i>
                        Kembali ke Room Belajar
                    </a>
                    
                    <button type="button" @click="showQuestions = !showQuestions" class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-campus-50 hover:bg-campus-100 text-[14px] font-bold text-campus-700 px-6 border border-campus-100 shadow-sm transition-all hover:-translate-y-0.5 w-full sm:w-auto">
                        <i data-lucide="list-checks" class="h-4.5 w-4.5"></i>
                        <span x-text="showQuestions ? 'Sembunyikan Pembahasan Soal' : 'Lihat Pembahasan & Jawaban Saya'"></span>
                    </button>
                </div>
            </section>
        @endif

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
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">Layanan AI belum berhasil merespons. Kembali ke room belajar atau materi untuk mencoba lagi.</p>
                @if(isset($room) && $room)
                    <a href="{{ route('study-rooms.show', $room) }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-full bg-campus-700 px-5 text-sm font-semibold text-white">Kembali ke Room Belajar</a>
                @else
                    <a href="{{ $quiz->folder ? route('folders.show', $quiz->folder) : route('documents.show', $quiz->document) }}" class="mt-4 inline-flex h-11 items-center justify-center rounded-full bg-campus-700 px-5 text-sm font-semibold text-white">Kembali ke materi</a>
                @endif
            </section>
        @else

        <form x-show="showQuestions" method="POST" action="{{ route('quizzes.attempts.store', $quiz) }}{{ request()->has('room') ? '?room=' . request('room') : '' }}" class="mt-5 space-y-4">
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

                <section class="min-w-0 overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm border border-slate-100 sm:p-7" x-data="{detail:false, currentSelection: '{{ $selected ? addslashes($selected) : '' }}'}">
                    <div class="flex min-w-0 items-start gap-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-campus-50 text-[15px] font-bold text-campus-700 ring-1 ring-campus-100 shadow-sm">{{ $question->position }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="break-words text-[15px] font-semibold leading-relaxed text-slate-900">{{ $question->question }}</p>
                            @if($isEssay && !$latestAttempt)
                                <p class="mt-2 flex items-center gap-1.5 text-[13px] font-medium text-slate-500">
                                    <i data-lucide="info" class="h-4 w-4"></i> Jawab singkat sesuai pemahaman materi.
                                </p>
                            @endif
                        </div>
                        @if($isEssay && $grade)
                            <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-bold ring-1 {{ (int)($grade['score']??0) >= 70 ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                Skor: {{ (int) ($grade['score'] ?? 0) }}
                            </span>
                        @endif
                    </div>

                    @if($isEssay)
                        <div class="mt-5 rounded-[1.25rem] bg-slate-50 p-4 ring-1 ring-slate-100">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Jawaban Kamu</label>
                            <textarea name="answers[{{ $question->id }}]" rows="{{ $latestAttempt ? 2 : 3 }}" required placeholder="Ketik jawaban di sini..." class="w-full resize-y rounded-xl border-slate-200 bg-white px-4 py-3 text-[14px] leading-relaxed shadow-sm transition-colors focus:border-campus-500 focus:ring-campus-500 placeholder:text-slate-400" {{ $latestAttempt ? 'readonly' : '' }}>{{ $selected }}</textarea>
                        </div>
                    @else
                        <div class="mt-6 grid gap-3">
                            @foreach($options as $option)
                                @php
                                    $optionClean = strtolower(trim(preg_replace('/^[A-D][\.\)]\s*/i', '', (string) $option)));
                                    $isSelected = $selected !== null && $selectedClean === $optionClean;
                                    $isCorrect = $latestAttempt && $optionClean === $correctClean;
                                    $isWrongSelection = $latestAttempt && $isSelected && ! $isCorrect;
                                @endphp
                                
                                @if($latestAttempt)
                                    {{-- Graded View --}}
                                    <label class="relative flex min-w-0 items-start gap-4 rounded-[1.25rem] p-4 text-[14px] leading-relaxed transition-all ring-1 {{ $isCorrect ? 'bg-emerald-50 text-emerald-900 ring-emerald-200 shadow-sm' : ($isWrongSelection ? 'bg-rose-50 text-rose-900 ring-rose-200 shadow-sm' : 'bg-white text-slate-500 ring-slate-100 opacity-60') }}">
                                        <div class="mt-0.5 shrink-0">
                                            @if($isCorrect)
                                                <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-600"></i>
                                            @elseif($isWrongSelection)
                                                <i data-lucide="x-circle" class="h-5 w-5 text-rose-600"></i>
                                            @else
                                                <div class="h-5 w-5 rounded-full border-2 border-slate-200"></div>
                                            @endif
                                        </div>
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" {{ $isSelected ? 'checked' : '' }} disabled class="sr-only">
                                        <span class="min-w-0 break-words {{ $isCorrect || $isWrongSelection ? 'font-medium' : '' }}">{{ $option }}</span>
                                    </label>
                                @else
                                    {{-- Interactive View --}}
                                    <label class="relative flex min-w-0 cursor-pointer items-start gap-4 rounded-[1.25rem] p-4 text-[14px] leading-relaxed transition-all hover:bg-slate-50 ring-1"
                                        x-bind:class="currentSelection === '{{ addslashes($option) }}' ? 'bg-campus-50 text-campus-900 ring-campus-500 shadow-md scale-[1.01]' : 'bg-white text-slate-700 ring-slate-200'">
                                        <div class="mt-0.5 shrink-0 flex items-center justify-center">
                                            <div class="flex h-5 w-5 items-center justify-center rounded-full border-2 transition-colors" x-bind:class="currentSelection === '{{ addslashes($option) }}' ? 'border-campus-600' : 'border-slate-300'">
                                                <div class="h-2.5 w-2.5 rounded-full bg-campus-600 transition-transform" x-bind:class="currentSelection === '{{ addslashes($option) }}' ? 'scale-100' : 'scale-0'"></div>
                                            </div>
                                        </div>
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" required class="sr-only" x-model="currentSelection">
                                        <span class="min-w-0 break-words" x-bind:class="currentSelection === '{{ addslashes($option) }}' ? 'font-medium' : ''">{{ $option }}</span>
                                    </label>
                                @endif
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
                            <div class="mt-5 rounded-[1.25rem] bg-emerald-50 p-4 ring-1 ring-emerald-200">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 mb-1">Kunci Jawaban</p>
                                <p class="font-medium text-emerald-900">{{ $correctOption }}</p>
                            </div>

                            @if($question->explanation)
                                <div class="mt-3 rounded-[1.25rem] bg-slate-50 p-4 text-[14px] leading-relaxed text-slate-700 ring-1 ring-slate-100">
                                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Pembahasan</p>
                                    <p class="break-words">{{ $question->explanation }}</p>
                                </div>
                            @endif
                        @endif
                    @endif
                </section>
            @endforeach

            <div class="sticky bottom-6 z-10 flex justify-center mt-8">
                @if($latestAttempt)
                    @if(isset($room) && $room)
                        <div class="flex flex-wrap gap-3 justify-center">
                            <a href="{{ route('study-rooms.show', $room) }}" class="inline-flex h-14 items-center justify-center gap-2 rounded-full bg-slate-900 px-8 text-[15px] font-bold text-white shadow-xl shadow-black/10 transition-all hover:-translate-y-1 hover:bg-slate-800 w-full sm:w-auto">
                                <i data-lucide="arrow-left" class="h-5 w-5"></i>
                                Kembali ke Room Belajar
                            </a>
                            <button type="button" @click="showQuestions = false; window.scrollTo({top: 0, behavior: 'smooth'})" class="inline-flex h-14 items-center justify-center gap-2 rounded-full bg-campus-50 border border-campus-100 px-8 text-[15px] font-bold text-campus-700 shadow-xl shadow-campus-700/5 transition-all hover:-translate-y-1 hover:bg-campus-100 w-full sm:w-auto">
                                <i data-lucide="award" class="h-5 w-5"></i>
                                Kembali ke Skor Akhir
                            </button>
                        </div>
                    @else
                        <a href="{{ route('quizzes.show', $quiz) }}?retake=true" class="inline-flex h-14 items-center justify-center gap-2 rounded-full bg-campus-700 px-8 text-[15px] font-bold text-white shadow-xl shadow-campus-700/30 ring-1 ring-white/20 transition-all hover:-translate-y-1 hover:bg-campus-800 hover:shadow-campus-700/40 w-full sm:w-auto">
                            <i data-lucide="refresh-cw" class="h-5 w-5"></i>
                            Kerjakan Ulang
                        </a>
                    @endif
                @else
                    <button class="inline-flex h-14 items-center justify-center gap-2 rounded-full bg-campus-700 px-8 text-[15px] font-bold text-white shadow-xl shadow-campus-700/30 ring-1 ring-white/20 transition-all hover:-translate-y-1 hover:bg-campus-800 hover:shadow-campus-700/40 w-full sm:w-auto">
                        <i data-lucide="check-circle" class="h-5 w-5"></i>
                        {{ $isEssay ? 'Simpan Jawaban Esai' : 'Koreksi Jawaban Saya' }}
                    </button>
                @endif
            </div>
        </form>
        @endif
    </div>
</x-app-layout>
