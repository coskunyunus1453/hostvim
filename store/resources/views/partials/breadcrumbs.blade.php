@if(!empty($breadcrumbs) && count($breadcrumbs) > 1)
<nav aria-label="Breadcrumb" class="border-b border-hv-border bg-hv-surface/80">
    <div class="mx-auto max-w-7xl px-4 py-3 lg:px-8">
        <ol class="flex flex-wrap items-center gap-1 text-sm text-hv-muted">
            @foreach($breadcrumbs as $index => $crumb)
                <li class="flex items-center gap-1">
                    @if($crumb['url'] && $index < count($breadcrumbs) - 1)
                        <a href="{{ $crumb['url'] }}" class="transition hover:text-hv-primary">
                            {{ $crumb['label'] }}
                        </a>
                    @else
                        <span class="font-medium text-hv-text" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                    @if($index < count($breadcrumbs) - 1)
                        <svg class="mx-1 h-3 w-3 text-hv-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</nav>
@endif
