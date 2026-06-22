<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Quiz;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiService
{
    public function summarize(Document|DocumentFolder $document, ?array $selectedDocIds = null): array
    {
        $isFolder = $document instanceof DocumentFolder;
        $typeLabel = $isFolder ? 'seluruh materi dari kumpulan file dalam folder ini' : 'materi ini';
        
        $prompt = "Buat ringkasan belajar yang sangat komprehensif, mendalam, sangat panjang, dan rapi dari {$typeLabel} dalam format JSON dengan kunci:
- full_summary: penjelasan materi secara sangat detail, menyeluruh, terstruktur, dan mendalam. 
  " . ($isFolder ? "PENTING: Gabungkan materi dari seluruh dokumen secara logis, terstruktur, dan runtut di bawah sub-topik pembahasan yang relevan (gunakan subjudul topik menggunakan markdown heading seperti ### atau ####). JANGAN membagi ringkasan secara terpisah menggunakan nama file/dokumen asal. Integrasikan seluruh penjelasan materi tersebut menjadi satu alur pembahasan belajar yang utuh, komprehensif, dan sangat terperinci (total panjang ringkasan harus berkisar antara 2000 sampai 4500 kata secara keseluruhan)." : "PENTING: Bedah seluruh bab, sub-materi, konsep, rumus, istilah teknis, dan langkah-langkah dari file ini secara sangat detail. Buat penjelasan yang sangat mendalam dan panjang (minimal 5-8 paragraf panjang, sekitar 1500-3000 kata secara keseluruhan) untuk menjelaskan seluruh materi ini secara komprehensif. Jangan menyingkat atau memadatkan penjelasan.") . "
  **Gunakan format Markdown yang kaya (seperti heading, bold, list bullet/numbering, tabel, blockquote, atau kode jika relevan) di dalam nilai string ini agar rapi saat ditampilkan.** 
- key_points: array of string, berisi poin-poin utama yang sangat penting (buat minimal " . ($isFolder ? "20-30" : "12-20") . " poin penting yang mencakup seluruh materi secara menyeluruh).
- conclusion: kesimpulan akhir yang merangkum keseluruhan pembelajaran secara holistik.

Aturan Penting:
1. Jangan membuat ringkasan yang terlalu singkat atau hanya garis besar saja. Pengguna membutuhkan penjelasan detail dari setiap konsep agar bisa dipelajari dengan baik.
2. Fokus ke penjabaran inti materi, penjelasan konsep, alasan, langkah-langkah, rumus (jika ada), dan insight penting.
3. Abaikan bagian tidak penting seperti cover, daftar isi, lampiran kosong, atau identitas tugas.
4. Pastikan response dalam format JSON valid.";

        return $this->jsonPrompt($document, $prompt, 7500, 'summary', $selectedDocIds);
    }

    public function chat(Document|DocumentFolder $document, string $question, array $history = [], ?array $selectedDocIds = null): string
    {
        $prompt = "Jawab pertanyaan pengguna berdasarkan materi yang diberikan.
Gunakan cuplikan relevan sebagai prioritas utama, lalu gunakan konteks tambahan jika perlu.
Jika istilah pertanyaan berbeda tetapi maknanya masih sama dengan materi, jelaskan berdasarkan konsep yang paling dekat.
Jika pengguna secara eksplisit menyapa (seperti 'halo', 'hai', 'selamat pagi/sore', dll.) di awal percakapan, sapa mereka kembali dengan ramah, perkenalkan dirimu sebagai asisten belajar, dan tanyakan materi apa yang ingin didiskusikan. Jika percakapan sudah berjalan (ada riwayat chat) ATAU pesan terakhir pengguna langsung bertanya tanpa sapaan, JANGAN berikan salam pembuka atau perkenalan diri lagi, langsung jawab pertanyaannya secara to-the-point. Namun, jika pertanyaan benar-benar di luar topik materi (seperti resep makanan, menghitung jumlah huruf kata acak, atau di luar konteks belajar dokumen), tolak secara sopan dan jelaskan bahwa kamu fokus membantu memahami materi ini.
Jangan cepat menyimpulkan tidak ada. Katakan informasi tidak ditemukan hanya jika setelah membaca cuplikan relevan dan konteks tambahan memang tidak ada dasar jawaban.
Jelaskan bertahap, gunakan bahasa Indonesia yang mudah dipahami, dan sertakan contoh sederhana jika membantu.
Jika pertanyaan menanyakan karakteristik (ciri-ciri), jenis, manfaat, langkah, atau daftar poin, jawab semua poin yang terlihat di materi, bukan hanya satu contoh.
Jika jawaban ada dalam bentuk daftar di materi, rangkum seluruh daftar tersebut dengan bahasa rapi.
Jika materi punya istilah teknis, jelaskan arti istilahnya dulu lalu hubungkan dengan konteks dokumen.
Berikan jawaban yang terasa seperti tutor pintar: jelas, lengkap, tidak kaku, dan tetap tidak bertele-tele.
Jangan cuma menjawab satu poin jika materi memuat banyak poin. Gabungkan semua poin relevan menjadi jawaban utuh.
Kalau pertanyaan pendek atau typo, tafsirkan maksud terdekat dari materi.
Jika jawaban berisi data perbandingan, daftar item dengan banyak atribut, atau struktur yang lebih mudah dibaca dalam bentuk tabel, gunakan format tabel Markdown.

Pertanyaan: {$question}";

        return $this->textPrompt($document, $prompt, $question, 1100, null, 'chat', $history, $selectedDocIds);
    }

    public function streamChat(Document|DocumentFolder $document, string $question, array $history = [], ?array $selectedDocIds = null): \Generator
    {
        $prompt = "Jawab pertanyaan pengguna berdasarkan materi yang diberikan.
Gunakan cuplikan relevan sebagai prioritas utama, lalu gunakan konteks tambahan jika perlu.
Jika istilah pertanyaan berbeda tetapi maknanya masih sama dengan materi, jelaskan berdasarkan konsep yang paling dekat.
Jika pengguna secara eksplisit menyapa (seperti 'halo', 'hai', 'selamat pagi/sore', dll.) di awal percakapan, sapa mereka kembali dengan ramah, perkenalkan dirimu sebagai asisten belajar, dan tanyakan materi apa yang ingin didiskusikan. Jika percakapan sudah berjalan (ada riwayat chat) ATAU pesan terakhir pengguna langsung bertanya tanpa sapaan, JANGAN berikan salam pembuka or perkenalan diri lagi, langsung jawab pertanyaannya secara to-the-point. Namun, jika pertanyaan benar-benar di luar topik materi (seperti resep makanan, menghitung jumlah huruf kata acak, atau di luar konteks belajar dokumen), tolak secara sopan dan jelaskan bahwa kamu fokus membantu memahami materi ini.
Jangan cepat menyimpulkan tidak ada. Katakan informasi tidak ditemukan hanya jika setelah membaca cuplikan relevan dan konteks tambahan memang tidak ada dasar jawaban.
Jelaskan bertahap, gunakan bahasa Indonesia yang mudah dipahami, dan sertakan contoh sederhana jika membantu.
Jika pertanyaan menanyakan karakteristik (ciri-ciri), jenis, manfaat, langkah, atau daftar poin, jawab semua poin yang terlihat di materi, bukan hanya satu contoh.
Jika jawaban ada dalam bentuk daftar di materi, rangkum seluruh daftar tersebut dengan bahasa rapi.
Jika materi punya istilah teknis, jelaskan arti istilahnya dulu lalu hubungkan dengan konteks dokumen.
Berikan jawaban yang terasa seperti tutor pintar: jelas, lengkap, tidak kaku, dan tetap tidak bertele-tele.
Jangan cuma menjawab satu poin jika materi memuat banyak poin. Gabungkan semua poin relevan menjadi jawaban utuh.
Kalau pertanyaan pendek atau typo, tafsirkan maksud terdekat dari materi.
Jika jawaban berisi data perbandingan, daftar item dengan banyak atribut, atau struktur yang lebih mudah dibaca dalam bentuk tabel, gunakan format tabel Markdown.

Pertanyaan: {$question}";

        $builtPrompt = $this->buildPrompt($document, $prompt, $question, 'chat', $history, $selectedDocIds);

        if (config('services.ai.provider') === 'kimchi') {
            return $this->sendKimchiStream($builtPrompt, 1100, null, 'chat');
        }

        return $this->sendGeminiStream($builtPrompt, 1100, null, 'chat');
    }

    public function generateQuiz(Document|DocumentFolder $document, string $questionType = 'multiple_choice', int $questionCount = 10, ?array $selectedDocIds = null): array
    {
        $questionCount = max(1, min(30, $questionCount));

        if ($questionType === 'essay') {
            return $this->jsonPrompt($document, "Buat {$questionCount} soal esai dalam JSON: {\"questions\":[{\"question\":\"\",\"options\":[],\"correct_answer\":\"contoh jawaban ideal\",\"explanation\":\"pembahasan singkat\"}]}. Soal menguji pemahaman konsep dari materi. Hindari cover/judul/identitas dokumen. Pertanyaan ringkas, jawaban ideal maksimal 3 kalimat, pembahasan maksimal 1 kalimat.", min(2800, 900 + ($questionCount * 160)), 'quiz', $selectedDocIds);
        }

        return $this->jsonPrompt($document, "Buat {$questionCount} soal pilihan ganda dalam JSON: {\"questions\":[{\"question\":\"\",\"options\":[\"A. ...\",\"B. ...\",\"C. ...\",\"D. ...\"],\"correct_answer\":\"A\",\"explanation\":\"\"}]}. Soal menguji pemahaman, bukan menyalin kalimat panjang. Pilihan pendek dan masuk akal. Hindari cover/judul/identitas dokumen. Pembahasan maksimal 1 kalimat. PENTING: Acak posisi jawaban yang benar secara merata di antara pilihan A, B, C, dan D pada setiap soal. Jangan menumpuk jawaban benar pada satu huruf pilihan saja (misalnya B terus-menerus atau A terus-menerus).", min(2800, 800 + ($questionCount * 130)), 'quiz', $selectedDocIds);
    }

    public function generateFlashcards(Document|DocumentFolder $document, ?array $selectedDocIds = null): array
    {
        return $this->jsonPrompt($document, 'Buat 10 kartu belajar dalam JSON: {"flashcards":[{"front":"","back":""}]}. Depan berupa pertanyaan singkat. Belakang berupa jawaban jelas maksimal 2 poin pendek. Prioritaskan definisi, manfaat, karakteristik, langkah, rumus, dan contoh penting. Hindari cover dan identitas tugas.', 1300, 'flashcard', $selectedDocIds);
    }

    public function gradeEssayQuiz(Quiz $quiz, array $answers): array
    {
        $quiz->loadMissing('questions');

        $payload = $quiz->questions
            ->map(fn ($question) => [
                'id' => $question->id,
                'question' => $question->question,
                'ideal_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
                'student_answer' => (string) ($answers[$question->id] ?? ''),
            ])
            ->values()
            ->all();

        $prompt = "Nilai jawaban esai siswa berdasarkan jawaban ideal.
Aturan penilaian:
1. Beri skor 0 sampai 100 untuk setiap soal.
2. Jika jawaban benar sebagian, beri skor parsial sesuai kelengkapan dan ketepatan.
3. Jangan terlalu kaku pada perbedaan kata, nilai makna dan konsepnya.
4. Jika jawaban sangat pendek tetapi memuat inti benar, beri skor wajar, bukan nol.
5. Beri feedback singkat dan saran perbaikan.

Data soal:
".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."

Balas hanya JSON valid:
{\"items\":[{\"id\":1,\"score\":0,\"feedback\":\"\",\"suggested_answer\":\"\"}],\"summary\":\"\"}";

        $text = $this->sendText($prompt, 2600, 'application/json', 'quiz');
        $json = json_decode($this->extractJson($text), true);

        return json_last_error() === JSON_ERROR_NONE ? $json : [];
    }

    private function textPrompt(Document|DocumentFolder $document, string $instruction, ?string $question = null, int $maxOutputTokens = 4096, ?string $responseMimeType = null, string $task = 'default', array $history = [], ?array $selectedDocIds = null): string
    {
        return $this->sendText($this->buildPrompt($document, $instruction, $question, $task, $history, $selectedDocIds), $maxOutputTokens, $responseMimeType, $task);
    }

    private function jsonPrompt(Document|DocumentFolder $document, string $instruction, int $maxOutputTokens = 4096, string $task = 'default', ?array $selectedDocIds = null): array
    {
        $text = $this->textPrompt($document, $instruction."\nBalas hanya JSON valid tanpa markdown.", null, $maxOutputTokens, 'application/json', $task, [], $selectedDocIds);
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

    private function sendText(string $prompt, int $maxOutputTokens = 4096, ?string $responseMimeType = null, string $task = 'default'): string
    {
        $this->extendExecutionTime();
        $isJson = ($responseMimeType === 'application/json');

        if (config('services.ai.provider') === 'kimchi') {
            try {
                return $this->cleanModelText($this->sendKimchi($prompt, $maxOutputTokens, $responseMimeType, $task), $isJson);
            } catch (RuntimeException $exception) {
                if (config('services.ai.fallback_provider') !== 'gemini' || ! $this->geminiConfigured()) {
                    throw $exception;
                }

                report($exception);
                $response = $this->sendGemini($prompt, $maxOutputTokens, $responseMimeType, $task);

                return $this->cleanModelText(data_get($response, 'candidates.0.content.parts.0.text', 'AI belum memberikan jawaban.'), $isJson);
            }
        }

        try {
            $response = $this->sendGemini($prompt, $maxOutputTokens, $responseMimeType, $task);

            return $this->cleanModelText(data_get($response, 'candidates.0.content.parts.0.text', 'AI belum memberikan jawaban.'), $isJson);
        } catch (RuntimeException $exception) {
            if (! $this->kimchiConfigured()) {
                throw $exception;
            }

            report($exception);

            return $this->cleanModelText($this->sendKimchi($prompt, $maxOutputTokens, $responseMimeType, $task), $isJson);
        }
    }

    private function sendGemini(string $prompt, int $maxOutputTokens = 4096, ?string $responseMimeType = null, string $task = 'default'): array
    {
        $this->extendExecutionTime();

        $apiKeys = $this->apiKeys();
        if ($apiKeys === []) {
            throw new RuntimeException('GEMINI_API_KEY atau GEMINI_API_KEYS belum diatur.');
        }

        $models = $this->models();
        $baseUrl = rtrim(config('services.gemini.base_url'), '/');
        $timeout = max(5, min((int) config('services.gemini.timeout', 300), 300));
        $connectTimeout = max(3, min((int) config('services.gemini.connect_timeout', 15), $timeout));

        $generationConfig = [
            'temperature' => $task === 'summary' ? 0.7 : 0.4,
            'topP' => 0.9,
            'maxOutputTokens' => max(512, min($maxOutputTokens, 8192)),
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

    private function sendGeminiStream(string $prompt, int $maxOutputTokens = 4096, ?string $responseMimeType = null, string $task = 'default'): \Generator
    {
        $this->extendExecutionTime();

        $apiKeys = $this->apiKeys();
        if ($apiKeys === []) {
            throw new RuntimeException('GEMINI_API_KEY atau GEMINI_API_KEYS belum diatur.');
        }

        $models = $this->models();
        $baseUrl = rtrim(config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $timeout = max(5, min((int) config('services.gemini.timeout', 300), 300));
        $connectTimeout = max(3, min((int) config('services.gemini.connect_timeout', 15), $timeout));

        $generationConfig = [
            'temperature' => 0.4,
            'topP' => 0.9,
            'maxOutputTokens' => max(512, min($maxOutputTokens, 8192)),
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
                        ->withOptions([
                            'verify' => config('services.gemini.verify_ssl'),
                            'stream' => true,
                        ])
                        ->post("{$baseUrl}/models/{$model}:streamGenerateContent?alt=sse&key={$apiKey}", [
                            'contents' => [[
                                'parts' => [['text' => $prompt]],
                            ]],
                            'generationConfig' => $generationConfig,
                        ]);
                } catch (ConnectionException $exception) {
                    throw new RuntimeException('Koneksi streaming ke Gemini gagal.', previous: $exception);
                }

                if ($response->successful()) {
                    $body = $response->toPsrResponse()->getBody();
                    $buffer = '';

                    while (! $body->eof()) {
                        $chunk = $body->read(1024);
                        $buffer .= $chunk;

                        while (($pos = strpos($buffer, "\n")) !== false) {
                            $line = substr($buffer, 0, $pos);
                            $buffer = substr($buffer, $pos + 1);

                            $line = trim($line);
                            if (str_starts_with($line, 'data: ')) {
                                $data = substr($line, 6);
                                if ($data === '[DONE]') {
                                    break 3;
                                }

                                $json = json_decode($data, true);
                                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                                    yield $json['candidates'][0]['content']['parts'][0]['text'];
                                }
                            }
                        }
                    }
                    return; // successfully yielded all chunks
                }

                $bodyStr = $response->body();
                $lastError = 'Gemini model '.$model.' / API key #'.($keyIndex + 1).' gagal: '.$bodyStr;

                if (! $this->shouldTryNextGeminiTarget($response->status(), $bodyStr)) {
                    throw new RuntimeException('Gemini API gagal pada model '.$model.': '.$bodyStr);
                }
            }
        }

        throw new RuntimeException('Semua model/API key Gemini tidak bisa dipakai. '.$lastError);
    }

    private function sendKimchi(string $prompt, int $maxOutputTokens = 4096, ?string $responseMimeType = null, string $task = 'default'): string
    {
        $apiKey = trim((string) config('services.kimchi.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('KIMCHI_API_KEY belum diatur.');
        }

        $baseUrl = rtrim((string) config('services.kimchi.base_url', 'https://llm.cast.ai/openai/v1'), '/');
        $model = $this->kimchiModelFor($task);
        $timeout = max(5, min((int) config('services.kimchi.timeout', 300), 300));
        $connectTimeout = max(3, min((int) config('services.kimchi.connect_timeout', 15), $timeout));

        $messages = [
            [
                'role' => 'system',
                'content' => 'Kamu adalah RuangBelajar AI. Jawab dalam bahasa Indonesia yang jelas, akurat, dan hanya berdasarkan konteks materi yang diberikan. Jangan tampilkan proses berpikir, reasoning internal, tag <think>, atau catatan internal.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $task === 'summary' ? 0.7 : 0.2,
            'max_tokens' => max(512, min($maxOutputTokens, 8192)),
        ];

        if ($responseMimeType === 'application/json') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->withOptions(['verify' => config('services.kimchi.verify_ssl')])
                ->post("{$baseUrl}/chat/completions", $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Koneksi ke Kimchi gagal. Periksa endpoint, DNS, firewall, atau koneksi hosting.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Kimchi API gagal: '.$response->body());
        }

        return (string) data_get($response->json(), 'choices.0.message.content', 'AI belum memberikan jawaban.');
    }

    private function sendKimchiStream(string $prompt, int $maxOutputTokens = 4096, ?string $responseMimeType = null, string $task = 'default'): \Generator
    {
        $this->extendExecutionTime();

        $apiKey = trim((string) config('services.kimchi.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('KIMCHI_API_KEY belum diatur.');
        }

        $baseUrl = rtrim((string) config('services.kimchi.base_url', 'https://llm.cast.ai/openai/v1'), '/');
        $model = $this->kimchiModelFor($task);
        $timeout = max(5, min((int) config('services.kimchi.timeout', 300), 300));
        $connectTimeout = max(3, min((int) config('services.kimchi.connect_timeout', 15), $timeout));

        $messages = [
            [
                'role' => 'system',
                'content' => 'Kamu adalah RuangBelajar AI. Jawab dalam bahasa Indonesia yang jelas, akurat, dan hanya berdasarkan konteks materi yang diberikan. Jangan tampilkan proses berpikir, reasoning internal, tag <think>, atau catatan internal.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.7, // As requested by user: 0.7
            'max_tokens' => max(512, min($maxOutputTokens, 8192)),
            'stream' => true,
        ];

        if ($responseMimeType === 'application/json') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withToken($apiKey)
                ->acceptJson()
                ->withOptions([
                    'verify' => config('services.kimchi.verify_ssl'),
                    'stream' => true,
                ])
                ->post("{$baseUrl}/chat/completions", $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Koneksi streaming gagal.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('API gagal (Streaming): '.$response->body());
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                $line = trim($line);
                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    if ($data === '[DONE]') {
                        break 2;
                    }

                    $json = json_decode($data, true);
                    if (isset($json['choices'][0]['delta']['content'])) {
                        yield $json['choices'][0]['delta']['content'];
                    }
                }
            }
        }
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

    private function kimchiConfigured(): bool
    {
        return trim((string) config('services.kimchi.api_key', '')) !== '';
    }

    private function geminiConfigured(): bool
    {
        return $this->apiKeys() !== [];
    }

    private function kimchiModelFor(string $task): string
    {
        $specific = match ($task) {
            'chat' => config('services.kimchi.chat_model'),
            'summary' => config('services.kimchi.summary_model'),
            'quiz' => config('services.kimchi.quiz_model'),
            'flashcard' => config('services.kimchi.flashcard_model'),
            default => null,
        };

        return (string) ($specific ?: config('services.kimchi.model', 'kimi-k2.6'));
    }

    private function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        @ini_set('max_execution_time', '300');
    }

    private function buildPrompt(Document|DocumentFolder $document, string $instruction, ?string $question = null, string $task = 'default', array $history = [], ?array $selectedDocIds = null): string
    {
        $sourceType = $document instanceof DocumentFolder ? 'FOLDER MATERI' : 'DOKUMEN';
        
        $contextPart = "";
        if ($question) {
            $relevantContext = $this->chatContext($document, $question, $selectedDocIds);
            $contextPart = "KONTEKS RELEVAN:\n{$relevantContext}";
        } else {
            $contextPart = "KONTEKS DOKUMEN:\n" . $this->generationContext($document, $task, $selectedDocIds);
        }

        $historyPart = "";
        if (!empty($history)) {
            $historyPart = "RIWAYAT CHAT SEBELUMNYA:\n";
            foreach ($history as $msg) {
                $sender = $msg['role'] === 'user' ? 'User' : 'RuangBelajar AI';
                $historyPart .= "[{$sender}]: {$msg['content']}\n";
            }
            $historyPart .= "\n";
        }

        $memoryPart = "";
        if (auth()->check()) {
            $userId = auth()->id();
            $documentId = $document instanceof Document ? $document->id : null;
            $folderId = $document instanceof DocumentFolder ? $document->id : null;

            $memoryRecord = \App\Models\AiMemory::where('user_id', $userId)
                ->when($documentId, fn ($q) => $q->where('document_id', $documentId))
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->first();

            if ($memoryRecord && filled($memoryRecord->content)) {
                $memoryPart = "MEMORI/INGATAN DISKUSI SEBELUMNYA (Gunakan ini untuk mengingat pemahaman siswa, preferensi belajar, atau materi yang sudah pernah dibahas sebelumnya untuk membuat percakapan terasa natural, berkesinambungan, dan cerdas):\n{$memoryRecord->content}\n\n";
            }
        }

        return "Kamu adalah RuangBelajar AI, asisten belajar untuk pelajar SD, SMP, SMA/SMK, mahasiswa, dan pembelajar umum.
Tugasmu membantu pengguna memahami materi, bukan sekadar memberi jawaban pendek.
Aturan:
1. Gunakan bahasa Indonesia yang natural dan mudah dipahami.
2. Sesuaikan kedalaman jawaban dengan tingkat materi pada konteks.
3. Jelaskan dari konsep dasar ke detail penting.
4. Cari jawaban dari istilah yang sama, sinonim, contoh, definisi, atau penjelasan konsep yang berkaitan.
5. Berfokuslah pada topik dan konsep materi yang diunggah sebagai acuan fakta utama. Namun, Anda diizinkan dan sangat didorong untuk mengobrol secara terbuka, ramah, interaktif, dan luwes (seperti ChatGPT pada umumnya) selama pembahasan masih berhubungan dengan konsep materi tersebut. Anda boleh menggunakan analogi umum, contoh eksternal, atau memberikan tips belajar terkait topik untuk membantu pemahaman siswa.
6. Jawab dengan gaya bahasa yang santai, bersahabat, interaktif, terbuka, dan asyik untuk diajak berdiskusi/debat konseptual. Jangan kaku menolak pertanyaan selama masih ada relevansi konsep dengan materi. Jika siswa menyapa di awal chat, sapa balik dengan ramah. Di setiap respons baru, langsung jawab atau tanggapi secara asyik tanpa mengulang perkenalan diri.
7. Jangan pernah mengarang fakta administratif atau informasi yang kontradiktif dengan materi. Jika tidak ada di materi dan tidak dapat dinalar secara ilmiah dari topik materi, jelaskan secara jujur.
8. Untuk materi teknis, beri contoh sederhana jika membantu.
9. Jangan tampilkan proses berpikir, reasoning internal, tag <think>, atau catatan internal.
10. Jangan gunakan LaTeX mentah seperti $$...$$. Jika ada rumus, tulis sebagai teks biasa yang rapi, contoh: Bobot = Jumlah Baris / Jumlah Kriteria.
11. Susun jawaban seperti tutor belajar: judul pendek, penjelasan bertahap, poin bernomor/bullet, lalu kesimpulan singkat.
12. Utamakan kualitas pemahaman: jelaskan hubungan antar konsep, sebab-akibat, langkah, contoh, dan batasan jika ada.
13. Jika pengguna menanyakan informasi tentang identitas dokumen, nama penulis, nama kelompok, nama dosen, tanggal, atau teks administratif lainnya yang tertulis di dalam dokumen, jawablah dengan jujur dan lengkap berdasarkan informasi yang tertulis di dalam dokumen tersebut.
14. Jangan menyalin mentah potongan materi panjang. Olah menjadi penjelasan belajar yang rapi.
15. Jadilah mitra diskusi yang kritis dan aktif. Jika relevan, berikan satu pertanyaan pemantik singkat di akhir respons untuk mengajak siswa berpikir kritis, menantang logika konseptual mereka, atau memicu debat edukatif yang seru.

{$memoryPart}{$sourceType}: {$document->title}

{$contextPart}

{$historyPart}INSTRUKSI:
{$instruction}";
    }

    public function consolidateMemory(Document|DocumentFolder $document, \App\Models\User $user, array $newMessages): void
    {
        if (empty($newMessages)) {
            return;
        }

        $documentId = $document instanceof Document ? $document->id : null;
        $folderId = $document instanceof DocumentFolder ? $document->id : null;

        $memoryRecord = \App\Models\AiMemory::where('user_id', $user->id)
            ->when($documentId, fn ($q) => $q->where('document_id', $documentId))
            ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
            ->first();

        $currentMemory = $memoryRecord ? $memoryRecord->content : "Belum ada memori tercatat.";

        $chatSegment = "";
        foreach ($newMessages as $msg) {
            $sender = ($msg['is_ai'] ?? false) ? 'RuangBelajar AI' : 'Siswa';
            $chatSegment .= "[{$sender}]: {$msg['message']}\n";
        }

        $prompt = "Tugasmu adalah bertindak sebagai modul pembuat memori jangka panjang AI.
Analisis segmen percakapan terbaru di bawah dan gabungkan informasi barunya ke dalam daftar memori yang sudah ada.

Aturan Konsolidasi Memori:
1. Catat poin ingatan penting tentang: konsep materi yang sedang dipelajari, riwayat kesalahan pemahaman siswa, materi yang sudah berhasil dipahami siswa, preferensi belajar, atau kesimpulan diskusi penting.
2. Singkat, padat, dan gunakan format bullet points (contoh: '- Siswa sempat keliru mengira rumus X menggunakan Y, namun sekarang sudah paham bahwa Y adalah Z').
3. Gabungkan poin baru dengan memori lama agar tetap rapi, ringkas, dan tidak duplikat.
4. JANGAN catat hal-hal administratif yang tidak penting seperti sapaan, ucapan terima kasih, atau percakapan di luar materi.
5. Maksimal hasilkan 6-8 bullet points terpenting agar hemat ruang.

MEMORI LAMA:
{$currentMemory}

PERCAKAPAN TERBARU:
{$chatSegment}

Balas hanya dengan daftar bullet points memori terupdate dalam bahasa Indonesia. Jangan sertakan kalimat pembuka, penutup, atau tanda markdown code block.";

        try {
            $updatedMemory = trim($this->sendText($prompt, 1000, null, 'chat'));

            if (filled($updatedMemory) && !str_contains($updatedMemory, 'AI belum memberikan jawaban')) {
                if ($memoryRecord) {
                    $memoryRecord->update(['content' => $updatedMemory]);
                } else {
                    \App\Models\AiMemory::create([
                        'user_id' => $user->id,
                        'document_id' => $documentId,
                        'folder_id' => $folderId,
                        'content' => $updatedMemory,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function chatContext(Document|DocumentFolder $document, string $question, ?array $selectedDocIds = null): string
    {
        if ($document instanceof Document) {
            $text = $document->extracted_text ?: '';
            if (mb_strlen($text) < 500000) {
                return $text !== '' ? $text : 'Materi dokumen belum memiliki teks yang berhasil diekstrak.';
            }
            return $this->relevantContext($text, $question, 150000);
        }

        $combinedText = $document->combinedExtractedText($selectedDocIds);
        if (mb_strlen($combinedText) < 500000) {
            return $combinedText !== '' ? $combinedText : 'Materi folder belum memiliki teks yang berhasil diekstrak.';
        }

        $documents = $document->documentsForPrompt($selectedDocIds)
            ->filter(fn (Document $item) => filled($item->extracted_text));

        $documentCount = $documents->count();
        if ($documentCount === 0) {
            return 'Materi folder belum memiliki teks yang berhasil diekstrak.';
        }

        $totalLimit = 200000;
        $perDocumentLimit = (int) floor(($totalLimit - ($documentCount * 100)) / $documentCount);
        $perDocumentLimit = max(1000, $perDocumentLimit);

        $sections = $documents
            ->map(function (Document $item) use ($question, $perDocumentLimit) {
                $context = $this->relevantContext($item->extracted_text ?: '', $question, $perDocumentLimit);

                return "### {$item->title}\n{$context}";
            })
            ->implode("\n\n");

        return $sections !== ''
            ? Str::limit($sections, $totalLimit)
            : 'Materi folder belum memiliki teks yang berhasil diekstrak.';
    }

    private function generationContext(Document|DocumentFolder $document, string $task = 'default', ?array $selectedDocIds = null): string
    {
        $limit = match ($task) {
            'summary' => 500000,
            'quiz' => 400000,
            'flashcard' => 300000,
            default => 600000,
        };

        if ($document instanceof Document) {
            return $this->compactContext($document->extracted_text ?: 'Materi belum memiliki teks yang berhasil diekstrak.', $limit);
        }

        $documents = $document->documentsForPrompt($selectedDocIds)
            ->filter(fn (Document $item) => filled($item->extracted_text));

        $documentCount = $documents->count();
        if ($documentCount === 0) {
            return 'Materi folder belum memiliki teks yang berhasil diekstrak.';
        }

        $perDocumentLimit = (int) floor(($limit - ($documentCount * 100)) / $documentCount);
        $perDocumentLimit = max(1000, $perDocumentLimit);

        $sections = $documents
            ->map(fn (Document $item) => "### {$item->title}\n".$this->compactContext($item->extracted_text ?: '', $perDocumentLimit))
            ->implode("\n\n");

        return $sections !== ''
            ? Str::limit($sections, $limit)
            : 'Materi folder belum memiliki teks yang berhasil diekstrak.';
    }

    private function compactContext(string $context, int $limit): string
    {
        $context = trim($context);
        if ($context === '') {
            return '';
        }

        if (mb_strlen($context) <= $limit) {
            return $context;
        }

        $headLimit = (int) floor($limit * 0.68);
        $tailLimit = max(400, $limit - $headLimit - 80);

        return Str::limit($context, $headLimit, '')
            ."\n\n[bagian akhir materi]\n"
            .Str::limit(Str::substr($context, -$tailLimit), $tailLimit, '');
    }

    private function relevantContext(string $context, string $question, int $limit = 5000): string
    {
        $context = trim($context);
        if ($context === '') {
            return 'Materi belum memiliki teks yang berhasil diekstrak.';
        }

        $context = Str::limit($context, 400000, '');

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

    private function cleanModelText(string $text, bool $isJson = false): string
    {
        $text = preg_replace('/<think>.*?<\/think>/is', '', $text) ?: $text;
        $text = preg_replace('/^\s*(?:reasoning|pemikiran|proses berpikir)\s*:.*?(?=\n#{1,6}\s|\n[A-Z]|\z)/isu', '', $text) ?: $text;
        $text = $this->repairMojibake($text);
        
        if (!$isJson) {
            $text = preg_replace('/^#{1,6}\s*/m', '', $text) ?: $text;
            $text = preg_replace('/^\s*-{3,}\s*$/m', '', $text) ?: $text;
            $text = preg_replace('/```+/', '', $text) ?: $text;
            $text = preg_replace('/^\|[-:\s|]+\|\s*$/m', '', $text) ?: $text;
            $text = preg_replace('/^\|\s*(.*?)\s*\|\s*$/m', '$1', $text) ?: $text;
            $text = preg_replace('/\s*\|\s*/', ' | ', $text) ?: $text;
        }
        
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?: $text;

        return trim($text);
    }

    private function repairMojibake(string $text): string
    {
        $replacements = [
            'â€¢' => '-',
            'â†’' => '->',
            'â‡’' => '->',
            'â‰¥' => '>=',
            'â‰¤' => '<=',
            'âˆ’' => '-',
            'Ã—' => 'x',
            'Î£' => 'Sigma',
            'â€œ' => '"',
            'â€' => '"',
            'â€˜' => "'",
            'â€™' => "'",
            'â€”' => '-',
            'â€“' => '-',
            'ä¸‰å±‚' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
