<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Flashcard;
use App\Models\Quiz;
use App\Models\StudyNote;
use App\Models\StudyRoom;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();

        // Apply isolation before RefreshDatabase runs, including when local config is cached.
        $app['config']->set([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'queue.default' => 'sync',
            'mail.default' => 'array',
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

    public function test_landing_uses_live_routes_and_icon_only_animation_control(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('welcome')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('aria-label="Jeda animasi"', false)
            ->assertDontSee('>Jeda animasi<', false)
            ->assertDontSee('Konsep UI / data contoh')
            ->assertDontSee('prototype.js');
    }

    public function test_signed_in_accounts_return_to_their_own_dashboard_from_auth_pages(): void
    {
        foreach (['admin' => 'admin.dashboard', 'mahasiswa' => 'student.dashboard'] as $role => $dashboard) {
            $user = User::factory()->create(['role' => $role]);
            foreach (['/', '/login', '/register', '/forgot-password'] as $path) {
                $this->actingAs($user)->get($path)->assertRedirect(route($dashboard));
            }
        }
    }

    public function test_auth_forms_keep_real_actions_fields_and_csrf_tokens(): void
    {
        foreach (['login', 'register'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('action="'.route($route).'"', false)
                ->assertSee('method="POST"', false)
                ->assertSee('name="_token"', false)
                ->assertSee('name="username"', false)
                ->assertSee('name="math_captcha"', false)
                ->assertSee('data-captcha-url="'.route('captcha.refresh').'"', false)
                ->assertDontSee('Ini preview desain');
        }

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="email"', false);

        $this->get(route('password.reset', ['token' => 'reset-token', 'email' => 'learner@example.com']))
            ->assertOk()
            ->assertSee('action="'.route('password.store').'"', false)
            ->assertSee('name="token" value="reset-token"', false)
            ->assertSee('value="learner@example.com"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_captcha_refresh_and_login_errors_remain_connected_to_session(): void
    {
        $captcha = $this->getJson(route('captcha.refresh'))
            ->assertOk()
            ->assertJsonStructure(['num1', 'num2', 'operator', 'question'])
            ->json();

        $answer = match ($captcha['operator']) {
            '+' => $captcha['num1'] + $captcha['num2'],
            '-' => $captcha['num1'] - $captcha['num2'],
            '*' => $captcha['num1'] * $captcha['num2'],
        };
        $this->assertSame($answer, session('math_captcha_answer'));

        $this->withSession(['math_captcha_answer' => 9])->post(route('login'), [
            'username' => 'learner',
            'password' => 'do-not-render-this-password',
            'math_captcha' => 8,
        ])->assertRedirect(route('login'))->assertSessionHasErrors('math_captcha');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Jawaban hitungan salah. Coba lagi.')
            ->assertSee('value="learner"', false)
            ->assertDontSee('do-not-render-this-password');
    }

    public function test_login_and_registration_submit_through_existing_auth_controllers(): void
    {
        $user = $this->student(['username' => 'learner']);
        $this->withSession(['math_captcha_answer' => 9])->post(route('login'), [
            'username' => 'learner',
            'password' => 'password',
            'math_captcha' => 9,
            'remember' => 1,
        ])->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->withSession(['math_captcha_answer' => 9])->post(route('register'), [
            'email' => 'new.learner@example.com',
            'username' => 'new.learner',
            'password' => 'Strong-study-password1',
            'password_confirmation' => 'Strong-study-password1',
            'math_captcha' => 9,
        ])->assertSessionHasNoErrors()->assertRedirect(route('student.dashboard'));

        $registered = User::where('email', 'new.learner@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($registered);
        $this->assertTrue(Hash::check('Strong-study-password1', $registered->password));
    }

    public function test_login_lock_and_password_reset_status_are_visible(): void
    {
        Cache::put('login:lock:127.0.0.1', time() + 60, 300);
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-login-lock=', false)
            ->assertSee('type="submit" disabled', false);

        $this->withSession(['status' => 'Tautan reset sudah dikirim.'])
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee('Tautan reset sudah dikirim.');
    }

    public function test_student_empty_pages_render_live_navigation_and_forms(): void
    {
        $this->actingAs($this->student());
        foreach ([
            'student.dashboard' => 'student.dashboard',
            'documents.index' => 'student.documents.index',
            'documents.create' => 'student.documents.create',
            'chat.index' => 'student.chat.index',
            'study-rooms.index' => 'student.study-rooms.index',
            'student.guide' => 'student.guide',
            'profile.edit' => 'profile.edit',
        ] as $route => $view) {
            $this->get(route($route))->assertOk()->assertViewIs($view)
                ->assertSee(route('documents.index'), false)
                ->assertSee(route('logout'), false)
                ->assertDontSee('Nadia Putri');
        }

        $this->get(route('documents.create'))
            ->assertSee('action="'.route('documents.store').'"', false)
            ->assertSee('multipart/form-data', false);
        $this->get(route('profile.edit'))
            ->assertSee('action="'.route('profile.update').'"', false)
            ->assertSee('action="'.route('profile.password.update').'"', false);
    }

    public function test_populated_learning_pages_render_owned_data_and_real_actions(): void
    {
        $data = $this->learningData();
        $this->actingAs($data['user']);

        foreach ([
            [route('student.dashboard'), 'student.dashboard', $data['document']->title],
            [route('documents.index'), 'student.documents.index', $data['document']->title],
            [route('documents.show', $data['document']), 'student.documents.show', $data['document']->title],
            [route('folders.show', $data['folder']), 'student.folders.show', $data['folder']->name],
            [route('summaries.show', $data['summary']), 'student.summaries.show', 'Poin inti untuk pengujian'],
            [route('quizzes.show', $data['quiz']), 'student.quizzes.show', $data['quiz']->title],
            [route('flashcards.index', $data['document']), 'student.flashcards.index', 'Apa fungsi alveolus?'],
            [route('folders.flashcards.index', $data['folder']), 'student.flashcards.index', $data['folder']->name],
            [route('chat.show', $data['session']), 'student.chat.show', $data['session']->title],
            [route('chat.index'), 'student.chat.index', $data['session']->title],
            [route('study-rooms.index'), 'student.study-rooms.index', $data['document']->title],
            [route('study-rooms.show', $data['room']), 'student.study-rooms.show', $data['document']->title],
        ] as [$url, $view, $text]) {
            $this->get($url)->assertOk()->assertViewIs($view)->assertSee($text)
                ->assertDontSee('aria-label="Cara belajar dari materi ini"', false);
        }

        $this->get(route('documents.show', $data['document']))
            ->assertSee(route('summaries.store', $data['document']), false)
            ->assertSee(route('documents.notes.store', $data['document']), false);
        $this->get(route('quizzes.show', $data['quiz']))
            ->assertSee('action="'.route('quizzes.attempts.store', $data['quiz']).'"', false);

        $data['quiz']->attempts()->create([
            'user_id' => $data['user']->id,
            'answers' => [$data['question']->id => 'B'],
            'score' => 1,
            'total' => 1,
            'submitted_at' => now(),
        ]);
        $this->get(route('quizzes.show', $data['quiz']))->assertOk();
        $this->get(route('student.dashboard'))->assertOk();
    }

    public function test_summary_and_quiz_processing_and_failure_states_render(): void
    {
        $data = $this->learningData();
        $this->actingAs($data['user']);
        foreach (['processing', 'failed'] as $status) {
            $data['summary']->update(['status' => $status]);
            $data['quiz']->update(['status' => $status]);
            $this->get(route('summaries.show', $data['summary']))->assertOk()
                ->assertDontSee('Poin inti untuk pengujian');
            $this->get(route('quizzes.show', $data['quiz']))->assertOk();
        }
    }

    public function test_foreign_learning_resources_remain_inaccessible(): void
    {
        $data = $this->learningData();
        $this->actingAs($this->student());

        foreach ([
            route('documents.show', $data['document']),
            route('folders.show', $data['folder']),
            route('summaries.show', $data['summary']),
            route('quizzes.show', $data['quiz']),
            route('flashcards.index', $data['document']),
            route('folders.flashcards.index', $data['folder']),
            route('chat.show', $data['session']),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get(route('study-rooms.show', $data['room']))->assertNotFound();
        $this->get(route('documents.index'))->assertOk()->assertDontSee($data['document']->title);
    }

    public function test_admin_pages_render_and_role_boundaries_hold(): void
    {
        $this->learningData();
        Http::fake(['open.er-api.com/*' => Http::response(['rates' => ['IDR' => 16300]])]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['admin.dashboard', 'admin.users.index', 'admin.documents.index', 'admin.revenue', 'profile.edit'] as $route) {
            $this->get(route($route))->assertOk()->assertSee(route('logout'), false);
        }
        $this->get(route('student.dashboard'))->assertForbidden();

        $this->actingAs($this->student());
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => 'mahasiswa'], $attributes));
    }

    private function learningData(): array
    {
        $user = $this->student(['name' => 'Pelajar Uji', 'username' => 'pelajar.uji']);
        $folder = DocumentFolder::create(['user_id' => $user->id, 'name' => 'Folder biologi pengujian']);
        $document = Document::create([
            'user_id' => $user->id,
            'title' => 'Sistem pernapasan pengujian',
            'original_name' => 'pernapasan.pdf',
            'file_name' => 'pernapasan.pdf',
            'file_path' => 'documents/pernapasan.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'extension' => 'pdf',
            'status' => 'processed',
            'extracted_text' => 'Alveolus menjadi tempat pertukaran gas.',
        ]);
        $folderDocument = $document->replicate(['public_id']);
        $folderDocument->folder_id = $folder->id;
        $folderDocument->title = 'Materi dalam folder pengujian';
        $folderDocument->save();

        $summary = Summary::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'short_summary' => 'Ringkasan singkat pernapasan.',
            'full_summary' => 'Alveolus menjadi tempat pertukaran gas.',
            'key_points' => ['Poin inti untuk pengujian'],
            'status' => 'completed',
        ]);
        $quiz = Quiz::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'title' => 'Latihan pernapasan pengujian',
            'question_count' => 1,
            'question_type' => 'multiple_choice',
            'status' => 'completed',
        ]);
        $question = $quiz->questions()->create([
            'question' => 'Di mana pertukaran gas terjadi?',
            'options' => ['A' => 'Trakea', 'B' => 'Alveolus', 'C' => 'Hidung', 'D' => 'Bronkus'],
            'correct_answer' => 'B',
            'explanation' => 'Pertukaran gas terjadi pada alveolus.',
            'position' => 1,
        ]);
        Flashcard::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'front' => 'Apa fungsi alveolus?',
            'back' => 'Pertukaran oksigen dan karbon dioksida.',
            'study_status' => 'ulang',
        ]);
        StudyNote::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'content' => 'Catatan pribadi untuk pengujian.',
        ]);
        $session = ChatSession::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'title' => 'Bahas alveolus pengujian',
        ]);
        $session->messages()->create(['role' => 'user', 'content' => 'Mengapa dinding alveolus tipis?']);
        $session->messages()->create(['role' => 'assistant', 'content' => 'Supaya gas mudah berdifusi.']);
        $room = StudyRoom::create([
            'host_id' => $user->id,
            'target_type' => Document::class,
            'target_id' => $document->id,
            'pin' => '1234',
            'status' => 'active',
        ]);

        return compact('user', 'folder', 'document', 'summary', 'quiz', 'question', 'session', 'room');
    }
}
