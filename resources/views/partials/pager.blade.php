@if ($paginator->hasPages())
    <nav class="pager">
        @if ($paginator->onFirstPage())
            <span class="pager-disabled">前へ</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">前へ</a>
        @endif
        <span>{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">次へ</a>
        @else
            <span class="pager-disabled">次へ</span>
        @endif
    </nav>
@endif
