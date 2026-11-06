<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = ['chat_session_id', 'role', 'content', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    public function forChat(string $question = ''): array
    {
        $metadata = $this->metadata ?? [];
        if ($this->role === 'assistant' && ! empty($metadata['source_snippets'])) {
            $metadata['source_snippets'] = app(\App\Services\LearningSourceService::class)
                ->relevantChatReferences($metadata['source_snippets'], $question, $this->content);
        }
        if (! empty($metadata['image_path'])) {
            $metadata['image_path'] = route('chat.attachments.image', ['session' => $this->session, 'message' => $this]);
            unset($metadata['image_disk']);
        }
        return ['id' => $this->id, 'role' => $this->role, 'content' => $this->content, 'metadata' => $metadata];
    }
}
