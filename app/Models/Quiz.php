<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = ['document_id', 'folder_id', 'user_id', 'study_room_id', 'title', 'question_type', 'question_count', 'status', 'generation_error', 'selected_document_ids'];

    protected function casts(): array
    {
        return ['selected_document_ids' => 'array'];
    }

    public function studyRoom()
    {
        return $this->belongsTo(StudyRoom::class, 'study_room_id');
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function folder()
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function source()
    {
        return $this->folder ?: $this->document;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
