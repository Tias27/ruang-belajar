<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyNote extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'document_id', 'folder_id', 'content'];

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
