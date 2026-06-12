<x-app-layout title="Kelola Dokumen">
    <h1 class="text-2xl font-semibold text-campus-900">Kelola Dokumen</h1>
    <div class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Judul</th><th class="px-4 py-3">Pemilik</th><th class="px-4 py-3">Ukuran</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($documents as $document)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $document->title }}</td>
                        <td class="px-4 py-3">{{ $document->user?->username }}</td>
                        <td class="px-4 py-3">{{ number_format($document->size / 1024 / 1024, 2) }} MB</td>
                        <td class="px-4 py-3">{{ $document->status }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.documents.destroy', $document) }}">@csrf @method('DELETE')<button class="text-rose-700">Hapus</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</x-app-layout>
