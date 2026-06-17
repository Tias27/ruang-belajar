<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use ZipArchive;

class DocumentTextExtractor
{
    public function extract(UploadedFile|string $file, string $extension): string
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = strtolower($extension);

        return match ($extension) {
            'docx' => $this->extractOfficeXml($path, ['word/document.xml']),
            'pptx' => $this->extractOfficeXml($path, ['ppt/slides/slide']),
            'pdf'  => $this->extractPdf($path),
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp' => $this->extractImage($path, $extension),
            default => '',
        };
    }

    private function extractImage(string $path, string $extension): string
    {
        if (! class_exists(\thiagoalessio\TesseractOCR\TesseractOCR::class)) {
            return '';
        }

        try {
            $ocr = new \thiagoalessio\TesseractOCR\TesseractOCR($path);
            $ocr->lang('ind', 'eng');
            
            $text = $ocr->run();
            return $this->clean($text);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Tesseract OCR Error: " . $e->getMessage());
            return '';
        }
    }

    private function extractOfficeXml(string $path, array $targets): string
    {
        if (! class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }

        $text = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            foreach ($targets as $target) {
                if (str_starts_with($name, $target) && str_ends_with($name, '.xml')) {
                    $xml = $zip->getFromIndex($i) ?: '';
                    $text .= ' '.html_entity_decode(strip_tags(str_replace(['</w:p>', '</a:p>'], "\n", $xml)));
                }
            }
        }

        $zip->close();

        return $this->clean($text);
    }

    private function extractPdf(string $path): string
    {
        $this->raisePdfLimits();

        $parsedText = $this->extractPdfWithParser($path);
        if ($parsedText !== '') {
            return $parsedText;
        }

        $content = @file_get_contents($path);
        if (! $content) {
            return '';
        }

        preg_match_all('/\(([^\\\)]*(?:\\.[^\\\)]*)*)\)/', $content, $matches);

        $chunks = collect($matches[1] ?? [])
            ->map(fn (string $chunk) => stripcslashes($chunk))
            ->map(fn (string $chunk) => $this->toUtf8($chunk))
            ->filter(fn (string $chunk) => $this->looksReadable($chunk))
            ->all();

        return $this->clean(implode(' ', $chunks));
    }

    private function raisePdfLimits(): void
    {
        @ini_set('memory_limit', '512M');

        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
    }

    private function extractPdfWithParser(string $path): string
    {
        if (! class_exists(Parser::class)) {
            return '';
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);

            return $this->clean($pdf->getText());
        } catch (\Throwable $exception) {
            report($exception);

            return '';
        }
    }

    private function clean(string $text): string
    {
        $text = $this->toUtf8($text);
        $text = str_replace(['❖', '▪', '●', '•', '◦', '✓', '✔'], "\n", $text);
        $text = preg_replace('/([a-z])([A-Z])/u', '$1 $2', $text) ?: $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?: '';
        $text = preg_replace('/[^\P{C}\r\n\t]+/u', ' ', $text) ?: '';
        $text = preg_replace("/[ \t]+/", ' ', $text) ?: '';
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?: '';

        return trim(str_replace(['\r', '\t'], ' ', $text));
    }

    private function toUtf8(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_scrub')) {
            $text = mb_scrub($text, 'UTF-8');
        } elseif (! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return $converted === false ? '' : $converted;
    }

    private function looksReadable(string $text): bool
    {
        $text = trim($text);

        if (mb_strlen($text) < 2) {
            return false;
        }

        $printable = preg_replace('/[^\pL\pN\pP\pZs]/u', '', $text) ?: '';

        return mb_strlen($printable) / max(1, mb_strlen($text)) > 0.75;
    }
}
