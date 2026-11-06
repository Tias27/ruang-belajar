<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TavilyClient
{
    public function enabled(): bool
    {
        return (bool) config('services.tavily.enabled') && filled(config('services.tavily.api_key'));
    }

    public static function publicUrl(mixed $url): bool
    {
        if (! is_string($url) || strlen($url) > 2000 || ! filter_var($url, FILTER_VALIDATE_URL)) return false;
        $parts = parse_url($url);
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        if (! in_array($parts['scheme'] ?? '', ['https', 'http'], true) || isset($parts['user']) || isset($parts['pass'])) return false;
        if (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true)) return false;
        if (! str_contains($host, '.') || preg_match('/(?:^|\.)(?:localhost|local|internal|test|invalid)$/', $host)) return false;
        if (filter_var($host, FILTER_VALIDATE_IP)) return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        return ! preg_match('/^[\d.]+$/', $host);
    }

    public function search(string $query, ?string $timeRange = null): array
    {
        $query = trim($query);
        if ($query === '' || mb_strlen($query) > 400) return ['error' => 'Kata pencarian tidak valid', 'sources' => []];
        $payload = ['query' => $query, 'search_depth' => 'basic', 'max_results' => 5,
            'include_answer' => false, 'include_raw_content' => false, 'include_published_date' => true];
        if (in_array($timeRange, ['day', 'week', 'month', 'year'], true)) $payload['time_range'] = $timeRange;
        $data = $this->request('search', $payload);
        if ($data === null) return ['error' => 'Pencarian web belum bisa diakses. Informasi terbaru belum terverifikasi.', 'sources' => []];
        $sources = [];
        foreach (array_slice($data['results'] ?? [], 0, 5) as $item) {
            if (! self::publicUrl($item['url'] ?? null) || isset($sources[$item['url']])) continue;
            $sources[$item['url']] = [
                'url' => $item['url'], 'title' => mb_substr(strip_tags((string) ($item['title'] ?? 'Sumber web')), 0, 250),
                'content' => mb_substr((string) ($item['content'] ?? ''), 0, 3000),
                'published_at' => is_string($item['published_date'] ?? null) ? mb_substr($item['published_date'], 0, 80) : null,
                'retrieved_at' => now('Asia/Jakarta')->toIso8601String(), 'read' => false,
            ];
        }
        return ['sources' => array_values($sources)];
    }

    public function extract(array $urls): array
    {
        $urls = array_values(array_unique(array_filter(array_slice($urls, 0, 3), self::publicUrl(...))));
        if ($urls === []) return ['error' => 'Tautan publik tidak valid', 'sources' => []];
        $data = $this->request('extract', ['urls' => $urls, 'extract_depth' => 'basic', 'format' => 'text', 'timeout' => 8]);
        if ($data === null) return ['error' => 'Isi halaman belum bisa dibaca', 'sources' => []];
        $sources = [];
        foreach (array_slice($data['results'] ?? [], 0, 3) as $item) {
            if (! self::publicUrl($item['url'] ?? null) || ! in_array($item['url'], $urls, true) || blank($item['raw_content'] ?? null)) continue;
            $sources[] = ['url' => $item['url'], 'title' => mb_substr(strip_tags((string) ($item['title'] ?? parse_url($item['url'], PHP_URL_HOST))), 0, 250),
                'content' => mb_substr((string) $item['raw_content'], 0, 9000), 'published_at' => null,
                'retrieved_at' => now('Asia/Jakarta')->toIso8601String(), 'read' => true];
        }
        return ['sources' => $sources];
    }

    private function request(string $endpoint, array $payload): ?array
    {
        if (! $this->enabled()) return null;
        $ca = config('services.tavily.ca_bundle');
        $verify = $ca ? (is_file($ca) ? $ca : base_path($ca)) : true;
        try {
            $response = Http::withToken(config('services.tavily.api_key'))->acceptJson()
                ->connectTimeout(5)->timeout(max(5, min(20, config('services.tavily.timeout', 15))))
                ->withOptions(['verify' => $verify, 'allow_redirects' => false])->post('https://api.tavily.com/'.$endpoint, $payload);
            if (! $response->successful()) {
                Log::warning('Web lookup unavailable', ['status' => $response->status()]);
                return null;
            }
            $data = $response->json();
            return is_array($data) && is_array($data['results'] ?? null) ? $data : null;
        } catch (Throwable) {
            // HTTP exceptions can contain credentials or query text; never log their payload.
            Log::warning('Web lookup connection unavailable');
            return null;
        }
    }
}
