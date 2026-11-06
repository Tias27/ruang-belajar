<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Flashcard;
use App\Models\Quiz;
use App\Models\StudyRoom;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LearningInteractionsTest extends TestCase
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
            'queue.default' => 'sync',
        ]);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['view.compiled' => storage_path('framework/views')]);
        $this->withoutVite();
        Http::preventStrayRequests();
    }

    public function test_card_review_updates_progress_and_rejects_invalid_or_foreign_reviews(): void
    {
        [$user, $document] = $this->material();
        $card = Flashcard::create([
            'user_id' => $user->id, 'document_id' => $document->id,
            'front' => 'Apa fungsi alveolus?', 'back' => 'Pertukaran gas.',
        ]);
        $url = route('flashcards.index', $document);
        $this->actingAs($user)->from($url)
            ->patch(route('flashcards.review', $card), ['study_status' => 'paham'])
            ->assertRedirect($url);
        $this->assertDatabaseHas('flashcards', [
            'id' => $card->id, 'study_status' => 'paham', 'review_count' => 1,
        ]);
        $this->assertNotNull($card->fresh()->last_reviewed_at);
        $this->get($url)->assertOk()->assertSee('Sudah paham')->assertSee('Diulang 1 kali');

        $this->patch(route('flashcards.review', $card), ['study_status' => 'invalid'])
            ->assertSessionHasErrors('study_status');
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
            ->patch(route('flashcards.review', $card), ['study_status' => 'sulit'])->assertForbidden();
        $this->assertSame(1, $card->fresh()->review_count);
    }

    public function test_deck_orders_difficult_cards_first_for_files_and_folders(): void
    {
        [$user, $document, $folder] = $this->material();
        foreach ([['document_id' => $document->id], ['folder_id' => $folder->id]] as $source) {
            foreach (['paham' => 1, 'baru' => 2, 'ulang' => 3, 'sulit' => 4] as $status => $position) {
                Flashcard::create($source + [
                    'user_id' => $user->id, 'front' => 'Kartu '.$status, 'back' => 'Jawaban',
                    'position' => $position, 'study_status' => $status,
                ]);
            }
        }
        $this->actingAs($user);
        foreach ([route('flashcards.index', $document), route('folders.flashcards.index', $folder)] as $url) {
            $this->get($url)->assertOk()->assertSeeInOrder(['Kartu sulit', 'Kartu ulang', 'Kartu baru', 'Kartu paham']);
        }
    }

    public function test_card_review_json_persists_without_redirect_and_checks_access(): void
    {
        [$user, $document, $folder] = $this->material();
        foreach ([['document_id' => $document->id], ['folder_id' => $folder->id]] as $source) {
            $card = Flashcard::create($source + ['user_id' => $user->id, 'front' => 'Uji', 'back' => 'Jawaban']);
            $this->actingAs($user);
            foreach (['sulit', 'ulang', 'paham'] as $index => $status) {
                $this->patchJson(route('flashcards.review', $card), ['study_status' => $status])
                    ->assertOk()->assertJson(['id' => $card->id, 'study_status' => $status, 'review_count' => $index + 1]);
            }
            $this->assertSame('paham', $card->fresh()->study_status);
            $this->patchJson(route('flashcards.review', $card), ['study_status' => 'invalid'])->assertUnprocessable();
            $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
                ->patchJson(route('flashcards.review', $card), ['study_status' => 'sulit'])->assertForbidden();
            $this->assertSame(3, $card->fresh()->review_count);
        }
    }

    public function test_notes_persist_and_update_for_each_source_without_duplicate_rows(): void
    {
        [$user, $document, $folder] = $this->material();
        $this->actingAs($user);
        foreach ([['documents', $document, 'document_id'], ['folders', $folder, 'folder_id']] as [$route, $source, $column]) {
            $url = route($route.'.show', $source);
            $this->from($url)->post(route($route.'.notes.store', $source), ['content' => 'Catatan pertama'])->assertRedirect($url);
            $this->post(route($route.'.notes.store', $source), ['content' => '  Catatan diperbarui  '])->assertRedirect($url);
            $this->assertDatabaseHas('study_notes', [
                'user_id' => $user->id, $column => $source->id, 'content' => 'Catatan diperbarui',
            ]);
            $this->get($url)->assertOk()->assertSee('Catatan diperbarui');
        }
        $this->assertDatabaseCount('study_notes', 2);
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
            ->post(route('documents.notes.store', $document), ['content' => 'Tidak boleh'])->assertForbidden();
        $this->post(route('folders.notes.store', $folder), ['content' => 'Tidak boleh'])->assertForbidden();
    }

    public function test_quiz_submission_keeps_scores_answers_and_retake_state(): void
    {
        [$user, $document] = $this->material();
        $quiz = $this->quiz($user, ['document_id' => $document->id]);
        $question = $quiz->questions()->create([
            'question' => 'Di mana pertukaran gas terjadi?',
            'options' => ['Trakea', 'Alveolus', 'Hidung', 'Bronkus'],
            'correct_answer' => 'B', 'position' => 1,
            'explanation' => 'Dinding alveolus tipis untuk difusi.',
        ]);
        $this->actingAs($user)->post(route('quizzes.attempts.store', $quiz), [
            'answers' => [$question->id => 'Alveolus'],
        ])->assertRedirect(route('quizzes.show', $quiz));
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id, 'user_id' => $user->id, 'score' => 1, 'total' => 1,
        ]);
        $this->assertSame('Alveolus', $quiz->attempts()->first()->answers[$question->id]);
        $this->get(route('quizzes.show', $quiz))->assertOk()
            ->assertSee('Kerjakan Ulang')->assertSee('Dinding alveolus tipis untuk difusi.');
        $this->get(route('quizzes.show', $quiz).'?retake=true')->assertOk()
            ->assertViewHas('latestAttempt', null)->assertDontSee('Kerjakan Ulang');

        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']))
            ->post(route('quizzes.attempts.store', $quiz), ['answers' => [$question->id => 'Alveolus']])
            ->assertForbidden();
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_folder_results_hide_feature_tabs_and_material_page_keeps_valid_selection(): void
    {
        [$user, $document, $folder] = $this->material();
        $document->update(['folder_id' => $folder->id]);
        $other = $document->replicate(['public_id']);
        $other->title = 'Materi lainnya';
        $other->save();
        $scope = [$document->public_id];
        $summary = Summary::create([
            'user_id' => $user->id, 'folder_id' => $folder->id,
            'short_summary' => 'Inti materi', 'full_summary' => 'Penjelasan materi',
            'selected_document_ids' => $scope,
        ]);
        $matchingChat = ChatSession::create([
            'user_id' => $user->id, 'folder_id' => $folder->id,
            'selected_document_ids' => $scope, 'title' => 'Obrolan pilihan',
        ]);
        $otherChat = ChatSession::create([
            'user_id' => $user->id, 'folder_id' => $folder->id,
            'selected_document_ids' => [$other->public_id], 'title' => 'Obrolan berbeda',
        ]);
        $otherChat->forceFill(['created_at' => now()->addMinute()])->save();
        $otherQuiz = $this->quiz($user, ['folder_id' => $folder->id, 'selected_document_ids' => [$other->public_id]]);
        $fallback = route('folders.show', $folder).'?'.http_build_query(['document_ids' => $scope]);

        $this->actingAs($user)->get(route('summaries.show', $summary))->assertOk()
            ->assertDontSee('aria-label="Cara belajar dari materi ini"', false)
            ->assertDontSee('href="'.route('chat.show', $matchingChat).'"', false)
            ->assertDontSee('href="'.route('chat.show', $otherChat).'"', false)
            ->assertDontSee('href="'.route('quizzes.show', $otherQuiz).'"', false)
            ->assertSee('href="'.route('folders.show', $folder).'"', false);
        $this->get($fallback)->assertOk()->assertSee('selected: '.\Illuminate\Support\Js::from(collect($scope))->toHtml(), false);

        $foreign = $this->material()[1];
        $this->get(route('folders.show', $folder).'?'.http_build_query([
            'document_ids' => [$document->public_id, $foreign->public_id],
        ]))->assertOk()->assertSee('selected: '.\Illuminate\Support\Js::from(collect($scope))->toHtml(), false);
    }

    public function test_room_learning_keeps_room_back_link_without_personal_tabs(): void
    {
        [$user, $document] = $this->material();
        $room = StudyRoom::create([
            'host_id' => $user->id, 'target_type' => Document::class,
            'target_id' => $document->id, 'pin' => '1234', 'status' => 'active',
        ]);
        $quiz = $this->quiz($user, ['document_id' => $document->id, 'study_room_id' => $room->id]);
        $member = User::factory()->create(['role' => 'mahasiswa']);
        $room->users()->attach($member);
        $this->actingAs($member);
        foreach ([route('quizzes.show', $quiz), route('flashcards.index', $document)] as $url) {
            $this->get($url.'?room='.$room->uuid)->assertOk()
                ->assertSee('href="'.route('study-rooms.show', $room).'"', false)
                ->assertDontSee('aria-label="Cara belajar dari materi ini"', false);
        }
    }

    private function material(): array
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Biologi']);
        $document = Document::create([
            'user_id' => $user->id, 'title' => 'Pernapasan',
            'original_name' => 'pernapasan.pdf', 'file_name' => 'pernapasan.pdf',
            'file_path' => 'documents/pernapasan.pdf', 'mime_type' => 'application/pdf',
            'extension' => 'pdf', 'size' => 2048, 'status' => 'processed',
            'extracted_text' => 'Alveolus menjadi tempat pertukaran gas.',
        ]);

        return [$user, $document, $folder];
    }

    private function quiz(User $user, array $source): Quiz
    {
        return Quiz::create($source + [
            'user_id' => $user->id, 'title' => 'Latihan pernapasan',
            'question_type' => 'multiple_choice', 'question_count' => 1, 'status' => 'completed',
        ]);
    }
}
