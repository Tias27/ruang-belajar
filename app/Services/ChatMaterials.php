<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ChatMaterials
{
    public function forSession(ChatSession $session): Collection
    {
        if (! $session->exists) return collect();
        $last = $session->messages()->where('role', 'user')->latest('id')->first();
        $ids = collect($last?->metadata['context_materials'] ?? $last?->metadata['materials'] ?? [])->pluck('id')->all();

        return $this->ownedDocuments($session->user_id, $ids);
    }

    public function fromRequest(Request $request, ?ChatSession $session = null): Collection
    {
        $data = $request->validate([
            'materials_present' => ['sometimes', 'boolean'],
            'material_ids' => ['sometimes', 'array', 'max:10'],
            'material_ids.*' => ['required', 'string', 'max:40', 'distinct'],
        ]);
        if (! $request->boolean('materials_present') && ! array_key_exists('material_ids', $data)) {
            return $session ? $this->forSession($session) : collect();
        }
        $ids = $data['material_ids'] ?? [];
        $documents = $this->ownedDocuments($request->user()->id, $ids);
        if ($documents->count() !== count($ids)) {
            throw ValidationException::withMessages(['material_ids' => 'Materi tidak tersedia atau teksnya belum terbaca. Pilih ulang materi']);
        }

        return $documents;
    }

    public function describe(Collection $documents): array
    {
        return $documents->map(fn (Document $document) => [
            'id' => $document->public_id, 'title' => $document->title,
            'extension' => strtoupper($document->extension),
        ])->values()->all();
    }

    public function snippets(Collection $documents, string $question, string $answer = ''): array
    {
        $sources = app(LearningSourceService::class);

        return $documents->flatMap(fn (Document $document) => $sources->chatSnippetsFor($document, $question, $answer, 1))->all();
    }

    private function ownedDocuments(int $userId, array $ids): Collection
    {
        if ($ids === []) return collect();

        return Document::where('user_id', $userId)->whereIn('public_id', $ids)
            ->get()->filter(fn (Document $document) => filled($document->extracted_text))
            ->sortBy(fn (Document $document) => array_search($document->public_id, $ids, true))->values();
    }
}
