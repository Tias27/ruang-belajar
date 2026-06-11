<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DocumentFolder extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = ['user_id', 'name', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'folder_id');
    }

    public function summaries()
    {
        return $this->hasMany(Summary::class, 'folder_id');
    }

    public function flashcards()
    {
        return $this->hasMany(Flashcard::class, 'folder_id');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'folder_id');
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class, 'folder_id');
    }

    public function notes()
    {
        return $this->hasMany(StudyNote::class, 'folder_id');
    }

    public function getTitleAttribute(): string
    {
        return $this->name;
    }

    public function combinedExtractedText(): string
    {
        return $this->documents()
            ->oldest()
            ->get(['title', 'extracted_text'])
            ->filter(fn (Document $document) => filled($document->extracted_text))
            ->map(fn (Document $document) => "### {$document->title}\n{$document->extracted_text}")
            ->implode("\n\n");
    }

    public function documentsForPrompt(): Collection
    {
        return $this->documents()->oldest()->get(['title', 'extracted_text']);
    }
}
