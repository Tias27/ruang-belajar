<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Services\GeminiService;
use App\Services\QuickChatReply;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuickChatReplyTest extends TestCase
{
    public function test_simple_messages_skip_network_and_material_preparation_in_both_chat_paths(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $service = app(GeminiService::class);
        $document = new Document(['title' => 'Materi', 'extracted_text' => 'Isi materi']);
        $this->travelTo(Carbon::parse('2026-11-05 17:05:00', 'UTC'));
        try {
            foreach ([null, $document] as $source) {
                foreach ([
                    ' HALO!! ' => 'Halo! Mau ngobrolin apa?',
                    'sekarang tanggal berapa?' => 'Sekarang Jumat, 6 November 2026 (WIB).',
                    'sekarang tanggal berapa dan tahun berapa' => 'Sekarang Jumat, 6 November 2026 (WIB).',
                    'jam berapa sekarang' => 'Sekarang pukul 00:05 WIB (Asia/Jakarta).',
                ] as $question => $answer) {
                    $this->assertSame($answer, $service->chat($source, $question));
                    $this->assertSame($answer, implode('', iterator_to_array($service->streamChat($source, $question))));
                }
            }
            $this->travelTo(Carbon::parse('2026-11-06 17:06:00', 'UTC'));
            $this->assertSame('Sekarang Sabtu, 7 November 2026 (WIB).', $service->chat(null, 'hari ini tanggal berapa'));
            $this->assertSame('Sekarang pukul 00:06 WIB (Asia/Jakarta).', $service->chat(null, 'jam berapa'));
            Http::assertNothingSent();
        } finally {
            $this->travelBack();
        }
    }

    public function test_contextual_questions_and_attachments_are_not_intercepted(): void
    {
        foreach ([
            'Halo, jelaskan karhutla',
            'Apa arti kata halo?',
            'Jam berapa sekarang di Tokyo?',
            'Tanggal berapa kejadian dalam materi?',
            'Sekarang tanggal berapa? Jelaskan UTC juga',
            'Jelaskan cara kerja jam',
            'Besok tanggal berapa?',
        ] as $question) {
            $this->assertNull(QuickChatReply::answer($question));
        }
        $this->assertNull(QuickChatReply::answer('halo', '/tmp/image.png'));
        $this->assertNull(QuickChatReply::answer('tanggal berapa sekarang', materials: [new Document]));
    }
}
