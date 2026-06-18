<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('study-room.{id}', function ($user, $id) {
    $room = \App\Models\StudyRoom::find($id);
    if (!$room || $room->status !== 'active') {
        return false;
    }
    $isAuthorized = (int) $room->host_id === (int) $user->id || $room->users()->where('users.id', $user->id)->exists();
    if ($isAuthorized) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=7c5cff&color=fff',
        ];
    }
    return false;
});

