<?php

namespace App\Services;

class SummaryLength
{
    public const POLICY = 'source-words-45-48-v1';

    public static function words(string $text): int
    {
        return preg_match_all('/[\pL\pN]+(?:[\x{2019}\x{0027}-][\pL\pN]+)*/u', $text) ?: 0;
    }

    public static function target(string $context): array
    {
        $words = self::words($context);
        return [
            'source_words' => $words,
            'min_words' => max(1, (int) ceil($words * 0.45)),
            'max_words' => max(1, (int) ceil($words * 0.48)),
        ];
    }
}
