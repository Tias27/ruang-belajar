<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Document;
use App\Services\LearningSourceService;
use Tests\TestCase;

class ChatReferencesTest extends TestCase
{
    public function test_unrelated_material_and_generic_word_matches_produce_no_references(): void
    {
        $source = new Document(['title' => 'Biologi', 'extracted_text' => 'Fotosintesis menggunakan klorofil untuk menangkap cahaya. Ini adalah informasi penting dalam materi.']);
        $service = app(LearningSourceService::class);
        foreach ([
            ['Apa itu laut?', 'Laut adalah kumpulan air asin yang luas.'],
            ['Jelaskan informasi terbaru', 'Berikut informasi penting tentang harga emas.'],
            ['halo', 'Halo! Mau ngobrolin apa?'],
            ['Sekarang tanggal berapa?', 'Jumat, 6 November 2026.'],
        ] as [$question, $answer]) {
            $this->assertSame([], $service->chatSnippetsFor($source, $question, $answer));
        }
        $source->extracted_text = 'Seleksi dilakukan untuk memilih peserta lomba.';
        $this->assertSame([], $service->chatSnippetsFor($source, 'Apa itu sel?', 'Sel adalah unit kehidupan.'));
    }

    public function test_relevant_material_and_contextual_followup_still_get_references(): void
    {
        $text = 'Fotosintesis berlangsung dalam kloroplas. Klorofil menangkap cahaya untuk fotosintesis.';
        $source = (new Document(['title' => 'Biologi', 'extracted_text' => $text]))->forceFill(['id' => 21]);
        $service = app(LearningSourceService::class);
        foreach (['Bagaimana fotosintesis?', 'Lalu fungsinya?'] as $question) {
            $references = $service->chatSnippetsFor($source, $question, 'Klorofil menangkap cahaya dalam kloroplas.');
            $this->assertCount(1, $references);
            $this->assertSame(21, $references[0]['document_id']);
        }
        $this->assertSame($text, $source->extracted_text);
    }

    public function test_existing_chat_references_are_filtered_without_mutating_history_or_web_sources(): void
    {
        $metadata = ['source_snippets' => [['document_id' => 1, 'title' => 'Biologi', 'snippet' => 'Klorofil menangkap cahaya untuk fotosintesis.']],
            'web_sources' => [['title' => 'Berita', 'url' => 'https://example.org/news']]];
        $message = new ChatMessage(['role' => 'assistant', 'content' => 'Laut merupakan kumpulan air asin.', 'metadata' => $metadata]);
        $result = $message->forChat('Apa itu laut?');
        $this->assertSame([], $result['metadata']['source_snippets']);
        $this->assertSame($metadata['web_sources'], $result['metadata']['web_sources']);
        $this->assertSame($metadata, $message->metadata);
    }

    public function test_failed_ai_request_does_not_turn_unrelated_material_into_fallback_evidence(): void
    {
        $source = new Document(['title' => 'Biologi', 'extracted_text' => 'Klorofil menangkap cahaya untuk fotosintesis.']);
        $service = app(LearningSourceService::class);
        $answer = $service->fallbackAnswer($source, 'Apa itu laut?', 'timeout');
        $this->assertStringNotContainsString('Klorofil', $answer);
        $this->assertSame([], $service->chatSnippetsFor($source, 'Apa itu laut?', $answer));
    }
}
