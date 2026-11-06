<?php

namespace App\Services;

use RuntimeException;

class SummaryResponseParser
{
    public function parse(string $response): array
    {
        $text = $this->unwrapFence(trim($response));
        for ($depth = 0; $depth < 2 && str_starts_with($text, '"'); $depth++) {
            $decoded = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_string($decoded)) break;
            $text = $this->unwrapFence(trim($decoded));
        }

        if (str_starts_with($text, '{') || str_starts_with($text, '[')) {
            $json = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)
                || ! is_string($json['full_summary'] ?? null)) $this->invalid();
            $body = trim($json['full_summary']);
            $this->validateBody($body);
            $result = [
                'full_summary' => $body,
                'key_points' => array_values(array_filter(array_map(
                    fn ($point) => is_string($point) ? trim($point) : '',
                    is_array($json['key_points'] ?? null) ? $json['key_points'] : [],
                ), fn ($point) => $point !== '')),
                'conclusion' => is_string($json['conclusion'] ?? null) ? trim($json['conclusion']) : null,
            ];
            if (is_string($json['short_summary'] ?? null)) $result['short_summary'] = trim($json['short_summary']);
            return $result;
        }

        $sections = $this->sections($text);
        $start = $sections['body']['end'] ?? 0;
        $points = $sections['points'] ?? null;
        $conclusion = $sections['conclusion'] ?? null;
        if ($points && $points['start'] < $start) $points = null;
        if ($conclusion && $conclusion['start'] < ($points['end'] ?? $start)) $conclusion = null;
        $end = $points['start'] ?? $conclusion['start'] ?? strlen($text);
        $body = trim(substr($text, $start, $end - $start));
        $this->validateBody($body, $sections === []);

        return [
            'full_summary' => $body,
            'key_points' => $points ? $this->points(substr($text, $points['end'], ($conclusion['start'] ?? strlen($text)) - $points['end'])) : [],
            'conclusion' => $conclusion ? trim(substr($text, $conclusion['end'])) : null,
        ];
    }

    private function unwrapFence(string $text): string
    {
        if (! preg_match('/\A(`{3,}|~{3,})(?:markdown|md|json)?[ \t]*\R/i', $text, $opening)) return $text;
        $character = preg_quote($opening[1][0], '/');
        $length = strlen($opening[1]);
        if (! preg_match('/\R'.$character.'{'.$length.',}[ \t]*\z/', $text, $closing, PREG_OFFSET_CAPTURE)) return $text;
        return trim(substr($text, strlen($opening[0]), $closing[0][1] - strlen($opening[0])));
    }

    private function sections(string $text): array
    {
        $sections = [];
        $offset = 0;
        $fence = null;
        foreach (preg_split('/(?<=\n)/', $text) as $line) {
            $plain = rtrim($line, "\r\n");
            if (preg_match('/^ {0,3}(`{3,}|~{3,})(.*)$/', $plain, $marker)) {
                if ($fence === null) $fence = $marker[1];
                elseif ($marker[1][0] === $fence[0] && strlen($marker[1]) >= strlen($fence) && trim($marker[2]) === '') $fence = null;
            } elseif ($fence === null && preg_match('/^ {0,3}##[ \t]+(Ringkasan lengkap|Poin penting|Kesimpulan)[ \t]*#*[ \t]*$/i', $plain, $heading)) {
                $name = match (strtolower($heading[1])) {
                    'ringkasan lengkap' => 'body', 'poin penting' => 'points', 'kesimpulan' => 'conclusion',
                };
                // The body starts at the first header; trailing summary sections use their final header.
                if ($name !== 'body' || ! isset($sections['body'])) {
                    $sections[$name] = ['start' => $offset, 'end' => $offset + strlen($line)];
                }
            }
            $offset += strlen($line);
        }
        return $sections;
    }

    private function points(string $text): array
    {
        $lines = preg_split('/\R/', trim($text));
        // A divider between sections does not belong to the final bullet.
        while ($lines !== [] && preg_match('/^(?:[ \t]*| {0,3}(?:(?:-[ \t]*){3,}|(?:\*[ \t]*){3,}|(?:_[ \t]*){3,}))$/', end($lines))) {
            array_pop($lines);
        }
        $points = [];
        $current = '';
        $listed = false;
        foreach ($lines as $line) {
            if (preg_match('/^ {0,3}(?:[-+*]|\d+[.)])[ \t]+(.*)$/', $line, $item)) {
                if (trim($current) !== '') $points[] = trim($current);
                $current = $item[1];
                $listed = true;
            } elseif (trim($line) === '' && ! $listed) {
                if (trim($current) !== '') $points[] = trim($current);
                $current = '';
            } else {
                $current .= ($current === '' ? '' : "\n").trim($line);
            }
        }
        if (trim($current) !== '') $points[] = trim($current);
        return $points;
    }

    private function validateBody(string $body, bool $unstructured = false): void
    {
        if ($body === '' || preg_match('/\A\s*(?:<!doctype\s+html|<(?:html|head|body)\b)/i', $body)
            || preg_match('/\A\s*(?:#{1,6}\s*)?(?:maaf|mohon maaf|sorry|I apologize|I am sorry)\b/iu', $body)
            || preg_match('/\A\s*(?:#{1,6}\s*)?(?:(?:maaf|mohon maaf|sorry)[\s,.:!-]*)?(?:(?:saya|aku|kami|AI|layanan(?: AI)?)\s+(?:saat ini\s+)?(?:belum|tidak|sedang)\s+(?:dapat|bisa|sibuk|tersedia)|(?:layanan|server|service)\s+(?:unavailable|busy)|(?:502|503|504)\b|gateway timeout)/iu', $body)
            || preg_match('/\A\s*(?:I (?:cannot|can\x27t|am unable to)|(?:AI|layanan|server)\s+(?:sedang\s+)?mengalami\s+(?:gangguan|masalah)|Sebagai (?:AI|model bahasa)[^\n]{0,100}(?:tidak|belum) bisa)/iu', $body)) $this->invalid();

        if ($unstructured) {
            preg_match_all('/[\pL\pN]+/u', $body, $words);
            if (mb_strlen($body) < 80 || count($words[0]) < 10) $this->invalid();
        }
    }

    private function invalid(): never
    {
        throw new RuntimeException('Jawaban AI belum berisi ringkasan lengkap. Coba buat ulang ringkasan');
    }
}
