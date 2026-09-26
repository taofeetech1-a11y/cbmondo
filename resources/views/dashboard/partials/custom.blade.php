@if ($paginator->hasPages())
    <div class="pager">
        @if ($paginator->onFirstPage())
            <button class="wide" disabled>Previous</button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"><button class="wide" type="button">Previous</button></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <button disabled>{{ $element }}</button>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <button class="active">{{ $page }}</button>
                    @else
                        <a href="{{ $url }}"><button type="button">{{ $page }}</button></a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"><button class="wide" type="button">Next</button></a>
        @else
            <button class="wide" disabled>Next</button>
        @endif
    </div>
@endif
