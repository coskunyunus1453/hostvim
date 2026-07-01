@props([
    'navRoots',
    'current' => null,
])

<nav class="docs-sidebar" aria-label="{{ landing_t('docs.sidebar_label') }}">
    <p class="docs-sidebar__title">{{ landing_t('docs.sidebar_title') }}</p>
    <ul class="docs-sidebar__list">
        @foreach ($navRoots as $root)
            <li class="docs-sidebar__group">
                <a href="{{ route('docs.show', $root->slug) }}"
                   @class([
                       'docs-sidebar__link docs-sidebar__link--root',
                       'docs-sidebar__link--active' => $current && ($current->id === $root->id || $current->parent_id === $root->id),
                   ])>
                    {{ $root->title }}
                </a>
                @if ($root->children->isNotEmpty())
                    <ul class="docs-sidebar__children">
                        @foreach ($root->children as $child)
                            <li>
                                <a href="{{ route('docs.show', $child->slug) }}"
                                   @class([
                                       'docs-sidebar__link',
                                       'docs-sidebar__link--active' => $current && $current->id === $child->id,
                                   ])>
                                    {{ $child->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
