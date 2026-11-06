<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MaterialShelf
{
    public function paginate(User $user, int $perPage, string $pageName, ?int $page = null): LengthAwarePaginator
    {
        $documents = $user->documents()->whereNull('folder_id')
            ->selectRaw("id, public_id, title, extension, size, created_at, 'document' as type, 0 as documents_count");
        $folders = $user->documentFolders()
            ->selectRaw("id, public_id, name as title, NULL as extension, 0 as size, created_at, 'folder' as type")
            ->selectSub(fn ($query) => $query->from('documents')->selectRaw('COUNT(*)')
                ->whereColumn('documents.folder_id', 'document_folders.id'), 'documents_count');

        // Page the combined shelf in SQL so folders and files share one stable order.
        return DB::query()->fromSub($documents->toBase()->unionAll($folders->toBase()), 'materials')
            ->orderByDesc('created_at')->orderBy('type')->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName, $page)
            ->withQueryString()
            ->through(fn ($material) => [
                'id' => $material->id,
                'type' => $material->type,
                'title' => $material->title,
                'meta' => $material->type === 'folder'
                    ? $material->documents_count.' file gabungan'
                    : strtoupper($material->extension).' | '.number_format($material->size / 1024, 1).' KB',
                'url' => route($material->type === 'folder' ? 'folders.show' : 'documents.show', $material->public_id),
                'created_at' => Carbon::parse($material->created_at),
            ]);
    }
}
