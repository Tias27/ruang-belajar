@if($paginator->hasPages())
    <div class="pagination-slot">
    <nav class="compact-pagination" data-page-name="{{ $paginator->getPageName() }}" aria-label="Halaman daftar">
        @if($paginator->onFirstPage())
            <span class="pagination-step" aria-disabled="true"><span aria-hidden="true">&lsaquo;</span> Sebelumnya</span>
        @else
            <a class="pagination-step" href="{{ $paginator->previousPageUrl() }}" rel="prev"><span aria-hidden="true">&lsaquo;</span> Sebelumnya</a>
        @endif
        <span class="pagination-position" aria-label="Halaman {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) dari {{ $paginator->lastPage() }}@endif">{{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) / {{ $paginator->lastPage() }}@endif</span>
        @if($paginator->hasMorePages())
            <a class="pagination-step" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya <span aria-hidden="true">&rsaquo;</span></a>
        @else
            <span class="pagination-step" aria-disabled="true">Berikutnya <span aria-hidden="true">&rsaquo;</span></span>
        @endif
    </nav>
    </div>
@endif
