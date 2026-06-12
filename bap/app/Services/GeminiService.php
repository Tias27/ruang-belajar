<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiService
{
    public function summarize(Document|DocumentFolder $document): array
    {
        return $this->jsonPrompt($document, 'Buat ringkasan belajar dalam JSON dengan kunci: short_summary, full_summary, key_points array, conclusion. Gunakan bahasa Indonesia yang jelas, urutkan dari konsep dasar ke konsep lanjutan, dan tambahkan contoh singkat jika materi cocok.', 4096);
    }

    public function chat(Document|DocumentFolder $document, string $question): string
    {
        $prompt = "Jawab pertanyaan pengguna berdasarkan materi yang diberikan.
Gunakan cuplikan relevan sebagai prioritas utama, lalu gunakan konteks tambahan jika perlu.
Jika istilah pertanyaan berbeda tetapi maknanya masih sama dengan materi, jelaskan berdasarkan konsep yang paling dekat.
Jangan cepat menyimpulkan tidak ada. Katakan informasi tidak ditemukan hanya jika setelah membaca cuplikan relevan dan konteks tambahan memang tidak ada dasar jawaban.
Jelaskan bertahap, gunakan bahasa Indonesia yang mudah dipahami, dan sertakan contoh sederhana jika membantu.
Jika pertanyaan menanyakan karakter, karakteristik, ciri, jenis, manfaat, langkah, atau daftar poin, jawab semua poin yang terlihat di materi, bukan hanya satu contoh.

Pertanyaan: {$question}";

        return $this->textPrompt($document, $prompt, $question, 2048);
    }

    public function generateQuiz(Document|DocumentFolder $document): array
    {
        return $this->jsonPrompt($document, 'Buat 10 soal pilihan ganda dalam JSON: {"questions":[{"question":"","options":["A. ...","B. ...","C. ...","D. ..."],"correct_answer":"A","explanation":""}]}. Susun dari mudah ke sulit, gunakan distraktor yang masuk akal, dan buat pembahasan singkat yang mengajarkan konsep.', 3072);
    }

    public function generateFlashcards(Document|DocumentFolder $document): array
    {
        return $this->jsonPrompt($document, 'Buat 10 kartu belajar dalam JSON: {"flashcards":[{"front":"","back":""}]}. Bagian depan berisi pertanyaan singkat, bagian belakang berisi jawaban jelas dan tidak terlalu panjang.', 3072);
    }

    private function textPrompt(Document|DocumentFolder $document, string $instruction, ?string $question = null, int $maxOutputTokens = 4096, ?string $responseMimeType = null): string
    {
        $response = $this->send($this->buildPrompt($document, $instruction, $question), $maxOutputTokens, $responseMimeType);

        return data_get($response, 'candidates.0.content.parts.0.text', 'AI belum memberikan jawaban.');
    }

    private function jsonPrompt(Document|DocumentFolder $document, string $instruction, int $maxOutputTokens = 4096): array
    {
        $text = $this->textPrompt($document, $instruction."\nBalas hanya JSON valid tanpa markdown.", null, $maxOutputTokens, 'application/json');
        $json = json_decode($this->extractJson($text), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return [];
    }

    private function extractJson(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?|```$/im', '', $text) ?: $text;
        $text = trim($text);
        
        $startObj = strpos($text, '{');
        $startArr = strpos($text, '[');
        
        $start = false;
        if ($startObj !== false && $startArr !== false) {
            $start = min($startObj, $startArr);
        } else {
            $start = $startObj !== false ? $startObj : $startArr;
        }

        $endObj = strrpos($text, '}');
        $endArr = strrpos($text, ']');
        
        $end = false;
        if ($endObj !== false && $endArr !== false) {
            $end = max($endObj, $endArr);
        } else {
            $end = $endObj !== false ? $endObj : $endArr;
        }

        if ($start === false || $end === false || $end < $start) {
            return '{}';
        }

        return substr($text, $start, $end - $start + 1);
    }

    private function send(string $prompt, int $maxOutputTokens = 4096, ?string $responseMimeType = null): array
    {
        $this->extendExecutionTime();

        $apiKeys = $this->apiKeys();
        if ($apiKeys === []) {
            throw new RuntimeException('GEMINI_API_KEY atau GEMINI_API_KEYS belum diatur.');
        }

        $models = $this->models();
        $baseUrl = rtrim(config('services.gemini.base_url'), '/');
        $timeout = max(5, min((int) config('services.gemini.timeout', 90), 90));
        $connectTimeout = max(3, min((int) config('services.gemini.connect_timeout', 15), $timeout));

        $generationConfig = [
            'temperature' => 0.25,
            'topP' => 0.9,
            'maxOutputTokens' => max(512, min($maxOutputTokens, 4096)),
        ];

        if ($responseMimeType) {
            $generationConfig['responseMimeType'] = $responseMimeType;
        }

        $lastError = null;

        foreach ($models as $modelIndex => $model) {
            foreach ($apiKeys as $keyIndex => $apiKey) {
                try {
                    $response = Http::timeout($timeout)
                        ->connectTimeout($connectTimeout)
                        ->withOptions(['verify' => config('services.gemini.verify_ssl')])
                        ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
                            'contents' => [[
                                'parts' => [['text' => $prompt]],
                            ]],
                            'generationConfig' => $generationConfig,
                        ]);
                } catch (ConnectionException $exception) {
                    throw new RuntimeException('Koneksi ke Gemini gagal. Periksa internet, DNS, firewall, atau koneksi Laragon/PHP ke generativelanguage.googleapis.com.', previous: $exception);
                }

                if ($response->successful()) {
                    return $response->json();
                }

                $body = $response->body();
                $lastError = 'Gemini model '.$model.' / API key #'.($keyIndex + 1).' gagal: '.$body;

                if (! $this->shouldTryNextGeminiTarget($response->status(), $body)) {
                    throw new RuntimeException('Gemini API gagal pada model '.$model.': '.$body);
                }
            }
        }

        throw new RuntimeException('Semua model/API key Gemini tidak bisa dipakai. '.$lastError);
    }

    private function apiKeys(): array
    {
        $keys = [];
        $multiKeys = (string) config('services.gemini.api_keys', '');
        if ($multiKeys !== '') {
            $keys = array_merge($keys, preg_split('/[\s,;]+/', $multiKeys) ?: []);
        }

        $singleKey = config('services.gemini.api_key');
        if ($singleKey) {
            $keys[] = $singleKey;
        }

        return collect($keys)
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function models(): array
    {
        $models = [];
        $multiModels = (string) config('services.gemini.models', '');
        if ($multiModels !== '') {
            $models = array_merge($models, preg_split('/[\s,;]+/', $multiModels) ?: []);
        }

        $singleModel = config('services.gemini.model');
        if ($singleModel) {
            $models[] = $singleModel;
        }

        return collect($models)
            ->map(fn ($model) => trim((string) $model))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function shouldTryNextGeminiTarget(int $status, string $body): bool
    {
        return $status === 429
            || $status === 503
            || ($status === 404 && str_contains($body, 'not found'))
            || str_contains($body, 'UNAVAILABLE')
            || str_contains($body, 'high demand')
            || str_contains($body, 'RESOURCE_EXHAUSTED')
            || str_contains($body, 'prepayment credits are depleted')
            || str_contains($body, 'API key not valid')
            || str_contains($body, 'PERMISSION_DENIED');
    }

    private function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(90);
        }

        @ini_set('max_execution_time', '90');
    }

    private function buildPrompt(Document|DocumentFolder $document, string $instruction, ?string $question = null): string
    {
        $sourceType = $document instanceof DocumentFolder ? 'FOLDER MATERI' : 'DOKUMEN';
        
        $contextPart = "";
        if ($question) {
            $relevantContext = $this->chatContext($document, $question);
            $contextPart = "KONTEKS RELEVAN:\n{$relevantContext}";
        } else {
            $contextPart = "KONTEKS DOKUMEN:\n" . $this->generationContext($document);
        }

        return "Kamu adalah RuangBelajar AI, asisten belajar untuk pelajar SD, SMP, SMA/SMK, mahasiswa, dan pembelajar umum.
Tugasmu membantu pengguna memahami materi, bukan sekadar memberi jawaban pendek.
Aturan:
1. Gunakan bahasa Indonesia yang natural dan mudah dipahami.
2. Sesuaikan kedalaman jawaban dengan tingkat materi pada konteks.
3. Jelaskan dari konsep dasar ke detail penting.
4. Cari jawaban dari istilah yang sama, sinonim, contoh, definisi, atau penjelasan konsep yang berkaitan.
5. Jika konteks tidak memuat jawaban setelah dicari secara wajar, katakan dengan jujur bahwa informasi tidak ditemukan di materi.
6. Jangan mengarang sumber di luar konteks.
7. Untuk materi teknis, beri contoh sederhana jika membantu.

{$sourceType}: {$document->title}

{$contextPart}

INSTRUKSI:
{$instruction}";
    }

    private function chatContext(Document|DocumentFolder $document, string $question): string
    {
        if ($document instanceof Document) {
            return $this->relevantContext($document->extracted_text ?: '', $question, 5000);
        }

        $sections = $document->documentsForPrompt()
            ->filter(fn (Document $item) => filled($item->extracted_text))
            ->map(function (Document $item) use ($question) {
                $context = $this->relevantContext($item->extracted_text ?: '', $question, 1600);

                return "### {$item->title}\n{$context}";
            })
            ->implode("\n\n");

        return $sections !== ''
            ? Str::limit($sections, 6500)
            : 'Materi folder belum memiliki teks yang berhasil diekstrak.';
    }

    private function generationContext(Document|DocumentFolder $document): string
    {
        if ($document instanceof Document) {
            return Str::limit($document->extracted_text ?: 'Materi belum memiliki teks yang berhasil diekstrak.', 12000);
        }

        $sections = $document->documentsForPrompt()
            ->filter(fn (Document $item) => filled($item->extracted_text))
            ->map(fn (Document $item) => "### {$item->title}\n".Str::limit($item->extracted_text ?: '', 1400))
            ->implode("\n\n");

        return $sections !== ''
            ? Str::limit($sections, 12000)
            : 'Materi folder belum memiliki teks yang berhasil diekstrak.';
    }

    private function relevantContext(string $context, string $question, int $limit = 5000): string
    {
        $context = trim($context);
        if ($context === '') {
            return 'Materi belum memiliki teks yang berhasil diekstrak.';
        }

        $context = Str::limit($context, 90000, '');

        $terms = collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($question)) ?: [])
            ->filter(fn (string $term) => mb_strlen($term) >= 3)
            ->reject(fn (string $term) => in_array($term, [
                'apa', 'dan', 'yang', 'ini', 'itu', 'pada', 'dari', 'atau', 'berikan', 'jelaskan', 'contoh', 'materi', 'dokumen',
            ], true))
            ->unique()
            ->values();

        if ($terms->isEmpty()) {
            return Str::limit($context, $limit);
        }

        $lineBlocks = $this->relevantLineBlocks($context, $terms->all(), $limit);
        if ($lineBlocks !== '') {
            return $lineBlocks;
        }

        $sentences = preg_split('/(?<=[.!?])\s+|\n+/u', $context) ?: [];
        $ranked = collect($sentences)
            ->map(fn (string $sentence) => trim($sentence))
            ->filter(fn (string $sentence) => mb_strlen($sentence) >= 20)
            ->map(function (string $sentence) use ($terms) {
                $lower = mb_strtolower($sentence);
                $score = $terms->sum(fn (string $term) => str_contains($lower, $term) ? 1 : 0);

                return ['sentence' => $sentence, 'score' => $score];
            })
            ->filter(fn (array $item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take(12)
            ->pluck('sentence')
            ->implode("\n");

        return $ranked !== '' ? Str::limit($ranked, $limit) : Str::limit($context, $limit);
    }

    private function relevantLineBlocks(string $context, array $terms, int $limit): string
    {
        $lines = collect(preg_split('/\R+/u', $context) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return '';
        }

        $matches = $lines
            ->map(function (string $line, int $index) use ($terms) {
                $lower = mb_strtolower($line);
                $score = collect($terms)->sum(function (string $term) use ($lower) {
                    return str_contains($lower, $term) || str_contains($lower, Str::singular($term)) ? 1 : 0;
                });

                return ['index' => $index, 'score' => $score];
            })
            ->filter(fn (array $item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take(5)
            ->values();

        if ($matches->isEmpty()) {
            return '';
        }

        $selected = [];
        foreach ($matches as $match) {
            $start = max(0, $match['index'] - 3);
            $end = min($lines->count() - 1, $match['index'] + 10);

            for ($index = $start; $index <= $end; $index++) {
                $selected[$index] = $lines[$index];
            }
        }

        ksort($selected);

        return Str::limit(implode("\n", $selected), $limit);
    }
}
