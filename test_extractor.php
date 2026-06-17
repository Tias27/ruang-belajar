<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = glob(storage_path('app/private/documents/*.jpg'));
if (empty($files)) {
    echo "NO JPG FILES FOUND\n";
    exit;
}

$path = $files[0];
echo "Testing: " . $path . "\n";

$extractor = new \App\Services\DocumentTextExtractor();
$result = $extractor->extract($path, 'jpg');

echo "Result length: " . strlen($result) . "\n";
if (strlen($result) > 0) {
    echo substr($result, 0, 500) . "\n";
} else {
    echo "EMPTY RESULT\n";
}
