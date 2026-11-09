<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\ChatFileFormats;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html as WordHtml;

class ChatExportController extends Controller
{
    public function export(Request $request)
    {
        $data = $request->validate([
            'content'  => ['required', 'string', 'max:100000'],
            'format'   => ['required', 'string', 'in:'.implode(',', ChatFileFormats::supported())],
            'filename' => ['nullable', 'string', 'max:200'],
        ]);

        $content  = $data['content'];
        $format   = $data['format'];
        $filename = Str::slug($data['filename'] ?? 'jawaban-ai', '-') ?: 'jawaban-ai';

        return match ($format) {
            'md'   => $this->exportMarkdown($content, $filename),
            'pdf'  => $this->exportPdf($content, $filename),
            'docx' => $this->exportDocx($content, $filename),
            'csv'  => $this->exportCsv($content, $filename),
            default => $this->exportText($content, $filename, $format),
        };
    }

    private function exportMarkdown(string $content, string $filename)
    {
        return response($content)
            ->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.md\"");
    }

    private function exportCsv(string $content, string $filename)
    {
        return response("\xEF\xBB\xBF" . $content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }

    private function exportText(string $content, string $filename, string $format)
    {
        return response($content)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.{$format}\"")
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function exportPdf(string $content, string $filename)
    {
        $html = $this->markdownToHtml($content);

        $wrapped = <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; line-height: 1.6; color: #1e293b; padding: 20px; }
            h1 { font-size: 20px; margin-bottom: 8px; color: #0f172a; }
            h2 { font-size: 17px; margin-bottom: 6px; color: #1e293b; }
            h3 { font-size: 14px; margin-bottom: 4px; color: #334155; }
            p { margin-bottom: 8px; }
            ul, ol { margin-bottom: 8px; padding-left: 20px; }
            li { margin-bottom: 3px; }
            code { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 11px; font-family: 'DejaVu Sans Mono', monospace; }
            pre { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 10px; overflow-wrap: break-word; white-space: pre-wrap; }
            pre code { background: none; padding: 0; }
            blockquote { border-left: 3px solid #94a3b8; padding-left: 12px; color: #64748b; margin: 8px 0; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
            th, td { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: left; font-size: 11px; }
            th { background: #f1f5f9; font-weight: 600; }
            hr { border: none; border-top: 1px solid #e2e8f0; margin: 12px 0; }
            strong { font-weight: 600; }
        </style>
        </head>
        <body>{$html}</body>
        </html>
        HTML;

        $pdf = Pdf::loadHTML($wrapped)
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false);

        return $pdf->download("{$filename}.pdf");
    }

    private function exportDocx(string $content, string $filename)
    {
        $html = $this->markdownToHtml($content);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 720,
            'marginBottom' => 720,
            'marginLeft'   => 720,
            'marginRight'  => 720,
        ]);

        // PHPWord's Html parser needs well-formed HTML
        $safeHtml = '<body>' . $html . '</body>';
        try {
            WordHtml::addHtml($section, $safeHtml, false, false);
        } catch (\Throwable $e) {
            // Fallback: add as plain text paragraphs
            foreach (explode("\n", $content) as $line) {
                $section->addText(htmlspecialchars($line, ENT_QUOTES, 'UTF-8'));
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'chat_export_') . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile, "{$filename}.docx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Convert markdown to basic HTML for PDF/DOCX rendering.
     * Strips mermaid/chart code blocks since they can't render in static docs.
     */
    private function markdownToHtml(string $markdown): string
    {
        // Remove mermaid and chart code blocks (can't render in PDF/DOCX)
        $markdown = preg_replace('/```(?:mermaid|chart)\s*\n.*?\n```/s', '', $markdown);

        // Remove LaTeX delimiters (dompdf can't render them)
        $markdown = preg_replace('/\$\$(.*?)\$\$/s', '<em>$1</em>', $markdown);
        $markdown = preg_replace('/\$(.*?)\$/s', '<em>$1</em>', $markdown);

        // Headings
        $markdown = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $markdown);
        $markdown = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $markdown);
        $markdown = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $markdown);
        $markdown = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $markdown);
        $markdown = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $markdown);
        $markdown = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $markdown);

        // Code blocks
        $markdown = preg_replace('/```\w*\s*\n(.*?)\n```/s', '<pre><code>$1</code></pre>', $markdown);

        // Inline code
        $markdown = preg_replace('/`([^`]+)`/', '<code>$1</code>', $markdown);

        // Bold and italic
        $markdown = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $markdown);
        $markdown = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $markdown);
        $markdown = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $markdown);

        // Horizontal rule
        $markdown = preg_replace('/^---+$/m', '<hr>', $markdown);

        // Blockquotes
        $markdown = preg_replace('/^>\s+(.+)$/m', '<blockquote>$1</blockquote>', $markdown);

        // Tables (GFM)
        $markdown = preg_replace_callback('/(?:^\|.+\|$\n?)+/m', function ($match) {
            $rows = array_filter(explode("\n", trim($match[0])));
            if (count($rows) < 2) return $match[0];

            $html = '<table>';
            foreach ($rows as $i => $row) {
                $cells = array_map('trim', explode('|', trim($row, '|')));
                // Skip separator row
                if ($i === 1 && preg_match('/^[\s:|-]+$/', $row)) continue;
                $tag = $i === 0 ? 'th' : 'td';
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $html .= "<{$tag}>{$cell}</{$tag}>";
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
            return $html;
        }, $markdown);

        // Unordered lists
        $markdown = preg_replace_callback('/(?:^[-*+]\s+.+$\n?)+/m', function ($match) {
            $items = preg_split('/^[-*+]\s+/m', trim($match[0]));
            $items = array_filter($items);
            $html = '<ul>';
            foreach ($items as $item) {
                $html .= '<li>' . trim($item) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        }, $markdown);

        // Ordered lists
        $markdown = preg_replace_callback('/(?:^\d+\.\s+.+$\n?)+/m', function ($match) {
            $items = preg_split('/^\d+\.\s+/m', trim($match[0]));
            $items = array_filter($items);
            $html = '<ol>';
            foreach ($items as $item) {
                $html .= '<li>' . trim($item) . '</li>';
            }
            $html .= '</ol>';
            return $html;
        }, $markdown);

        // Links
        $markdown = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $markdown);

        // Paragraphs: wrap remaining loose text lines
        $lines = explode("\n", $markdown);
        $result = [];
        $inBlock = false;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^<(pre|table|ul|ol|blockquote|h[1-6]|hr|div)/', $trimmed)) {
                $inBlock = true;
            }
            if (preg_match('/<\/(pre|table|ul|ol|blockquote|div)>/', $trimmed)) {
                $inBlock = false;
                $result[] = $line;
                continue;
            }
            if (!$inBlock && $trimmed !== '' && !preg_match('/^</', $trimmed)) {
                $result[] = '<p>' . $trimmed . '</p>';
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }
}
