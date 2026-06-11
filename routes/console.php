<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Document;
use App\Services\DocumentTextExtractor;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('documents:reextract-pdf {--all : Proses semua PDF, termasuk yang sudah punya teks}', function (DocumentTextExtractor $extractor) {
    $query = Document::query()->where('extension', 'pdf');

    if (! $this->option('all')) {
        $query->where(function ($query) {
            $query->whereNull('extracted_text')->orWhere('extracted_text', '');
        });
    }

    $documents = $query->get();
    $this->info('Memproses '.$documents->count().' PDF...');

    foreach ($documents as $document) {
        $path = storage_path('app/private/'.$document->file_path);

        if (! is_file($path)) {
            $this->warn("Lewati {$document->id}: file tidak ditemukan.");
            continue;
        }

        $text = $extractor->extract($path, 'pdf');
        $document->update([
            'extracted_text' => $text,
            'status' => filled($text) ? 'processed' : 'uploaded',
            'processing_notes' => filled($text)
                ? null
                : 'Teks PDF belum dapat diekstrak. Jika PDF berupa hasil scan/gambar, perlu OCR.',
        ]);

        $this->line("PDF {$document->id}: ".mb_strlen($text).' karakter');

        unset($text);
        gc_collect_cycles();
    }

    $this->info('Selesai.');
})->purpose('Ekstrak ulang teks PDF yang sudah diunggah');
