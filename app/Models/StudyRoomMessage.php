<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyRoomMessage extends Model
{
    protected $fillable = ['study_room_id', 'user_id', 'message', 'is_ai', 'metadata'];

    protected $casts = [
        'is_ai' => 'boolean',
        'metadata' => 'array',
    ];

    public function room()
    {
        return $this->belongsTo(StudyRoom::class, 'study_room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
