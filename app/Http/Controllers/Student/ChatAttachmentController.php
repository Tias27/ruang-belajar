<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    public function image(ChatSession $session, ChatMessage $message)
    {
        abort_unless($session->user_id === auth()->id(), 403);
        abort_unless($message->chat_session_id === $session->id, 404);
        $metadata = $message->metadata ?? [];
        $relative = $metadata['image_path'] ?? '';
        $disk = ($metadata['image_disk'] ?? null) === 'local' ? 'local' : 'public';
        if ($disk === 'public') $relative = preg_replace('#^/?storage/#', '', $relative);
        abort_unless(preg_match('#^chat_images/[a-zA-Z0-9_-]+\.(?:png|jpe?g|gif|webp)$#i', $relative), 404);
        $path = $this->path($disk, $relative);
        $mime = mime_content_type($path);
        abort_unless(in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true), 404);

        return response()->file($path, $this->headers($mime));
    }

    public function document(Document $document)
    {
        abort_unless($document->user_id === auth()->id(), 403);
        $pdf = strtolower($document->extension) === 'pdf';
        if ($pdf) $this->path('local', $document->file_path);
        abort_if(! $pdf && blank($document->extracted_text), 404);

        return response()->json([
            'title' => $document->original_name ?: $document->title,
            'kind' => $pdf ? 'pdf' : 'text',
            'url' => $pdf ? route('chat.attachments.document-file', $document) : null,
            'text' => $document->extracted_text ?? '',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function documentFile(Document $document)
    {
        abort_unless($document->user_id === auth()->id(), 403);
        abort_unless(strtolower($document->extension) === 'pdf', 404);
        $path = $this->path('local', $document->file_path);
        abort_unless(mime_content_type($path) === 'application/pdf', 404);

        return response()->file($path, $this->headers('application/pdf'));
    }

    private function path(string $disk, string $relative): string
    {
        $root = realpath(Storage::disk($disk)->path(''));
        $path = realpath(Storage::disk($disk)->path($relative));
        abort_unless($root && $path && is_file($path) && str_starts_with($path, $root.DIRECTORY_SEPARATOR), 404);
        return $path;
    }

    private function headers(string $mime): array
    {
        return ['Content-Type' => $mime, 'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN'];
    }
}
