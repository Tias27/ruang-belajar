<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ChatSession;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Quiz;
use App\Models\StudyRoom;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationUiTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'view.compiled' => storage_path('framework/views'),
        ]);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_dashboard_pages_every_material_without_changing_latest_material(): void
    {
        $user = $this->student();
        $owned = collect();
        for ($i = 0; $i < 7; $i++) {
            $owned->push($this->document($user, 'Materi '.$i)->title);
        }
        for ($i = 0; $i < 6; $i++) {
            $owned->push(DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder '.$i])->title);
        }
        $this->document($this->student(), 'Materi akun lain');
        for ($i = 0; $i < 9; $i++) {
            ActivityLog::create(['user_id' => $user->id, 'action' => 'upload_document']);
        }

        $this->actingAs($user);
        $seen = collect();
        $latest = null;
        for ($page = 1; $page <= 7; $page++) {
            $response = $this->get(route('student.dashboard', ['materials_page' => $page, 'activity_page' => 2]))->assertOk();
            $materials = $response->viewData('recentMaterials');
            $this->assertSame(2, $materials->perPage());
            $this->assertSame(13, $materials->total());
            $this->assertCount($page === 7 ? 1 : 2, $materials);
            $latest ??= $response->viewData('latestMaterial');
            $this->assertEquals($latest, $response->viewData('latestMaterial'));
            $this->assertCount(4, $response->viewData('activities'));
            $this->assertStringContainsString('activity_page=2', $materials->url(1));
            $seen = $seen->merge($materials->pluck('title'));
            $response->assertDontSee('pagination.next')->assertDontSee('pagination.previous')->assertDontSee('>ruangbelajar.<', false);
            $this->assertPagerRegions($response->getContent());
        }
        $this->assertCount(13, $seen->unique());
        $this->assertEqualsCanonicalizing($owned->all(), $seen->all());
        $this->get(route('student.dashboard', ['activity_page' => 3]))->assertViewHas('activities', fn ($items) => $items->count() === 1 && ! $items->hasMorePages());
    }

    public function test_library_pages_files_and_folders_independently_and_preserves_search(): void
    {
        $user = $this->student();
        for ($i = 0; $i < 7; $i++) {
            $this->document($user, 'Biologi '.$i);
            DocumentFolder::create(['user_id' => $user->id, 'name' => 'Biologi folder '.$i]);
        }
        $this->document($user, 'Statistika');
        $response = $this->actingAs($user)->get(route('documents.index', ['q' => 'Biologi', 'page' => 2, 'folders_page' => 1]))->assertOk();
        $this->assertCount(2, $response->viewData('documents'));
        $this->assertCount(5, $response->viewData('folders'));
        $this->assertSame(7, $response->viewData('documents')->total());
        $this->assertStringContainsString('q=Biologi', $response->viewData('folders')->nextPageUrl());
        $this->assertStringContainsString('page=2', $response->viewData('folders')->nextPageUrl());
        $response->assertSee('Sebelumnya')->assertSee('Berikutnya')->assertDontSee('pagination.next');
        $this->assertPagerRegions($response->getContent());
    }

    public function test_chat_history_has_five_entries_per_page_and_keeps_search(): void
    {
        $user = $this->student();
        $document = $this->document($user, 'Sumber');
        for ($i = 0; $i < 11; $i++) {
            $session = ChatSession::create(['user_id' => $user->id, 'document_id' => $document->id, 'title' => 'Bahas biologi '.$i]);
            $session->messages()->create(['role' => 'assistant', 'content' => '**Alveolus** membantu pertukaran gas']);
        }
        $this->actingAs($user);
        $seen = collect();
        foreach ([1 => 5, 2 => 5, 3 => 1] as $page => $count) {
            $response = $this->get(route('chat.index', ['q' => 'biologi', 'page' => $page]))->assertOk();
            $sessions = $response->viewData('sessions');
            $this->assertCount($count, $sessions);
            $this->assertSame(11, $sessions->total());
            $this->assertStringContainsString('q=biologi', $sessions->url(1));
            $seen = $seen->merge($sessions->pluck('id'));
            $response->assertDontSee('**Alveolus**')->assertSee('Alveolus');
            $this->assertPagerRegions($response->getContent());
        }
        $this->assertCount(11, $seen->unique());
    }

    public function test_room_materials_and_active_rooms_are_paged_but_upload_keeps_all_folders(): void
    {
        $user = $this->student();
        $host = $this->student();
        for ($i = 0; $i < 7; $i++) {
            DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder '.$i]);
            $this->document($user, 'File '.$i);
            $room = StudyRoom::create(['host_id' => $host->id, 'target_type' => Document::class, 'target_id' => $this->document($host, 'Materi bersama '.$i)->id, 'pin' => str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'status' => 'active']);
            $room->users()->attach($user);
        }
        $response = $this->actingAs($user)->get(route('study-rooms.index', ['materials_page' => 3, 'rooms_page' => 2]))->assertOk();
        $this->assertCount(4, $response->viewData('materials'));
        $this->assertSame(14, $response->viewData('materials')->total());
        $this->assertCount(2, $response->viewData('activeRooms'));
        $this->assertCount(7, $response->viewData('allFolders'));
        $this->assertStringContainsString('rooms_page=2', $response->viewData('materials')->previousPageUrl());
        $this->assertPagerRegions($response->getContent());
    }

    public function test_learning_history_paginates_for_both_files_and_folders(): void
    {
        $user = $this->student();
        $document = $this->document($user, 'File sejarah');
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder sejarah']);
        $this->actingAs($user);
        foreach ([$document, $folder] as $source) {
            $key = $source instanceof DocumentFolder ? 'folder_id' : 'document_id';
            for ($i = 0; $i < 7; $i++) {
                Summary::create(['user_id' => $user->id, $key => $source->id, 'short_summary' => 'Ringkasan', 'full_summary' => 'Isi ringkasan', 'status' => 'completed']);
                Quiz::create(['user_id' => $user->id, $key => $source->id, 'title' => 'Kuis '.$i, 'question_count' => 1, 'status' => 'completed']);
                ChatSession::create(['user_id' => $user->id, $key => $source->id, 'title' => 'Percakapan '.$i]);
            }
            $response = $this->get(route($key === 'folder_id' ? 'folders.show' : 'documents.show', [$source, 'summary_page' => 2, 'quiz_page' => 2, 'chat_page' => 2]))->assertOk();
            foreach (['summaryHistory', 'quizHistory', 'chatHistory'] as $history) {
                $this->assertCount(2, $response->viewData($history));
                $this->assertSame(7, $response->viewData($history)->total());
            }
            $response->assertSee('Sebelumnya')->assertDontSee('pagination.previous');
            $this->assertPagerRegions($response->getContent());
        }
    }

    public function test_async_requests_keep_search_and_require_the_same_account_access(): void
    {
        $user = $this->student();
        $document = $this->document($user, 'Materi privat');
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'text/html'])
            ->get(route('documents.index', ['page' => 2]))->assertRedirect(route('login'));
        $this->actingAs($this->student())->get(route('documents.show', $document))->assertForbidden();

        $this->actingAs($user);
        for ($i = 0; $i < 6; $i++) $this->document($user, 'Biologi '.$i);
        $response = $this->get(route('documents.index', ['q' => 'Biologi', 'page' => 2]))->assertOk();
        $this->assertCount(1, $response->viewData('documents'));
        $response->assertDontSee('Materi privat')->assertSee('data-pagination-region="page"', false);
        $this->assertPagerRegions($response->getContent());
    }

    private function assertPagerRegions(string $html): void
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        foreach ($xpath->query('//*[@data-page-name]') as $pager) {
            $region = $pager->parentNode;
            while ($region instanceof \DOMElement && ! $region->hasAttribute('data-pagination-region')) {
                $region = $region->parentNode;
            }
            $this->assertInstanceOf(\DOMElement::class, $region);
            $this->assertSame($pager->getAttribute('data-page-name'), $region->getAttribute('data-pagination-region'));
            $this->assertSame(0, $region->getElementsByTagName('script')->length);
        }
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'mahasiswa']);
    }

    private function document(User $user, string $title): Document
    {
        return Document::create(['user_id' => $user->id, 'title' => $title, 'original_name' => $title.'.pdf', 'file_name' => 'fixture.pdf', 'file_path' => 'documents/fixture.pdf', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 2048, 'status' => 'processed', 'extracted_text' => 'Materi uji']);
    }
}
