@extends('layouts.app')

@section('content')
<article class="py-16">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        @if($post->category)<span class="text-sm font-bold uppercase text-[#166534]">{{ $post->category->name }}</span>@endif
        <h1 class="mt-2 text-4xl font-extrabold text-stone-900">{{ $post->title }}</h1>
        <div class="mt-4 flex gap-4 text-sm text-stone-500">
            <time>{{ $post->published_at?->format('d F Y') }}</time>
            @if($post->author)<span>{{ $post->author->name }}</span>@endif
        </div>
        @if($post->featured_image)
            <div class="mt-8 overflow-hidden rounded-2xl">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                     alt="{{ $post->title }}" class="w-full object-cover">
            </div>
        @endif
        <div class="prose-hostvim mt-10">{!! safe_html($post->content) !!}</div>
    </div>
</article>
@endsection
