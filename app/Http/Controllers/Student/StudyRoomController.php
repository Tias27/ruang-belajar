<?php
 
namespace App\Http\Controllers\Student;
 
use App\Http\Controllers\Controller;
use App\Models\StudyRoom;
use App\Models\StudyRoomMessage;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\GeminiService;
use App\Services\LearningSourceService;
use App\Events\StudyRoomMessageSent;
use App\Services\ActivityLogger;
use App\Services\DocumentTextExtractor;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
 
class StudyRoomController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // Cleanup: Close any duplicate active rooms hosted by the user, keeping only the latest one active
        $allActiveRooms = StudyRoom::with(['target', 'host', 'users'])
            ->where('host_id', $userId)
            ->where('status', 'active')
            ->latest()
            ->get();

        if ($allActiveRooms->count() > 1) {
            $latestRoom = $allActiveRooms->first();
            StudyRoom::where('host_id', $userId)
                ->where('status', 'active')
                ->where('id', '!=', $latestRoom->id)
                ->update(['status' => 'closed']);
            
            $myActiveRooms = collect([$latestRoom]);
        } else {
            $myActiveRooms = $allActiveRooms;
        }

        // Active rooms joined by the user (where they are not the host)
        $joinedActiveRooms = StudyRoom::with(['target', 'host', 'users'])
            ->where('status', 'active')
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->latest()
            ->get();

        // User's folders
        $folders = Auth::user()->documentFolders()
            ->latest()
            ->get();

        // User's documents (not in a folder)
        $documents = Auth::user()->documents()
            ->whereNull('folder_id')
            ->latest()
            ->get();

        return view('student.study-rooms.index', compact('myActiveRooms', 'joinedActiveRooms', 'folders', 'documents'));
    }

    public function store(Request $request, DocumentTextExtractor $extractor, ActivityLogger $logger)
    {
        $request->validate([
            'source_type' => ['required', 'string', 'in:document,folder,upload'],
            'document_id' => ['required_if:source_type,document', 'nullable', 'integer', 'exists:documents,id'],
            'folder_id' => [
                'required_if:source_type,folder', 
                'nullable', 
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->source_type === 'folder') {
                        if (!is_numeric($value) || !DocumentFolder::where('id', $value)->exists()) {
                            $fail('Folder yang dipilih tidak valid.');
                        }
                    } elseif ($request->source_type === 'upload') {
                        if ($value !== 'new' && (!is_numeric($value) || !DocumentFolder::where('id', $value)->exists())) {
                            $fail('Folder yang dipilih tidak valid.');
                        }
                    }
                }
            ],
            'new_folder_name' => ['required_if:folder_id,new', 'nullable', 'string', 'max:250'],
            'files' => ['required_if:source_type,upload', 'nullable', 'array'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,docx,pptx,jpg,jpeg,png,gif,webp'],
        ]);

        $target = null;

        if ($request->source_type === 'document') {
            $document = Document::findOrFail($request->document_id);
            abort_if($document->user_id !== Auth::id(), 403);
            $target = $document;
        } elseif ($request->source_type === 'folder') {
            $folder = DocumentFolder::findOrFail($request->folder_id);
            abort_if($folder->user_id !== Auth::id(), 403);
            $target = $folder;
        } elseif ($request->source_type === 'upload') {
            $files = $request->file('files');
            if (empty($files)) {
                return back()->withErrors(['error' => 'Harap pilih file untuk diunggah.']);
            }

            // Handle Folder Creation/Selection
            $folderId = null;
            $targetFolder = null;

            if ($request->folder_id === 'new' && $request->filled('new_folder_name')) {
                $targetFolder = DocumentFolder::create([
                    'user_id' => Auth::id(),
                    'name' => trim((string) $request->new_folder_name),
                    'description' => 'Folder dibuat otomatis saat upload materi baru di Belajar Bareng.',
                ]);
                $folderId = $targetFolder->id;
            } elseif ($request->filled('folder_id') && is_numeric($request->folder_id)) {
                $targetFolder = DocumentFolder::where('user_id', Auth::id())->find($request->folder_id);
                if ($targetFolder) {
                    $folderId = $targetFolder->id;
                }
            } elseif (count($files) > 1) {
                // Auto-create folder for multiple files if none is selected
                $firstFileTitle = pathinfo($files[0]->getClientOriginalName(), PATHINFO_FILENAME);
                $targetFolder = DocumentFolder::create([
                    'user_id' => Auth::id(),
                    'name' => Str::limit($firstFileTitle . ' & Teman-teman', 200, ''),
                    'description' => 'Folder dibuat otomatis untuk mengelompokkan materi belajar bareng.',
                ]);
                $folderId = $targetFolder->id;
            }

            $storageDirectory = storage_path('app/private/documents');
            if (! is_dir($storageDirectory)) {
                mkdir($storageDirectory, 0755, true);
            }

            $firstDoc = null;

            foreach ($files as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $fileName = (string) Str::uuid().'.'.$extension;
                $path = 'documents/'.$fileName;
                
                $file->move($storageDirectory, $fileName);
                $absolutePath = $storageDirectory.DIRECTORY_SEPARATOR.$fileName;

                // Generate title
                $fileTitle = pathinfo($originalName, PATHINFO_FILENAME) ?: $originalName;
                $title = Str::limit($fileTitle, 250, '');

                $document = Document::create([
                    'user_id' => Auth::id(),
                    'folder_id' => $folderId,
                    'title' => $title,
                    'original_name' => $originalName,
                    'file_name' => $fileName,
                    'file_path' => $path,
                    'mime_type' => $this->mimeTypeFor($extension),
                    'size' => $fileSize,
                    'extension' => $extension,
                    'status' => 'processing',
                ]);

                $text = '';
                $processingNotes = null;

                try {
                    $text = $extractor->extract($absolutePath, $extension);
                } catch (\Throwable $exception) {
                    report($exception);
                    $processingNotes = 'Teks belum dapat diekstrak otomatis dari dokumen ini.';
                }

                $document->update([
                    'extracted_text' => $text,
                    'status' => filled($text) ? 'processed' : 'uploaded',
                    'processing_notes' => filled($text) ? null : ($processingNotes ?: 'Teks belum dapat diekstrak otomatis.'),
                ]);

                $logger->log('upload_document', $document, ['title' => $document->title]);
                
                if (!$firstDoc) {
                    $firstDoc = $document;
                }
            }

            $target = $targetFolder ?: $firstDoc;
        }

        if (!$target) {
            return back()->withErrors(['error' => 'Gagal memilih materi belajar.']);
        }

        // Create the study room targeting this folder or document
        $room = $this->createRoom($target);

        return redirect()->route('study-rooms.show', $room)->with('status', 'Room belajar bareng berhasil dibuat!');
    }

    private function mimeTypeFor(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    public function storeDocument(Document $document)
    {
        abort_if($document->user_id !== Auth::id(), 403);

        $room = $this->createRoom($document);

        return redirect()->route('study-rooms.show', $room)->with('status', 'Room belajar bareng berhasil dibuat!');
    }

    public function storeFolder(Request $request, DocumentFolder $folder)
    {
        abort_if($folder->user_id !== Auth::id(), 403);

        $selectedDocIds = $request->input('document_ids');

        $room = $this->createRoom($folder, $selectedDocIds);

        return redirect()->route('study-rooms.show', $room)->with('status', 'Room belajar bareng berhasil dibuat!');
    }

    private function createRoom($target, ?array $selectedDocIds = null)
    {
        // Close any other active rooms hosted by this user
        StudyRoom::where('host_id', Auth::id())
            ->where('status', 'active')
            ->update(['status' => 'closed']);

        // Generate a unique 4-digit PIN for active rooms
        do {
            $pin = sprintf('%04d', random_int(0, 9999));
        } while (StudyRoom::where('pin', $pin)->where('status', 'active')->exists());

        return StudyRoom::create([
            'host_id' => Auth::id(),
            'target_type' => get_class($target),
            'target_id' => $target->id,
            'pin' => $pin,
            'status' => 'active',
            'selected_document_ids' => $selectedDocIds,
        ]);
    }

    public function join(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'string', 'size:4'],
        ], [
            'pin.required' => 'PIN wajib diisi.',
            'pin.size' => 'PIN harus terdiri dari 4 digit angka.',
        ]);

        $room = StudyRoom::where('pin', $request->pin)
            ->where('status', 'active')
            ->first();

        if (!$room) {
            return back()->withErrors(['pin' => 'Room dengan PIN tersebut tidak ditemukan atau sudah ditutup.']);
        }

        // Add user to the study_room_users pivot if they are not the host
        if ($room->host_id !== Auth::id()) {
            $room->users()->syncWithoutDetaching([Auth::id()]);
        }

        return redirect()->route('study-rooms.show', $room);
    }

    public function show(StudyRoom $room)
    {
        if ($room->status !== 'active') {
            abort(404, 'Sesi belajar ini sudah ditutup.');
        }

        if (!$room->target) {
            $room->update(['status' => 'closed']);
            return redirect()->route('study-rooms.index')->withErrors(['pin' => 'Materi untuk room belajar ini telah terhapus, room telah otomatis ditutup.']);
        }

        $userId = Auth::id();
        $isHost = $room->host_id === $userId;
        $isJoined = $room->users()->where('users.id', $userId)->exists();

        if (!$isHost && !$isJoined) {
            abort(404, 'Room tidak ditemukan.');
        }

        // Eager load relationships including target morph models' studies
        $room->load([
            'host',
            'users',
            'messages.user',
            'target' => function ($morphTo) use ($room) {
                $morphTo->morphWith([
                    \App\Models\Document::class => [
                        'summaries',
                        'quizzes' => fn ($query) => $query->where('study_room_id', $room->id),
                        'flashcards'
                    ],
                    \App\Models\DocumentFolder::class => [
                        'summaries',
                        'quizzes' => fn ($query) => $query->where('study_room_id', $room->id),
                        'flashcards'
                    ],
                ]);
            }
        ]);

        return view('student.study-rooms.show', compact('room'));
    }
    public function sendMessage(Request $request, StudyRoom $room, GeminiService $gemini, LearningSourceService $sources)
    {
        if ($room->status !== 'active') {
            return response()->json(['error' => 'Room sudah ditutup.'], 403);
        }

        $userId = Auth::id();
        $isHost = $room->host_id === $userId;
        $isJoined = $room->users()->where('users.id', $userId)->exists();

        if (!$isHost && !$isJoined) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke room ini.'], 403);
        }

        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120', 'mimes:jpeg,png,jpg,webp,gif'],
        ]);

        $imagePath = null;
        $dbMetadata = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/public/chat_images'), $fileName);
            $imagePath = 'chat_images/' . $fileName;
            $dbMetadata = ['image_path' => 'storage/' . $imagePath];
        }

        $absoluteImagePath = $imagePath ? storage_path('app/public/' . $imagePath) : null;

        // Fetch recent messages before saving the new one as chat history context
        $history = $room->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn($msg) => [
                'role' => $msg->is_ai ? 'assistant' : 'user',
                'content' => $msg->message,
            ])
            ->all();

        // 1. Save user message
        $userMessage = StudyRoomMessage::create([
            'study_room_id' => $room->id,
            'user_id' => $userId,
            'message' => $request->message,
            'is_ai' => false,
            'metadata' => $dbMetadata,
        ]);

        try {
            broadcast(new StudyRoomMessageSent($userMessage))->toOthers();
        } catch (\Throwable $e) {
            report($e);
        }

        $target = $room->target;
        $aiResponse = '';
        try {
            $aiResponse = $gemini->chat($target, $request->message, $history, $room->selected_document_ids, $absoluteImagePath);
            $sourceSnippets = $sources->snippetsFor($target, $request->message.' '.$aiResponse);
        } catch (\Throwable $exception) {
            report($exception);
            $aiResponse = $sources->fallbackAnswer($target, $request->message, $exception->getMessage());
            $sourceSnippets = $sources->snippetsFor($target, $request->message);
        }
        $aiMessage = StudyRoomMessage::create([
            'study_room_id' => $room->id,
            'user_id' => null,
            'message' => $aiResponse,
            'is_ai' => true,
            'metadata' => [
                'source_snippets' => $sourceSnippets,
            ],
        ]);

        try {
            broadcast(new StudyRoomMessageSent($aiMessage));
        } catch (\Throwable $e) {
            report($e);
        }

        \App\Jobs\ConsolidateAiMemoryJob::dispatchSync($target, auth()->user(), [
            ['is_ai' => false, 'message' => $userMessage->message],
            ['is_ai' => true, 'message' => $aiMessage->message],
        ]);

        return response()->json([
            'status' => 'success',
            'user_message' => $userMessage->load('user'),
            'ai_message' => $aiMessage->load('user'),
        ]);
    }

    public function getMessages(Request $request, StudyRoom $room)
    {
        if ($room->status !== 'active') {
            return response()->json(['error' => 'Room sudah ditutup.'], 403);
        }

        $userId = Auth::id();
        $isHost = $room->host_id === $userId;
        $isJoined = $room->users()->where('users.id', $userId)->exists();

        if (!$isHost && !$isJoined) {
            return response()->json(['error' => 'Anda tidak memiliki akses ke room ini.'], 403);
        }

        $afterId = (int) $request->query('after_id', 0);

        $messages = $room->messages()
            ->with('user')
            ->when($afterId > 0, fn($q) => $q->where('id', '>', $afterId))
            ->oldest()
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'user_id'    => $m->user_id,
                'user_name'  => $m->is_ai ? 'RuangBelajar AI' : ($m->user ? $m->user->name : 'Siswa'),
                'message'    => $m->message,
                'is_ai'      => (bool) $m->is_ai,
                'metadata'   => $m->metadata,
                'created_at' => $m->created_at->toIso8601String(),
            ])
            ->values();

        return response()->json(['messages' => $messages]);
    }

    public function close(StudyRoom $room)
    {
        abort_if($room->host_id !== Auth::id(), 403);

        // Clean up quiz history (quizzes & attempts) created during this study room session
        // Using study_room_id column is extremely robust and precise
        $quizzes = \App\Models\Quiz::where('study_room_id', $room->id)->get();
        foreach ($quizzes as $quiz) {
            $quiz->questions()->delete();
            $quiz->attempts()->delete();
            $quiz->delete();
        }

        // Just in case, delete attempts linked to this room's timeframe on target's other quizzes (if any exist)
        $target = $room->target;
        if ($target) {
            $allQuizzes = $target->quizzes;
            foreach ($allQuizzes as $quiz) {
                $quiz->attempts()
                    ->where('created_at', '>=', $room->created_at)
                    ->delete();
            }
        }

        $room->update(['status' => 'closed']);

        return redirect()->route('study-rooms.index')->with('status', 'Room belajar bareng telah ditutup.');
    }
}
