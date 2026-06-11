<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flashcard extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = ['document_id', 'folder_id', 'user_id', 'front', 'back', 'position', 'study_status', 'review_count', 'last_reviewed_at'];

    protected function casts(): array
    {
        return ['last_reviewed_at' => 'datetime'];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function folder()
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
