<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'size',
        'extension',
        'status',
        'extracted_text',
        'processing_notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function folder()
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function summaries()
    {
        return $this->hasMany(Summary::class);
    }

    public function flashcards()
    {
        return $this->hasMany(Flashcard::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class);
    }

    public function notes()
    {
        return $this->hasMany(StudyNote::class);
    }
}
