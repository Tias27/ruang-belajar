<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\ActivityLogger;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        // Hapus sesi chat kosong milik user yang login agar tidak membebani database dan riwayat
        auth()->user()->chatSessions()->doesntHave('messages')->delete();

        $search = trim((string) $request->query('q', ''));
        $sessions = auth()->user()
            ->chatSessions()
            ->with(['document', 'folder', 'latestMessage'])
            ->withCount('messages');

        if ($search !== '') {
            $sessions->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhereHas('document', fn ($document) => $document->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('folder', fn ($folder) => $folder->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('messages', fn ($message) => $message->where('content', 'like', "%{$search}%"));
            });
        }

        return view('student.chat.index', [
            'sessions' => $sessions->latest()->paginate(12)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(Document $document)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        $session = $document->chatSessions()->create([
            'user_id' => auth()->id(),
            'title' => 'Tanya Materi '.$document->title,
        ]);

        return redirect()->route('chat.show', $session);
    }

    public function createFolder(DocumentFolder $folder)
    {
        abort_if($folder->user_id !== auth()->id(), 403);

        $session = $folder->chatSessions()->create([
            'user_id' => auth()->id(),
            'title' => 'Tanya Folder '.$folder->name,
        ]);

        return redirect()->route('chat.show', $session);
    }

    public function show(ChatSession $session)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        return view('student.chat.show', [
            'session' => $session->load('document', 'folder', 'messages'),
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'session_ids' => ['required', 'array', 'min:1'],
            'session_ids.*' => ['string', 'max:40'],
        ]);

        $deleted = ChatSession::query()
            ->where('user_id', auth()->id())
            ->whereIn('public_id', $data['session_ids'])
            ->delete();

        return redirect()
            ->route('chat.index')
            ->with('status', $deleted.' riwayat dihapus.');
    }

    public function store(Request $request, ChatSession $session, GeminiService $gemini, ActivityLogger $logger, LearningSourceService $sources)
    {
        abort_if($session->user_id !== auth()->id(), 403);

        $data = $request->validate(['question' => ['required', 'string', 'max:2000']]);

        // Fetch recent messages before saving the new one as chat history context
        $history = $session->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->all();

        $userMessage = $session->messages()->create(['role' => 'user', 'content' => $data['question']]);

        $source = $session->folder ?: $session->document;

        if ($request->header('Accept') === 'text/event-stream' || $request->expectsJson()) {
            return response()->stream(function () use ($session, $gemini, $sources, $data, $source, $logger, $history) {
                $session->update(['title' => Str::limit($data['question'], 70)]);
                $logger->log('chat_document', $session, ['document_id' => $session->document_id, 'folder_id' => $session->folder_id]);

                $fullAnswer = '';
                $sourceSnippets = [];

                try {
                    $stream = $gemini->streamChat($source, $data['question'], $history);
                    foreach ($stream as $chunk) {
                        $fullAnswer .= $chunk;
                        echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }

                    $sourceSnippets = $sources->snippetsFor($source, $data['question'].' '.$fullAnswer);
                } catch (Throwable $exception) {
                    report($exception);
                    $fullAnswer = $sources->fallbackAnswer($source, $data['question'], $exception->getMessage());
                    $sourceSnippets = $sources->snippetsFor($source, $data['question']);
                    echo "data: " . json_encode(['chunk' => $fullAnswer]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }

                $assistantMessage = $session->messages()->create([
                    'role' => 'assistant',
                    'content' => $fullAnswer,
                    'metadata' => [
                        'source_snippets' => $sourceSnippets,
                    ],
                ]);

                // Dispatch job to consolidate long-term memory in the background
                \App\Jobs\ConsolidateAiMemoryJob::dispatch($source, auth()->user(), [
                    ['is_ai' => false, 'message' => $data['question']],
                    ['is_ai' => true, 'message' => $fullAnswer],
                ])->afterResponse();

                echo "data: " . json_encode([
                    'done' => true,
                    'title' => $session->title,
                    'message' => [
                        'id' => $assistantMessage->id,
                        'role' => 'assistant',
                        'content' => $assistantMessage->content,
                        'metadata' => $assistantMessage->metadata,
                    ],
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }, 200, [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // Fallback for non-AJAX / non-streaming requests
        try {
            $answer = $gemini->chat($source, $data['question'], $history);
            $sourceSnippets = $sources->snippetsFor($source, $data['question'].' '.$answer);
        } catch (Throwable $exception) {
            report($exception);
            $answer = $sources->fallbackAnswer($source, $data['question'], $exception->getMessage());
            $sourceSnippets = $sources->snippetsFor($source, $data['question']);
        }
        $session->messages()->create([
            'role' => 'assistant',
            'content' => $answer,
            'metadata' => [
                'source_snippets' => $sourceSnippets,
            ],
        ]);
        $session->update(['title' => Str::limit($data['question'], 70)]);
        $logger->log('chat_document', $session, ['document_id' => $session->document_id, 'folder_id' => $session->folder_id]);

        // Dispatch job to consolidate long-term memory in the background
        \App\Jobs\ConsolidateAiMemoryJob::dispatch($source, auth()->user(), [
            ['is_ai' => false, 'message' => $data['question']],
            ['is_ai' => true, 'message' => $answer],
        ])->afterResponse();

        return redirect()->route('chat.show', $session);
    }
}
