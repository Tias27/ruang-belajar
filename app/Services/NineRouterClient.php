<?php

namespace App\Services;

use Generator;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class NineRouterClient
{
    public function complete(string|array $prompt, int $maxTokens, bool $json = false, string $task = 'default'): string
    {
        $response = $this->request($prompt, $maxTokens, $json, $task, false);
        $content = $response->json('choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('9router tidak mengembalikan jawaban teks.');
        }

        return $content;
    }

    public function stream(string|array $prompt, int $maxTokens, string $task = 'chat', bool $json = false): Generator
    {
        $deadline = $this->monotonicSeconds() + $this->timeoutSeconds();
        $response = $this->request($prompt, $maxTokens, $json, $task, true);
        $body = $response->toPsrResponse()->getBody();
        if ($task === 'summary') $body = $this->summaryStream($body, $deadline);
        $buffer = '';
        $receivedText = false;
        $finished = false;

        try {
            while (!$body->eof()) {
                // Yield each SSE line without waiting for a 1024-byte block to fill.
                $buffer .= \GuzzleHttp\Psr7\Utils::readLine($body);
                while (($end = strpos($buffer, "\n")) !== false || ($body->eof() && $buffer !== '')) {
                    $line = $end === false ? $buffer : substr($buffer, 0, $end);
                    $buffer = $end === false ? '' : substr($buffer, $end + 1);
                    $line = trim($line);
                    if (!str_starts_with($line, 'data:')) continue;
                    $data = trim(substr($line, 5));
                    if ($data === '[DONE]') {
                        $finished = true;
                        break 2;
                    }
                    $event = json_decode($data, true);
                    if (!is_array($event) || isset($event['error'])) {
                        throw new RuntimeException('Respons streaming 9router tidak valid atau gagal.');
                    }
                    $text = data_get($event, 'choices.0.delta.content');
                    if (is_string($text) && $text !== '') {
                        $receivedText = true;
                        yield $text;
                    }
                    if (data_get($event, 'choices.0.finish_reason') !== null) {
                        if ($task === 'summary' && data_get($event, 'choices.0.finish_reason') !== 'stop') {
                            throw new RuntimeException('Ringkasan belum selesai dibuat oleh AI. Coba ringkas file terpilih dalam jumlah lebih kecil');
                        }
                        // The answer is complete; do not wait for a delayed [DONE] or socket close.
                        $finished = true;
                        break 2;
                    }
                }
            }
        } finally {
            $body->close();
        }

        if (!$receivedText || !$finished) {
            throw new RuntimeException('Jawaban streaming 9router kosong atau koneksi terputus sebelum selesai.');
        }
    }

    public function completeSummary(string|array $prompt, int $maxTokens): string
    {
        $answer = '';
        foreach ($this->stream($prompt, $maxTokens, 'summary') as $chunk) $answer .= $chunk;
        return $answer;
    }

    private function summaryStream(StreamInterface $body, float $deadline): StreamInterface
    {
        $resource = null;
        if ($body instanceof Stream && $body->getMetadata('wrapper_type') === 'http') {
            $resource = $body->detach();
            $body = Utils::streamFor($resource);
        }

        // StreamHandler applies per-read timeouts, so enforce a total deadline too.
        return FnStream::decorate($body, ['read' => function (int $length) use ($body, $deadline, $resource): string {
            $remaining = $deadline - $this->monotonicSeconds();
            if ($remaining <= 0) {
                throw new RuntimeException('Batas waktu pembuatan ringkasan habis sebelum jawaban selesai');
            }
            if (is_resource($resource)) {
                $seconds = (int) $remaining;
                stream_set_timeout($resource, $seconds, max(1, (int) (($remaining - $seconds) * 1000000)));
            }
            try {
                return $body->read($length);
            } catch (RuntimeException) {
                throw new RuntimeException('Koneksi ringkasan terputus atau batas waktu habis sebelum jawaban selesai');
            }
        }]);
    }

    protected function monotonicSeconds(): float
    {
        return hrtime(true) / 1000000000;
    }

    private function timeoutSeconds(): int
    {
        return max(5, min((int) config('services.nine_router.timeout', 300), 300));
    }

    private function request(string|array $prompt, int $maxTokens, bool $json, string $task, bool $stream): Response
    {
        $key = trim((string) config('services.nine_router.api_key'));
        $base = rtrim((string) config('services.nine_router.base_url'), '/');
        $model = trim((string) config('services.nine_router.model'));
        if ($key === '' || $model === '' || !filter_var($base, FILTER_VALIDATE_URL)
            || !in_array(parse_url($base, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('Konfigurasi API key, base URL, atau model 9router belum valid.');
        }

        $content = $prompt;
        if (is_array($prompt)) {
            $content = [];
            foreach ($prompt as $part) {
                if (isset($part['text'])) {
                    $content[] = ['type' => 'text', 'text' => $part['text']];
                } elseif (isset($part['inlineData'])) {
                    $image = $part['inlineData'];
                    $content[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$image['mimeType']};base64,{$image['data']}"]];
                }
            }
        }

        $chatStyle = $task === 'chat'
            ? ' Jawab secara alami, lengkap, dan mudah dipahami. Untuk permintaan penjelasan, kembangkan pembahasan dari pengertian sederhana ke bagian penting dan cara kerjanya. Jelaskan istilah teknis dengan bahasa sehari-hari. Terangkan hubungan dalam contoh, bukan sekadar menyebut daftar. Gunakan kalimat utuh dan paragraf yang mengalir; hindari gaya catatan pendek. Sesuaikan panjang serta format dengan kebutuhan pengguna, tanpa target jumlah kata atau susunan wajib. Hormati permintaan jawaban singkat dan jujur tentang ketidakpastian.'
            : '';

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'Kamu adalah RuangBelajar AI. Jawab dalam bahasa Indonesia. Jika konteks materi disediakan, gunakan sebagai acuan. Jangan tampilkan reasoning internal atau tag <think>.'.$chatStyle.($json ? ' Balas hanya JSON valid.' : '')],
                ['role' => 'user', 'content' => $content],
            ],
            'temperature' => $task === 'summary' ? 0.7 : 0.4,
            'max_tokens' => max(512, min($maxTokens, 8192)),
            'stream' => $stream,
        ];
        if ($json) $payload['response_format'] = ['type' => 'json_object'];

        $timeout = $this->timeoutSeconds();
        try {
            $response = Http::withToken($key)
                ->withHeaders([
                    'Accept' => $stream ? 'text/event-stream' : 'application/json',
                    // Keep gateway token savers from injecting terse response instructions.
                    'X-9Router-Token-Saver' => 'off',
                ])
                ->timeout($timeout)
                ->connectTimeout(max(1, min((int) config('services.nine_router.connect_timeout', 15), $timeout)))
                ->withOptions(['stream' => $stream, 'allow_redirects' => false])
                ->post($base.'/chat/completions', $payload);
        } catch (ConnectionException) {
            // Do not propagate request details containing credentials or study material.
            throw new RuntimeException('Koneksi ke 9router gagal. Periksa endpoint dan koneksi server.');
        }
        if (!$response->successful()) {
            throw new RuntimeException('9router gagal merespons (HTTP '.$response->status().'). Periksa API key, model, dan status provider di 9router.');
        }

        return $response;
    }
}
