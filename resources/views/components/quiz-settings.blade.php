@props(['id' => 'quiz'])
<dialog x-ref="quizSettings" class="quiz-settings-dialog" aria-labelledby="{{ $id }}-settings-title"
    :aria-busy="loading"
    @cancel="if (loading) $event.preventDefault()"
    @click="if (!loading && $event.target === $el) { const bounds = $el.getBoundingClientRect(); if ($event.clientX < bounds.left || $event.clientX > bounds.right || $event.clientY < bounds.top || $event.clientY > bounds.bottom) $el.close(); }">
    <div class="quiz-settings-heading">
        <div>
            <h2 id="{{ $id }}-settings-title">Atur soal</h2>
            <p>Pilih jenis dan jumlah soal yang mau kamu kerjakan</p>
        </div>
        <button type="button" class="quiz-settings-close" aria-label="Tutup pengaturan soal"
            :disabled="loading" @click="$refs.quizSettings.close()">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    </div>

    <fieldset class="quiz-settings-types" :inert="loading">
        <legend>Jenis soal</legend>
        <div>
            <label><input type="radio" name="question_type" value="multiple_choice" x-model="type" checked autofocus><span>Pilihan ganda</span></label>
            <label><input type="radio" name="question_type" value="essay" x-model="type"><span>Esai</span></label>
        </div>
    </fieldset>

    <div class="quiz-settings-count" :inert="loading">
        <div><label for="{{ $id }}-question-count">Jumlah soal</label><p>1 sampai 30 soal</p></div>
        <div class="quiz-settings-stepper">
            <button type="button" aria-label="Kurangi jumlah soal" :disabled="count <= 1"
                @click="count = Math.max(1, Math.min(30, Number(count) || 1) - 1)"><i data-lucide="minus" aria-hidden="true"></i></button>
            <input id="{{ $id }}-question-count" type="number" name="question_count" min="1" max="30" step="1" required x-model.number="count" inputmode="numeric" aria-describedby="{{ $id }}-count-hint">
            <button type="button" aria-label="Tambah jumlah soal" :disabled="count >= 30"
                @click="count = Math.min(30, Math.max(1, Number(count) || 1) + 1)"><i data-lucide="plus" aria-hidden="true"></i></button>
        </div>
    </div>
    <p id="{{ $id }}-count-hint" class="sr-only">Masukkan bilangan bulat antara 1 dan 30</p>

    <div class="quiz-settings-footer">
        <button type="button" class="button button-secondary" :disabled="loading" @click="$refs.quizSettings.close()">Batal</button>
        <button type="submit" class="button button-primary" :disabled="aiBusy || loading">
            <span x-text="loading ? 'Membuat soal...' : 'Buat soal'"></span>
        </button>
    </div>
</dialog>
