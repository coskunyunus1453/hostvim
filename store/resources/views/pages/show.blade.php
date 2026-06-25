@extends('layouts.app')

@section('content')
<section class="py-16">
    <div class="mx-auto max-w-4xl px-4 lg:px-8">
        <h1 class="text-4xl font-extrabold text-stone-900">{{ $page->title }}</h1>
        @if($page->excerpt)<p class="mt-4 text-lg text-stone-600">{{ $page->excerpt }}</p>@endif
        <div class="prose-hostvim mt-10">{!! safe_html($page->content) !!}</div>
    </div>
</section>
@endsection
