<?php

namespace App\Services;

class QuickChatReply
{
    public static function answer(string $question, ?string $imagePath = null, array $materials = []): ?string
    {
        if ($imagePath !== null || $materials !== []) return null;

        // Match the whole message so extra questions and document instructions still reach AI.
        $question = mb_strtolower(trim($question));
        $question = preg_replace('/\s+/u', ' ', $question);
        $question = rtrim($question, " \t\n\r\0\x0B?!. ");
        if (in_array($question, ['halo', 'hai', 'hi', 'hello', 'helo'], true)) {
            return 'Halo! Mau ngobrolin apa?';
        }

        $now = now('Asia/Jakarta')->locale('id');
        if (preg_match('/^(?:(?:sekarang|hari ini) )?(?:tanggal|hari|bulan|tahun) (?:berapa|apa)(?: (?:sekarang|hari ini))?(?: (?:dan|bulan dan) tahun berapa)?(?: wib)?$/u', $question)) {
            return 'Sekarang '.$now->translatedFormat('l, j F Y').' (WIB).';
        }
        if (preg_match('/^(?:sekarang )?(?:jam|pukul) berapa(?: sekarang)?(?: wib)?$/u', $question)) {
            return 'Sekarang pukul '.$now->format('H:i').' WIB (Asia/Jakarta).';
        }

        return null;
    }

    public static function stream(string $answer): \Generator
    {
        yield $answer;
    }
}
