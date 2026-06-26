@if(!empty($schemas))
    @foreach($schemas as $schema)
        @if(!empty($schema))
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        @endif
    @endforeach
@endif
