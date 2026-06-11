<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFolder;

class DocumentDeletionService
{
    public function delete(Document $document): void
    {
        $this->deletePhysicalFile($document);
        $document->delete();
    }

    public function deleteMany(iterable $documents): int
    {
        $count = 0;

        foreach ($documents as $document) {
            $this->delete($document);
            $count++;
        }

        return $count;
    }

    public function deleteFolderWithDocuments(DocumentFolder $folder): int
    {
        $deletedDocuments = $this->deleteMany($folder->documents()->get());

        $folder->delete();

        return $deletedDocuments;
    }

    private function deletePhysicalFile(Document $document): void
    {
        $absolutePath = $this->privateStoragePath($document->file_path);

        if ($absolutePath && is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private function privateStoragePath(string $relativePath): ?string
    {
        $basePath = realpath(storage_path('app/private'));
        $targetPath = realpath(storage_path('app/private/'.$relativePath));

        if (! $basePath || ! $targetPath || ! str_starts_with($targetPath, $basePath.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $targetPath;
    }
}
