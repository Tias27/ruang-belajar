<?php

namespace App\Repositories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository
{
    public function latestForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Document::whereBelongsTo($user)->latest()->paginate($perPage);
    }

    public function latestForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Document::with('user')->latest()->paginate($perPage);
    }
}
