{{-- CUSTOM PAGINATION TEMPLATE LOADED --}}
@if ($paginator->hasPages())
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 20px; padding: 10px 0; background-color: #f8f9fa; border-radius: 4px;">
        <div style="flex: 1; padding-left: 10px;">
            <p class="text-muted mb-0" style="margin: 0; font-size: 14px; font-weight: 500;">
                Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
            </p>
        </div>
        <nav style="flex-shrink: 0; padding-right: 10px;">
            <ul class="pagination mb-0" style="margin: 0;">
                {{-- Previous Page Link - Removed --}}

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link - Removed --}}
            </ul>
        </nav>
    </div>
@endif
