<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$document = App\Models\Document::find(77);
if (!$document) {
    die("Document not found\n");
}

echo "Document: " . $document->title . "\n";

$gemini = app(App\Services\GeminiService::class);

// Let's call jsonPrompt but print the raw text returned
// We can use Reflection to inspect/run textPrompt, or we can look at GeminiService.php
$prompt = "Buat ringkasan belajar yang sangat komprehensif, mendalam, sangat panjang, dan rapi dari materi ini dalam format JSON dengan kunci:
- full_summary: penjelasan materi secara sangat detail, menyeluruh, terstruktur, dan mendalam. Bahas semua topik, bab, atau sub-materi secara detail dan proporsional. Berikan penjelasan mendalam untuk setiap konsep penting.
  **Gunakan format Markdown yang kaya (seperti heading, bold, list bullet/numbering, tabel, blockquote, atau kode jika relevan) di dalam nilai string ini agar rapi saat ditampilkan.** 
- key_points: array of string, berisi poin-poin utama yang sangat penting (buat minimal 8-15 poin penting yang mencakup seluruh materi secara menyeluruh).
- conclusion: kesimpulan akhir yang merangkum keseluruhan pembelajaran secara holistik.

Aturan Penting:
1. Jangan membuat ringkasan yang terlalu singkat atau hanya garis besar saja. Pengguna membutuhkan penjelasan detail dari setiap konsep agar bisa dipelajari dengan baik.
2. Fokus ke penjabaran inti materi, penjelasan konsep, alasan, langkah-langkah, rumus (jika ada), dan insight penting.
3. Abaikan bagian tidak penting seperti cover, daftar isi, lampiran kosong, atau identitas tugas.
4. Pastikan response dalam format JSON valid.";

$prompt = $prompt . "\nBalas hanya JSON valid tanpa markdown.";

// Using Reflection to call sendText on GeminiService
$reflector = new ReflectionClass(App\Services\GeminiService::class);
$methodBuildPrompt = $reflector->getMethod('buildPrompt');
$methodBuildPrompt->setAccessible(true);
$builtPrompt = $methodBuildPrompt->invoke(
    $gemini,
    $document,
    $prompt,
    null, // question
    'summary', // task
    [], // history
    null // selectedDocIds
);

$methodSendText = $reflector->getMethod('sendText');
$methodSendText->setAccessible(true);

echo "Sending prompt...\n";
$rawText = $methodSendText->invoke($gemini, $builtPrompt, 7000, 'application/json', 'summary');

echo "RAW TEXT LENGTH: " . strlen($rawText) . "\n";
echo "--- RAW TEXT START ---\n";
echo $rawText . "\n";
echo "--- RAW TEXT END ---\n";

$extracted = $reflector->getMethod('extractJson')->invoke($gemini, $rawText);
echo "--- EXTRACTED JSON START ---\n";
echo $extracted . "\n";
echo "--- EXTRACTED JSON END ---\n";

$json = json_decode($extracted, true);
echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
if ($json === null) {
    echo "DECODE FAILED\n";
} else {
    echo "DECODE SUCCESSFUL\n";
}
