<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearningSourceService
{
    public function fallbackQuiz(Document|DocumentFolder $source, int $limit = 10, string $questionType = 'multiple_choice'): array
    {
        $snippets = $this->quizCandidates($source, $limit);

        return collect($snippets)
            ->take($limit)
            ->values()
            ->map(function (array $snippet, int $index) use ($questionType) {
                $answer = $this->answerText($snippet['snippet'], 240);

                if ($questionType === 'essay') {
                    return [
                        'question' => 'Jelaskan poin penting dari materi '.$this->topicLabel($snippet, $index).'.',
                        'options' => [],
                        'correct_answer' => $answer,
                        'explanation' => 'Jawaban dapat dibandingkan dengan inti materi dari bagian '.$snippet['title'].'.',
                    ];
                }

                return [
                    'question' => 'Apa inti materi dari '.$this->topicLabel($snippet, $index).'?',
                    'options' => [
                        'A. '.$answer,
                        'B. Materi tersebut hanya membahas identitas dokumen tanpa konsep utama.',
                        'C. Materi tersebut tidak memiliki hubungan dengan pembahasan di dokumen.',
                        'D. Materi tersebut berisi kesimpulan yang berlawanan dengan isi dokumen.',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Jawaban A benar karena sesuai dengan potongan materi yang terbaca dari dokumen.',
                ];
            })
            ->all();
    }

    public function fallbackFlashcards(Document|DocumentFolder $source, int $limit = 10): array
    {
        $cards = $this->flashcardCandidates($source, $limit);

        return collect($cards)
            ->take($limit)
            ->values()
            ->map(fn (array $card, int $index) => [
                'front' => 'Jelaskan poin penting dari '.$this->topicLabel($card, $index).'.',
                'back' => $this->formatFlashcardBack($card['snippet']),
            ])
            ->all();
    }

    private function quizCandidates(Document|DocumentFolder $source, int $limit): array
    {
        $documents = $source instanceof DocumentFolder
            ? $source->documents()->oldest()->get(['id', 'title', 'extracted_text'])
            : collect([$source]);

        $candidates = $documents
            ->flatMap(function (Document $document) {
                return collect($this->studyUnits($document->extracted_text ?: ''))
                    ->map(fn (string $unit) => [
                        'document_id' => $document->id,
                        'title' => $document->title,
                        'snippet' => $unit,
                        'score' => $this->quizUnitScore($unit),
                    ]);
            })
            ->filter(fn (array $item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->unique(fn (array $item) => Str::lower($this->completeLimit($item['snippet'], 90)))
            ->take($limit)
            ->map(fn (array $item) => [
                'document_id' => $item['document_id'],
                'title' => $item['title'],
                'snippet' => $item['snippet'],
            ])
            ->values()
            ->all();

        if ($candidates !== []) {
            return $candidates;
        }

        return $this->snippetsFor($source, 'konsep definisi karakteristik manfaat langkah tujuan masalah solusi metode proses', $limit);
    }

    public function fallbackAnswer(Document|DocumentFolder $source, string $question, string $reason): string
    {
        $snippets = $this->snippetsFor($source, $question, 3);
        $safeReason = $this->safeAiFailureReason($reason);

        if ($snippets === []) {
            return "{$safeReason}\n\nMateri ini juga belum punya teks terbaca yang cukup untuk dibuat jawaban lokal. Cek halaman detail dokumen untuk memastikan PDF/DOCX/PPTX berhasil terbaca.";
        }

        $points = collect($snippets)
            ->map(fn (array $snippet, int $index) => ($index + 1).". {$snippet['snippet']}")
            ->implode("\n\n");

        return "{$safeReason}\n\nJawaban sementara dari potongan materi yang paling relevan:\n\n{$points}\n\nIni belum secerdas jawaban AI penuh, tapi bisa dipakai untuk menangkap bagian materi yang berkaitan dengan pertanyaanmu.";
    }

    public function snippetsFor(Document|DocumentFolder $source, string $query, int $limit = 3): array
    {
        $keywords = $this->keywords($query);
        $documents = $source instanceof DocumentFolder
            ? $source->documents()->oldest()->get(['id', 'title', 'extracted_text'])
            : collect([$source]);

        $snippets = $documents
            ->flatMap(fn (Document $document) => $this->documentSnippets($document, $keywords))
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $snippet) => [
                'document_id' => $snippet['document_id'],
                'title' => $snippet['title'],
                'snippet' => $snippet['snippet'],
            ])
            ->values()
            ->all();

        if ($snippets !== []) {
            return $snippets;
        }

        return $documents
            ->filter(fn (Document $document) => filled($document->extracted_text))
            ->take($limit)
            ->map(fn (Document $document) => [
                'document_id' => $document->id,
                'title' => $document->title,
                'snippet' => $this->completeLimit($this->clean($document->extracted_text), 900),
            ])
            ->values()
            ->all();
    }

    private function documentSnippets(Document $document, array $keywords): Collection
    {
        if (! filled($document->extracted_text)) {
            return collect();
        }

        return collect($this->chunks($document->extracted_text))
            ->map(function (string $chunk) use ($document, $keywords) {
                $clean = $this->clean($chunk);

                return [
                    'document_id' => $document->id,
                    'title' => $document->title,
                    'snippet' => $this->completeLimit($clean, 900),
                    'score' => $this->score($clean, $keywords),
                ];
            })
            ->filter(fn (array $snippet) => $snippet['score'] > 0);
    }

    private function flashcardCandidates(Document|DocumentFolder $source, int $limit): array
    {
        $documents = $source instanceof DocumentFolder
            ? $source->documents()->oldest()->get(['id', 'title', 'extracted_text'])
            : collect([$source]);

        $candidates = $documents
            ->flatMap(function (Document $document) {
                return collect($this->studyUnits($document->extracted_text ?: ''))
                    ->map(fn (string $unit) => [
                        'document_id' => $document->id,
                        'title' => $document->title,
                        'snippet' => $unit,
                        'score' => $this->studyUnitScore($unit),
                    ]);
            })
            ->filter(fn (array $item) => mb_strlen($item['snippet']) >= 35)
            ->sortByDesc('score')
            ->unique(fn (array $item) => Str::lower($this->completeLimit($item['snippet'], 90)))
            ->take($limit)
            ->map(fn (array $item) => [
                'document_id' => $item['document_id'],
                'title' => $item['title'],
                'snippet' => $item['snippet'],
            ])
            ->values()
            ->all();

        if ($candidates !== []) {
            return $candidates;
        }

        return $this->snippetsFor($source, 'definisi konsep ciri manfaat langkah contoh', $limit);
    }

    private function studyUnits(string $text): array
    {
        $text = $this->clean($text);
        if ($text === '') {
            return [];
        }

        $text = preg_replace('/\s+(?=\d+\.\s+)/u', "\n", $text) ?: $text;
        $text = preg_replace('/\s+(?=(?:[A-Z][\pL\s]{2,70}:))/u', "\n", $text) ?: $text;
        $parts = preg_split('/\n+|(?<=\.)\s+(?=[A-Z0-9])/u', $text) ?: [];

        return collect($parts)
            ->map(fn (string $part) => $this->completeLimit($part, 520))
            ->map(fn (string $part) => trim($part, " \t\n\r\0\x0B:;,.-"))
            ->filter(fn (string $part) => mb_strlen($part) >= 35)
            ->reject(fn (string $part) => $this->isAdministrativeText($part))
            ->reject(fn (string $part) => preg_match('/^(contoh|rumus|keterangan|daftar isi|referensi)\b/iu', $part))
            ->values()
            ->all();
    }

    private function studyUnitScore(string $unit): int
    {
        $score = 0;
        $lower = Str::lower($unit);

        foreach (['adalah', 'merupakan', 'berfungsi', 'tujuan', 'manfaat', 'karakteristik', 'ciri', 'langkah', 'jenis', 'kelebihan', 'kekurangan'] as $marker) {
            if (str_contains($lower, $marker)) {
                $score += 3;
            }
        }

        if (preg_match_all('/\b\d+\.\s*/u', $unit) >= 2) {
            $score += 4;
        }

        return $score + min(5, intdiv(mb_strlen($unit), 120));
    }

    private function quizUnitScore(string $unit): int
    {
        $unit = trim($unit);
        $lower = Str::lower($unit);

        if ($this->isAdministrativeText($unit)) {
            return 0;
        }

        $score = $this->studyUnitScore($unit);

        foreach (['masalah', 'solusi', 'metode', 'sistem', 'proses', 'tahap', 'analisis', 'desain', 'implementasi', 'pengujian'] as $marker) {
            if (str_contains($lower, $marker)) {
                $score += 2;
            }
        }

        if (str_word_count($unit) < 8 || mb_strlen($unit) < 50) {
            $score -= 4;
        }

        return max(0, $score);
    }

    private function isAdministrativeText(string $text): bool
    {
        $lower = Str::lower($text);

        if (preg_match('/\b(laporan akhir|tugas mata kuliah|disusun oleh|program studi|fakultas|universitas|dosen pengampu|kelompok|daftar isi|kata pengantar|referensi|bibliografi)\b/iu', $text)) {
            return true;
        }

        $letters = preg_replace('/[^\pL]/u', '', $text) ?: '';
        if ($letters !== '') {
            $upper = preg_replace('/[^\p{Lu}]/u', '', $text) ?: '';
            if (mb_strlen($upper) / max(1, mb_strlen($letters)) > 0.65) {
                return true;
            }
        }

        return str_contains($lower, 'rekayasa perangkat lunak sistem informasi manajemen order')
            && ! str_contains($lower, 'masalah')
            && ! str_contains($lower, 'metode')
            && ! str_contains($lower, 'solusi');
    }

    private function chunks(string $text): array
    {
        $clean = $this->clean($text);
        $paragraphs = preg_split('/(?<=\.)\s+|\n{2,}|(?=\b\d+\.\s+)/', $clean) ?: [];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (strlen($current.' '.$paragraph) > 900 && $current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            $current = trim($current.' '.$paragraph);
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private function keywords(string $query): array
    {
        preg_match_all('/[\pL\pN]{4,}/u', Str::lower($query), $matches);

        return collect($matches[0] ?? [])
            ->reject(fn (string $word) => in_array($word, ['yang', 'dengan', 'untuk', 'dari', 'pada', 'atau', 'adalah', 'dalam', 'materi', 'jelaskan'], true))
            ->unique()
            ->take(18)
            ->values()
            ->all();
    }

    private function score(string $text, array $keywords): int
    {
        $haystack = Str::lower($text);

        return collect($keywords)
            ->sum(fn (string $keyword) => Str::contains($haystack, $keyword) ? 1 : 0);
    }

    private function clean(string $text): string
    {
        $text = str_replace([
            'â€¢', 'â†’', 'â‡’', 'â‰¥', 'â‰¤', 'âˆ’', 'Ã—', 'Î£', 'â€œ', 'â€', 'â€˜', 'â€™', 'â€”', 'â€“', 'ä¸‰å±‚',
        ], [
            '-', '->', '->', '>=', '<=', '-', 'x', 'Sigma', '"', '"', "'", "'", '-', '-', '',
        ], $text);
        $text = str_replace(['❖', '▪', '●', '•', '◦', '✓', '✔'], ' ', $text);
        $text = preg_replace('/([a-z])([A-Z])/u', '$1 $2', $text) ?: $text;
        $text = preg_replace('/(\d+)\s*([A-Z][\pL]+)/u', '$1 $2', $text) ?: $text;
        $text = preg_replace('/(\d+)([a-z]+)/u', '$1 $2', $text) ?: $text;
        $text = preg_replace('/([,:;])(?=\S)/u', '$1 ', $text) ?: $text;
        $text = preg_replace('/([A-Za-z])\(/u', '$1 (', $text) ?: $text;
        $text = preg_replace('/\b(Langkah)\s*(\d+)/iu', '$1 $2', $text) ?: $text;
        $text = preg_replace('/([A-Za-z])([—–-])(?=\S)/u', '$1 $2 ', $text) ?: $text;

        $replacements = [
            'KarakteristikELECTRE' => 'Karakteristik ELECTRE',
            'Menggunakanpembobotankriteria' => 'Menggunakan pembobotan kriteria',
            'Cocokuntukbanyakalternatif' => 'Cocok untuk banyak alternatif',
            'Memilikiproses' => 'Memiliki proses',
            'BerbasisKomputer' => 'Berbasis Komputer',
            'MeningkatkanKomunikasi' => 'Meningkatkan Komunikasi',
            'MendukungPengambilanKeputusan' => 'Mendukung Pengambilan Keputusan',
            'MemfasilitasiBrainstorming' => 'Memfasilitasi Brainstorming',
            'MengurangiDominasiIndividu' => 'Mengurangi Dominasi Individu',
            'MendukungKolaborasiJarakJauh' => 'Mendukung Kolaborasi Jarak Jauh',
            'Masalahkompleks' => 'Masalah kompleks',
            'Strukturhierarki' => 'Struktur hierarki',
            'AHPmemecahmasalahmenjadistrukturhierarki' => 'AHP memecah masalah menjadi struktur hierarki',
            'Goal(Tujuan)' => 'Goal (Tujuan)',
            'Criteria(Kriteria)' => 'Criteria (Kriteria)',
            'Sub-criteria(opsional)' => 'Sub-criteria (opsional)',
            'Alternatives(Alternatif)' => 'Alternatives (Alternatif)',
            'Sedikitlebihpenting' => 'Sedikit lebih penting',
            'Lebihpenting' => 'Lebih penting',
            'Sangatpenting' => 'Sangat penting',
            'Mutlaklebihpenting' => 'Mutlak lebih penting',
            'MenyusunHierarki' => 'Menyusun Hierarki',
            'MembuatMatriksPerbandinganBerpasangan' => 'Membuat Matriks Perbandingan Berpasangan',
            'Contohmatriks' => 'Contoh matriks',
            'NormalisasiMatriks' => 'Normalisasi Matriks',
            'MenghitungBobot' => 'Menghitung Bobot',
            'BersifatSubjektif' => 'Bersifat Subjektif',
            'Hasilsangattergantungpada' => 'Hasil sangat tergantung pada',
            'Penilaianmanusia' => 'Penilaian manusia',
            'expertjudgement' => 'expert judgement',
            'Bisabiasatautidakkonsisten' => 'Bisa bias atau tidak konsisten',
            'JumlahPerbandinganBanyak' => 'Jumlah Perbandingan Banyak',
            'Jikakriteria' => 'Jika kriteria',
            'makajumlahperbandingan' => 'maka jumlah perbandingan',
            'Semakinbanyak' => 'Semakin banyak',
            'semakinrumit' => 'semakin rumit',
            'KonsistensiSulitDijaga' => 'Konsistensi Sulit Dijaga',
            'Seringterjadi' => 'Sering terjadi',
            'Nilaitidakkonsisten' => 'Nilai tidak konsisten',
            'Harusrevisimatriksberulang' => 'Harus revisi matriks berulang',
            'TidakEfisienuntukDataBesar' => 'Tidak Efisien untuk Data Besar',
            'Tidak Efisienuntuk Data Besar' => 'Tidak Efisien untuk Data Besar',
            'AHPkurangcocokjika' => 'AHP kurang cocok jika',
            'Alternatifsangatbanyak' => 'Alternatif sangat banyak',
            'Kriteriaterlalu' => 'Kriteria terlalu',
            'Sensitifterhadap' => 'Sensitif terhadap',
            'Sensitifter' => 'Sensitif ter',
            'LangkahMetodeELECTRE' => 'Langkah Metode ELECTRE',
            'Langkah MetodeELECTRE' => 'Langkah Metode ELECTRE',
            'MenyusunMatriksKeputusan' => 'Menyusun Matriks Keputusan',
            'MatriksKeputusan' => 'Matriks Keputusan',
            'Bentukumum' => 'Bentuk umum',
            'NormalisasiMatriks' => 'Normalisasi Matriks',
            'hasilnormalisasi' => 'hasil normalisasi',
            'datakriteria' => 'data kriteria',
            'Menggunakanpembobotan' => 'Menggunakan pembobotan',
            'banyakalternatif' => 'banyak alternatif',
            'Mempunyaiproses' => 'Mempunyai proses',
        ];

        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        $text = $this->spaceCommonGlue($text);
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        return trim($text);
    }

    private function spaceCommonGlue(string $text): string
    {
        $words = [
            'adalah', 'merupakan', 'untuk', 'dengan', 'dalam', 'pada', 'dari', 'atau',
            'jika', 'maka', 'karena', 'sebagai', 'secara', 'terhadap', 'antara', 'melalui',
            'tujuan', 'manfaat', 'fungsi', 'proses', 'jumlah',
            'kriteria', 'alternatif', 'keputusan', 'kelompok', 'sistem', 'metode', 'konsep',
            'langkah', 'contoh', 'rumus', 'keterangan', 'penting', 'besar', 'kecil',
        ];

        foreach ($words as $word) {
            $quoted = preg_quote($word, '/');
            $text = preg_replace('/([\pL])('.$quoted.')/iu', '$1 $2', $text) ?: $text;
            $text = preg_replace('/('.$quoted.')([\pL])/iu', '$1 $2', $text) ?: $text;
        }

        return $text;
    }

    private function topicLabel(array $snippet, int $index): string
    {
        $text = $this->clean($snippet['snippet'] ?? '');

        if (preg_match('/(?:karakteristik|karakter|ciri|manfaat|langkah|jenis)\s+[\pL\pN\s-]{2,60}/iu', $text, $match)) {
            return trim($match[0]);
        }

        return ($snippet['title'] ?? 'materi').' bagian '.($index + 1);
    }

    private function answerText(string $snippet, int $limit = 420): string
    {
        $text = $this->clean($snippet);
        $text = preg_replace('/^\d+\s+/', '', $text) ?: $text;

        return $this->completeLimit($text, $limit);
    }

    private function completeLimit(string $text, int $limit): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $boundary = max(
            mb_strrpos($cut, '.') ?: 0,
            mb_strrpos($cut, ';') ?: 0,
            mb_strrpos($cut, ',') ?: 0
        );

        if ($boundary >= 80) {
            return trim(mb_substr($cut, 0, $boundary), " \t\n\r\0\x0B,;.");
        }

        return trim(preg_replace('/\s+\S*$/u', '', $cut) ?: $cut);
    }

    public function formatFlashcardBack(string $snippet): string
    {
        $text = $this->clean($snippet);
        $text = preg_replace('/^\d+\s+/', '', $text) ?: $text;
        $text = preg_replace('/^\s*[\pL\s]+\(\d+\)\s*/u', '', $text) ?: $text;
        $text = preg_replace('/\s+\d{1,2}\s+[\pL\s]+\(\d+\)\s*/u', "\n", $text) ?: $text;
        $text = preg_replace('/\b(Langkah\s+\d+)\s*[—–-]\s*/iu', '$1 - ', $text) ?: $text;

        $numbered = preg_split('/\s*(?=\d+\.\s*)/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = count($numbered) >= 2
            ? $numbered
            : (preg_split('/(?=\b(?:Goal|Criteria|Sub-criteria|Alternatives|Langkah\s+\d+|Menyusun|Membuat|Normalisasi|Menghitung|Bersifat|Jumlah|Konsistensi|Tidak Efisien|Sensitif|Menggunakan|Cocok|Memiliki)\b)|(?<=\.)\s+/u', $text) ?: []);

        $points = collect($parts)
            ->map(fn (string $part) => preg_replace('/^\d+\.\s*/u', '', $part) ?: $part)
            ->map(fn (string $part) => preg_replace('/\b\d{1,2}\s+[\pL\s]+\(\d+\)\s*/u', '', $part) ?: $part)
            ->map(fn (string $part) => preg_replace('/\b(?:contoh|rumus)\s*:\s*.*$/iu', '', $part) ?: $part)
            ->map(fn (string $part) => preg_replace('/\b(?:bentuk umum|keterangan)\s*:?.*$/iu', '', $part) ?: $part)
            ->map(fn (string $part) => $this->removeTruncatedTail($part))
            ->map(fn (string $part) => preg_replace('/^([\pL\s]+):\s*\1:\s*/iu', '$1: ', $part) ?: $part)
            ->map(fn (string $part) => preg_replace('/\b([\pL\s]{4,80})\s+\1\b/iu', '$1', $part) ?: $part)
            ->map(fn (string $part) => preg_replace('/^(?:karakteristik|langkah|metode|kekurangan|kelebihan|tujuan|manfaat)(?:\s+[\pL\pN]+){0,4}\s*:\s*/iu', '', $part) ?: $part)
            ->map(fn (string $part) => trim($part, " \t\n\r\0\x0B:;,.-"))
            ->map(fn (string $part) => preg_replace('/\s+/', ' ', $part) ?: $part)
            ->filter(fn (string $part) => mb_strlen($part) >= 8)
            ->reject(fn (string $part) => preg_match('/^\d+\s*$/', $part))
            ->reject(fn (string $part) => preg_match('/^(contoh|rumus|nilai|keterangan|kekurangan|kelebihan)$/iu', $part))
            ->reject(fn (string $part) => preg_match('/^(?:karakteristik|langkah|metode|kekurangan|kelebihan|tujuan|manfaat)(?:\s+[\pL\pN]+){0,4}(?:\s*\(\d+\))?$/iu', $part))
            ->reject(fn (string $part) => preg_match('/^(memiliki|langkah\s+\d+)$/iu', $part))
            ->reject(fn (string $part) => preg_match('/\b[\pL]{1,3}$/iu', $part))
            ->unique(fn (string $part) => Str::lower($part))
            ->take(5)
            ->values();

        if ($points->isEmpty()) {
            return Str::limit($this->removeTruncatedTail($text), 220, '');
        }

        return $points
            ->map(fn (string $point) => '- '.Str::limit($point, 180, ''))
            ->implode("\n");
    }

    private function removeTruncatedTail(string $text): string
    {
        if (! preg_match('/(?:\.{3}|…)/u', $text)) {
            return trim($text);
        }

        $text = preg_replace('/\s+\S*(?:\.{3}|…).*$/u', '', $text) ?: $text;

        if (str_contains($text, ',')) {
            $text = preg_replace('/,\s*[^,]*$/u', '', $text) ?: $text;
        }

        return trim($text);
    }

    private function safeAiFailureReason(string $reason): string
    {
        $lower = Str::lower($reason);

        return match (true) {
            str_contains($lower, 'quota')
                || str_contains($lower, 'resource_exhausted')
                || str_contains($lower, 'prepayment credits are depleted')
                    => 'Asisten belajar AI sedang tidak dapat dihubungi. Silakan coba beberapa saat lagi.',
            str_contains($lower, 'unavailable')
                || str_contains($lower, 'high demand')
                || str_contains($lower, '503')
                    => 'Asisten belajar AI sedang sibuk. Silakan coba beberapa saat lagi.',
            str_contains($lower, 'api key')
                || str_contains($lower, 'permission_denied')
                || str_contains($lower, '401')
                || str_contains($lower, '403')
                    => 'Asisten belajar AI sedang mengalami kendala teknis. Silakan coba beberapa saat lagi.',
            str_contains($lower, 'timeout')
                || str_contains($lower, 'timed out')
                || str_contains($lower, 'curl error')
                || str_contains($lower, 'koneksi')
                    => 'Koneksi ke asisten belajar AI terputus. Silakan coba beberapa saat lagi.',
            default => 'Asisten belajar AI belum berhasil merespons. Silakan coba beberapa saat lagi.',
        };
    }
}
