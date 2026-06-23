<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Http\Request;
$request = Request::create('http://localhost', 'GET');
$app->instance('request', $request);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Services\GeminiService;
use App\Models\DocumentFolder;

$folder = DocumentFolder::where('name', 'kalkulus')->first() ?: DocumentFolder::first();
$gemini = app(GeminiService::class);
$imagePath = storage_path('app/public/chat_images/77277785-3125-4a6f-980a-b83704874043.png');

echo "Image path: " . $imagePath . "\n";
echo "Exists: " . (file_exists($imagePath) ? 'yes' : 'no') . "\n";

$response = $gemini->chat($folder, "Jelaskan gambar ini", [], null, $imagePath);
echo "Response:\n" . $response . "\n";
