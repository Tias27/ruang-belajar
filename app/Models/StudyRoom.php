<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyRoom extends Model
{
    protected $fillable = ['uuid', 'host_id', 'target_type', 'target_id', 'pin', 'status'];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    protected static function booted()
    {
        static::creating(function ($room) {
            do {
                $uuid = (string) random_int(100000000000, 999999999999);
            } while (static::where('uuid', $uuid)->exists());
            
            $room->uuid = $uuid;
        });
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'study_room_users')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(StudyRoomMessage::class);
    }
}
