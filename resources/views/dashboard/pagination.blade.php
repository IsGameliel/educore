@if($paginator->hasPages())
    <nav class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-3 border-top" aria-label="{{ $label }} pages">
        <span class="text-muted small">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        <div class="d-flex gap-2">
            @if($paginator->onFirstPage())
                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Previous</span>
            @else
                <a class="btn btn-sm btn-outline-primary" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif
            @if($paginator->hasMorePages())
                <a class="btn btn-sm btn-outline-primary" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
