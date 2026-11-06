<?php

$project = dirname(__DIR__, 2);
$fixture = getenv('PAGINATION_FIXTURE_DIR');
if (! $fixture || ! str_starts_with(str_replace('\\', '/', $fixture), str_replace('\\', '/', $project).'/storage/framework/testing/pagination-')) {
    throw new RuntimeException('Invalid fixture directory');
}
foreach (['views', 'sessions'] as $dir) {
    if (! is_dir($fixture.'/'.$dir)) mkdir($fixture.'/'.$dir, 0777, true);
}
foreach (['APP_ENV' => 'testing', 'APP_URL' => 'http://127.0.0.1:8125', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $fixture.'/fixture.sqlite', 'VIEW_COMPILED_PATH' => $fixture.'/views'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require $project.'/vendor/autoload.php';
$app = require $project.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app['config']->set([
    'app.url' => 'http://127.0.0.1:8125', 'app.debug' => true,
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => $fixture.'/fixture.sqlite', 'database.connections.sqlite.url' => null,
    'session.driver' => 'file', 'session.files' => $fixture.'/sessions', 'session.cookie' => 'pagination_fixture', 'session.secure' => false, 'session.domain' => null,
    'cache.default' => 'array', 'view.compiled' => $fixture.'/views', 'logging.default' => 'single', 'logging.channels.single.path' => $fixture.'/fixture.log',
    'broadcasting.default' => 'null', 'broadcasting.connections.reverb.key' => '', 'queue.default' => 'sync', 'mail.default' => 'array',
]);
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8125');
Illuminate\Support\Facades\URL::forceScheme('http');

if (getenv('CHAT_MATERIAL_FIXTURE') === '1') {
    $app['config']->set([
        'filesystems.disks.local.root' => $fixture.'/uploads',
        'services.ai.provider' => '9router', 'services.ai.fallback_provider' => 'none',
        'services.nine_router.api_key' => 'fixture-key', 'services.nine_router.base_url' => 'https://router.test/v1',
        'services.nine_router.model' => 'fixture-model',
    ]);
    Illuminate\Support\Facades\Http::preventStrayRequests();
    Illuminate\Support\Facades\Http::fake(['router.test/*' => Illuminate\Support\Facades\Http::response(
        'data: {"choices":[{"delta":{"content":"Jawaban uji dari lampiran"},"finish_reason":"stop"}]}'."\n\n",
        200, ['Content-Type' => 'text/event-stream']
    )]);
}

if (PHP_SAPI === 'cli') {
    touch($fixture.'/fixture.sqlite');
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $user = App\Models\User::factory()->create(['name' => 'Pelajar Uji', 'role' => 'mahasiswa']);
    $source = null;
    for ($i = 0; $i < 12; $i++) {
        $document = App\Models\Document::create(['user_id' => $user->id, 'title' => 'Materi '.$i, 'original_name' => 'materi.pdf', 'file_name' => 'fixture.pdf', 'file_path' => 'documents/fixture.pdf', 'extension' => 'pdf', 'mime_type' => 'application/pdf', 'size' => 2048, 'status' => 'processed', 'extracted_text' => 'Teks materi uji']);
        $source ??= $document;
        App\Models\DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder '.$i]);
        $session = App\Models\ChatSession::create(['user_id' => $user->id, 'document_id' => $source->id, 'title' => 'Bahas materi '.$i]);
        $session->messages()->create(['role' => 'assistant', 'content' => 'Jawaban uji']);
        App\Models\ActivityLog::create(['user_id' => $user->id, 'action' => 'chat_document']);
        App\Models\Summary::create(['user_id' => $user->id, 'document_id' => $source->id, 'short_summary' => 'Ringkasan', 'full_summary' => 'Isi ringkasan', 'status' => 'completed']);
    }
    $emptyChat = App\Models\ChatSession::create(['user_id' => $user->id, 'document_id' => $source->id, 'title' => 'Tanya Materi '.$source->title]);
    for ($i = 1; $i <= 7; $i++) {
        App\Models\Flashcard::create(['user_id' => $user->id, 'document_id' => $source->id,
            'front' => 'Pertanyaan kartu '.$i, 'back' => 'Jawaban kartu '.$i, 'position' => $i]);
    }
    file_put_contents($fixture.'/data.json', json_encode(['user' => $user->id, 'document' => '/documents/'.$source->public_id, 'folder' => '/folders/'.App\Models\DocumentFolder::first()->public_id, 'chat' => parse_url(route('chat.show', $emptyChat), PHP_URL_PATH)]));
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($project.'/public'.$path)) return false;
$data = json_decode(file_get_contents($fixture.'/data.json'), true);
Illuminate\Support\Facades\Auth::setUser(App\Models\User::findOrFail($data['user']));
$app->handleRequest(Illuminate\Http\Request::capture());
