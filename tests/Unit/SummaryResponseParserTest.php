<?php

namespace Tests\Unit;

use App\Services\SummaryResponseParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SummaryResponseParserTest extends TestCase
{
    public function test_markdown_sections_preserve_full_body_and_wrapped_points(): void
    {
        $body = "### Pengertian\n\nAir menguap karena menerima energi panas.\n\n| Tahap | Hasil |\n| --- | --- |\n| Kondensasi | Awan |\n\nRumus: \\(E=mc^2\\).";
        $response = "Berikut ringkasan materi yang diminta.\n\n## Ringkasan lengkap\n\n{$body}\n\n## Poin penting\n- Energi panas memicu penguapan\n  dan memengaruhi perubahan wujud.\n- Uap air mengembun.\n\n## Kesimpulan\nAir mengalami perubahan wujud secara berulang.";
        $parsed = (new SummaryResponseParser)->parse($response);
        $this->assertSame($body, $parsed['full_summary']);
        $this->assertSame(["Energi panas memicu penguapan\ndan memengaruhi perubahan wujud.", 'Uap air mengembun.'], $parsed['key_points']);
        $this->assertSame('Air mengalami perubahan wujud secara berulang.', $parsed['conclusion']);
    }

    public function test_headings_inside_code_are_not_treated_as_sections(): void
    {
        $body = "Contoh kode berikut menjelaskan pembacaan teks.\n\n```python\ndef contoh():\n    return 1\n\n## Poin penting\n## Kesimpulan\n```\n\nPenjelasan setelah kode tetap utuh.";
        $parsed = (new SummaryResponseParser)->parse("## RINGKASAN LENGKAP\n{$body}\n## POIN PENTING\n1. Kode mempertahankan indentasi.\n2) Heading di dalam kode bukan pemisah.\n## KESIMPULAN\nFormat kode tetap dipertahankan.");
        $this->assertSame($body, $parsed['full_summary']);
        $this->assertCount(2, $parsed['key_points']);
        $this->assertSame('Format kode tetap dipertahankan.', $parsed['conclusion']);
    }

    public function test_only_final_summary_sections_are_extracted(): void
    {
        $body = "Pengantar konsep\n\n## Poin penting\nPembahasan awal yang masih bagian isi\n\n### Detail\nPenjelasan rinci mengenai hubungan kedua konsep.";
        $parsed = (new SummaryResponseParser)->parse("## Ringkasan lengkap\n{$body}\n\n## Poin penting\n- Poin penutup\n\n## Kesimpulan\nKesimpulan akhir");
        $this->assertSame($body, $parsed['full_summary']);
        $this->assertSame(['Poin penutup'], $parsed['key_points']);
    }

    public function test_trailing_points_dividers_are_removed_without_changing_body_or_code(): void
    {
        $body = "### Materi\nPenjelasan materi tetap utuh.\n\n---\n\n```text\n---\n```";
        foreach (['---', '***', '___', '- - -', '* * *', '_ _ _'] as $divider) {
            $parsed = (new SummaryResponseParser)->parse("## Ringkasan lengkap\n{$body}\n\n## Poin penting\n- Poin terakhir\n\n{$divider}\n\n## Kesimpulan\nPenjelasan selesai");
            $this->assertSame($body, $parsed['full_summary']);
            $this->assertSame(['Poin terakhir'], $parsed['key_points']);
        }

        $point = "Contoh pemisah:\n```text\n---\n```";
        $parsed = (new SummaryResponseParser)->parse("## Ringkasan lengkap\n{$body}\n\n## Poin penting\n- {$point}\n\n---\n\n## Kesimpulan\nPenjelasan selesai");
        $this->assertSame([$point], $parsed['key_points']);
    }

    public function test_optional_outer_fence_is_removed_without_damaging_inner_code(): void
    {
        $body = "Pembahasan kode\n\n```php\necho 'materi';\n```";
        $parsed = (new SummaryResponseParser)->parse("````markdown\n## Ringkasan lengkap\n{$body}\n## Poin penting\n- Kode ditampilkan utuh\n## Kesimpulan\nBaca kode sesuai konteks\n````");
        $this->assertSame($body, $parsed['full_summary']);
        $this->assertSame(['Kode ditampilkan utuh'], $parsed['key_points']);
    }

    public function test_valid_json_and_double_encoded_json_remain_compatible(): void
    {
        $json = json_encode([
            'full_summary' => "### Pengertian\nMateri lengkap dalam format lama.",
            'key_points' => [' Poin pertama ', ['nested' => 'bukan string'], 2, '', 'Poin kedua'],
            'conclusion' => ['invalid'], 'short_summary' => 'Inti materi',
        ], JSON_THROW_ON_ERROR);
        foreach ([$json, "```json\n{$json}\n```", json_encode($json, JSON_THROW_ON_ERROR)] as $response) {
            $parsed = (new SummaryResponseParser)->parse($response);
            $this->assertSame("### Pengertian\nMateri lengkap dalam format lama.", $parsed['full_summary']);
            $this->assertSame(['Poin pertama', 'Poin kedua'], $parsed['key_points']);
            $this->assertNull($parsed['conclusion']);
            $this->assertSame('Inti materi', $parsed['short_summary']);
        }
    }

    public function test_substantial_unstructured_markdown_is_not_discarded_or_given_invented_points(): void
    {
        $body = "### Perubahan wujud air\n\nAir menerima energi panas sehingga sebagian molekulnya berubah menjadi uap. Uap tersebut dapat mendingin dan berubah menjadi tetes air melalui proses kondensasi.";
        $parsed = (new SummaryResponseParser)->parse($body);
        $this->assertSame($body, $parsed['full_summary']);
        $this->assertSame([], $parsed['key_points']);
        $this->assertNull($parsed['conclusion']);
    }

    public function test_missing_optional_sections_do_not_discard_completed_body(): void
    {
        $parsed = (new SummaryResponseParser)->parse("## Ringkasan lengkap\nPembahasan materi yang tersedia.");
        $this->assertSame('Pembahasan materi yang tersedia.', $parsed['full_summary']);
        $this->assertSame([], $parsed['key_points']);
        $this->assertNull($parsed['conclusion']);
    }

    public function test_invalid_responses_fail_without_leaking_response_content(): void
    {
        foreach ([
            '', '   ', '{}', '[]', '{"full_summary":"Rahasia belum selesai',
            '{"full_summary":null}', '{"full_summary":""}',
            'Maaf, AI sedang sibuk. Silakan coba lagi beberapa saat lagi karena layanan tidak tersedia.',
            'Maaf, saya tidak bisa membuat ringkasan karena layanan sedang mengalami gangguan koneksi.',
            'Maaf, permintaan Anda tidak dapat diproses karena proses pembacaan materi mengalami masalah. Silakan coba lagi nanti.',
            'AI sedang mengalami gangguan dan permintaan ringkasan tidak dapat diproses untuk saat ini. Silakan coba kembali nanti.',
            '<!DOCTYPE html><html><body>504 Gateway Timeout</body></html>',
            '504 Gateway Timeout: request failed', 'Ringkasan singkat',
            "## Ringkasan lengkap\n\n## Poin penting\n- Satu\n## Kesimpulan\nKosong",
        ] as $response) {
            try {
                (new SummaryResponseParser)->parse($response);
                $this->fail('Invalid output must be rejected');
            } catch (RuntimeException $exception) {
                $this->assertSame('Jawaban AI belum berisi ringkasan lengkap. Coba buat ulang ringkasan', $exception->getMessage());
            }
        }
    }
}
