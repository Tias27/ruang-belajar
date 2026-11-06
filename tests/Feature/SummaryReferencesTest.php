<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Services\LearningSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SummaryReferencesTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array',
        ]);
        return $app;
    }

    private function document(User $user, DocumentFolder $folder, string $text, string $title): Document
    {
        return Document::create([
            'user_id' => $user->id, 'folder_id' => $folder->id, 'title' => $title,
            'original_name' => 'reference.pdf', 'file_name' => 'reference.pdf',
            'file_path' => 'documents/reference-fixture.pdf', 'mime_type' => 'application/pdf',
            'extension' => 'pdf', 'size' => strlen($text), 'status' => 'processed', 'extracted_text' => $text,
        ]);
    }

    public function test_chat_references_only_use_selected_folder_files(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Sains']);
        $selected = $this->document($user, $folder, 'Fotosintesis berlangsung di kloroplas. Klorofil menangkap cahaya.', 'Biologi');
        $other = $this->document($user, $folder, 'Laut mengandung air asin dan garam terlarut.', 'Laut');
        $service = app(LearningSourceService::class);
        $this->assertSame([], $service->chatSnippetsFor($folder, 'Apa itu laut?', 'Laut mengandung air asin.', selectedDocIds: [$selected->public_id]));
        $refs = $service->chatSnippetsFor($folder, 'Apa itu laut?', 'Laut mengandung air asin.', selectedDocIds: [$other->public_id]);
        $this->assertSame([$other->id], array_column($refs, 'document_id'));
    }

    private function previousAlgorithm(LearningSourceService $service, Collection $documents, string $query, int $limit): array
    {
        $call = fn (string $method, ...$args) => (new \ReflectionMethod($service, $method))->invoke($service, ...$args);
        $keywords = $call('keywords', $query);
        $ranked = $documents->flatMap(function (Document $document) use ($call, $keywords) {
            if (! filled($document->extracted_text)) return [];
            return collect($call('chunks', $document->extracted_text))->map(function (string $chunk) use ($document, $call, $keywords) {
                $clean = $call('clean', $chunk);
                return [
                    'document_id' => $document->id, 'title' => $document->title,
                    'snippet' => $call('completeLimit', $clean, 900), 'score' => $call('score', $clean, $keywords),
                ];
            })->filter(fn ($item) => $item['score'] > 0);
        })->sortByDesc('score')->take($limit)->map(fn ($item) => [
            'document_id' => $item['document_id'], 'title' => $item['title'], 'snippet' => $item['snippet'],
        ])->values()->all();

        if ($ranked !== []) return $ranked;
        return $documents->filter(fn ($document) => filled($document->extracted_text))->take($limit)
            ->map(fn ($document) => [
                'document_id' => $document->id, 'title' => $document->title,
                'snippet' => $call('completeLimit', $call('clean', $document->extracted_text), 900),
            ])->values()->all();
    }

    public function test_reference_content_and_ranking_match_previous_algorithm_for_long_and_noisy_inputs(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Pengujian referensi']);
        $this->document($user, $folder, str_repeat('Klorofil menyerap cahaya matahari. Air dibutuhkan untuk fotosintesis. ', 160), 'Fotosintesis');
        $this->document($user, $folder, str_repeat('Proses fotosintesis: cahaya,air,dan karbon. BerbasisKomputer Menggunakanpembobotankriteria NormalisasiMatriks. ', 120), 'Glues');
        $this->document($user, $folder, str_repeat('Persamaan23ContohPerhitungan memiliki luas 42cm. Nilai x=2 dan AHPmemecahmasalahmenjadistrukturhierarki. ', 100), 'Rumus');
        $this->document($user, $folder, str_repeat('Tumbuhan ' . "α" . ' mengubah energi; pengukuran angka dilakukan berulang. ', 180), 'Unicode');
        $this->document($user, $folder, '', 'Tidak terbaca');
        $service = app(LearningSourceService::class);
        $documents = $folder->documents()->oldest()->get();
        foreach (['fotosintesis cahaya air tumbuhan', 'normalisasi kriteria bobot komputer', 'perhitungan struktur hierarki', 'pengukuran energi', 'tidakadaistilahini', 'yang dari pada untuk'] as $query) {
            foreach ([1, 3, 7] as $limit) {
                $expected = $this->previousAlgorithm($service, $documents, $query, $limit);
                $this->assertSame($expected, $service->snippetsFor($folder, $query, $limit), $query.' limit '.$limit);
            }
        }
    }

    public function test_selected_folder_references_never_include_unselected_files_even_for_fallback(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Pilihan']);
        $excluded = $this->document($user, $folder, 'Fotosintesis klorofil energi cahaya air tumbuhan.', 'Bukan pilihan');
        $selected = $this->document($user, $folder, 'Klorofil menyerap cahaya.', 'Pilihan');
        $service = app(LearningSourceService::class);
        foreach (['fotosintesis klorofil energi cahaya air tumbuhan', 'zoologi ekologi statistik'] as $query) {
            $actual = $service->snippetsFor($folder, $query, 3, [$selected->public_id]);
            $this->assertNotEmpty($actual);
            $this->assertSame([$selected->id], array_column($actual, 'document_id'));
            $this->assertNotContains($excluded->id, array_column($actual, 'document_id'));
            $this->assertSame($this->previousAlgorithm($service, collect([$selected]), $query, 3), $actual);
        }
        $this->assertSame($service->snippetsFor($folder, 'klorofil'), $service->snippetsFor($folder, 'klorofil', 3, []));
    }

    public function test_document_references_preserve_long_paragraph_boundary_and_stable_ties(): void
    {
        $service = app(LearningSourceService::class);
        $document = new Document(['title' => 'Teks', 'extracted_text' => str_repeat('Klorofil mengubah energi cahaya, ', 80)]);
        $document->id = 123;
        foreach (['klorofil energi', 'tidak cocok', ''] as $query) {
            $this->assertSame($this->previousAlgorithm($service, collect([$document]), $query, 3), $service->snippetsFor($document, $query));
        }
    }
}
