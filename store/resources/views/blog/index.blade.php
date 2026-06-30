@extends('layouts.app')

@section('content')
<section class="bg-gradient-to-b from-orange-50/50 to-white py-16">
    <div class="mx-auto max-w-7xl px-4 text-center lg:px-8">
        <h1 class="text-4xl font-extrabold text-stone-900">Blog & Rehberler</h1>
    </div>
</section>
<section class="py-16">
    <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($posts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="card group block overflow-hidden p-0">
                    @if($post->featured_image)
                        <div class="aspect-video overflow-hidden">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->featured_image) }}"
                                 alt="{{ $post->title }}" loading="lazy"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        </div>
                    @else
                        <div class="aspect-video bg-gradient-to-br from-orange-100 to-green-100"></div>
                    @endif
                    <div class="p-6">
                        <h2 class="font-bold text-stone-900 group-hover:text-[#C2410C]">{{ $post->title }}</h2>
                        <p class="mt-2 text-sm text-stone-600">{{ Str::limit($post->excerpt ?? strip_tags($post->content), 120) }}</p>
                        <time class="mt-4 block text-xs text-stone-400">{{ $post->published_at?->format('d M Y') }}</time>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-center text-stone-500">Henüz blog yazısı yok.</p>
            @endforelse
        </div>
        <div class="mt-10">{{ $posts->links() }}</div>
    </div>
</section>
@endsection
