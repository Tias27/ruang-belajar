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
    'services.tavily.enabled' => false,
]);
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8125');
Illuminate\Support\Facades\URL::forceScheme('http');
Illuminate\Support\Facades\Http::preventStrayRequests();

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

if (getenv('WEB_CHAT_FIXTURE') === '1') {
    $app['config']->set(['services.tavily.enabled' => true, 'services.tavily.api_key' => 'fixture-search-key', 'services.tavily.ca_bundle' => null,
        'services.ai.provider' => '9router', 'services.nine_router.api_key' => 'fixture-key',
        'services.nine_router.base_url' => 'https://router.test/v1', 'services.nine_router.model' => 'fixture-model']);
    Illuminate\Support\Facades\Http::fake([
        'router.test/*' => function ($request) {
            $hasResult = collect($request['messages'])->contains('role', 'tool');
            $delta = $hasResult ? ['content' => 'Kabar perlu dilihat sesuai status pemeriksaannya [1](https://example.org/berita).']
                : ['tool_calls' => [['index' => 0, 'id' => 'web-fixture', 'type' => 'function', 'function' => ['name' => 'search_web', 'arguments' => '{"query":"berita terkini"}']]]];
            return Illuminate\Support\Facades\Http::response('data: '.json_encode(['choices' => [['delta' => $delta, 'finish_reason' => $hasResult ? 'stop' : 'tool_calls']]])."\n\n");
        },
        'api.tavily.com/search' => Illuminate\Support\Facades\Http::response(['results' => [['url' => 'https://example.org/berita', 'title' => 'Laporan sumber uji', 'content' => 'Cuplikan berita', 'published_date' => '2026-11-05']]]),
        'api.tavily.com/extract' => Illuminate\Support\Facades\Http::response(['results' => [['url' => 'https://example.org/berita', 'raw_content' => 'Isi sumber uji']]]),
    ]);
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
    if (getenv('ATTACHMENT_FIXTURE') === '1') {
        $pdf = "%PDF-1.4\n";
        $stream = "BT /F1 18 Tf 50 150 Td (Preview file uji) Tj ET";
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 220] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', "<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream"];
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        Illuminate\Support\Facades\Storage::disk('local')->put('documents/fixture.pdf', $pdf);
        App\Models\Document::create(['user_id' => $user->id, 'title' => 'Preview DOCX', 'original_name' => 'preview.docx',
            'file_name' => 'preview.docx', 'file_path' => 'documents/preview.docx', 'extension' => 'docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'size' => 20, 'status' => 'processed', 'extracted_text' => 'Isi dokumen untuk preview <script>window.badPreview=true</script>']);
    }
    for ($i = 1; $i <= 7; $i++) {
        App\Models\Flashcard::create(['user_id' => $user->id, 'document_id' => $source->id,
            'front' => 'Pertanyaan kartu '.$i, 'back' => 'Jawaban kartu '.$i, 'position' => $i]);
    }
    $summaryStates = [];
    if (getenv('SUMMARY_FIXTURE') === '1') {
        foreach (['processing', 'failed'] as $status) {
            $summary = App\Models\Summary::create([
                'user_id' => $user->id, 'document_id' => $source->id,
                'short_summary' => 'Uji status', 'full_summary' => '', 'status' => $status,
            ]);
            $summaryStates[$status] = parse_url(route('summaries.show', $summary), PHP_URL_PATH);
        }
    }
    file_put_contents($fixture.'/data.json', json_encode(['user' => $user->id, 'document' => '/documents/'.$source->public_id, 'folder' => '/folders/'.App\Models\DocumentFolder::first()->public_id, 'chat' => parse_url(route('chat.show', $emptyChat), PHP_URL_PATH), 'summary_states' => $summaryStates]));
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($project.'/public'.$path)) return false;
$data = json_decode(file_get_contents($fixture.'/data.json'), true);
Illuminate\Support\Facades\Auth::setUser(App\Models\User::findOrFail($data['user']));
$app->handleRequest(Illuminate\Http\Request::capture());
