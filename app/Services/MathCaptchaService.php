<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class MathCaptchaService
{
    const SESSION_KEY = 'math_captcha_answer';
    const SESSION_ATTEMPTS = 'math_captcha_attempts';
    const MAX_ATTEMPTS = 5;

    /**
     * Generate a new math captcha question and store answer in session.
     */
    public static function generate(): array
    {
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        $operator = ['+', '-', '*'][random_int(0, 2)];

        // Ensure subtraction doesn't go negative
        if ($operator === '-' && $num1 < $num2) {
            [$num1, $num2] = [$num2, $num1];
        }

        $answer = match ($operator) {
            '+' => $num1 + $num2,
            '-' => $num1 - $num2,
            '*' => $num1 * $num2,
        };

        Session::put(self::SESSION_KEY, $answer);
        Session::put(self::SESSION_ATTEMPTS, 0);

        return [
            'num1' => $num1,
            'num2' => $num2,
            'operator' => $operator,
            'question' => "Berapa {$num1} {$operator} {$num2}?",
        ];
    }

    /**
     * Get current captcha question (regenerate if not exists).
     */
    public static function getQuestion(): array
    {
        if (!Session::has(self::SESSION_KEY)) {
            return self::generate();
        }

        $answer = Session::get(self::SESSION_KEY);
        // We need to reconstruct the question - simpler to just regenerate
        return self::generate();
    }

    /**
     * Validate user's answer against session.
     */
    public static function validate(int $userAnswer): bool
    {
        $correctAnswer = Session::get(self::SESSION_KEY);
        $attempts = Session::get(self::SESSION_ATTEMPTS, 0);

        if ($correctAnswer === null) {
            return false;
        }

        $attempts++;
        Session::put(self::SESSION_ATTEMPTS, $attempts);

        $isValid = $userAnswer === $correctAnswer;

        if ($isValid) {
            self::clear();
        } elseif ($attempts >= self::MAX_ATTEMPTS) {
            self::clear();
        }

        return $isValid;
    }

    /**
     * Clear captcha from session.
     */
    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::SESSION_ATTEMPTS);
    }

    /**
     * Get remaining attempts.
     */
    public static function remainingAttempts(): int
    {
        $attempts = Session::get(self::SESSION_ATTEMPTS, 0);
        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    /**
     * Check if captcha is locked (too many attempts).
     */
    public static function isLocked(): bool
    {
        return self::remainingAttempts() === 0;
    }
}