<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = ['document_id', 'folder_id', 'user_id', 'short_summary', 'full_summary', 'key_points', 'conclusion', 'raw_response', 'status', 'generation_error'];

    protected function casts(): array
    {
        return ['key_points' => 'array', 'raw_response' => 'array'];
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
}
