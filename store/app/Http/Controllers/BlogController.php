<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\SeoService;

class BlogController extends Controller
{
    public function index(SeoService $seo)
    {
        $posts = BlogPost::where('is_published', true)
            ->with('category')
            ->orderByDesc('published_at')
            ->paginate(9);

        $categories = BlogCategory::orderBy('sort_order')->get();

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'Blog', 'url' => null],
        ];

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $categories,
            'seo' => $seo->forBlogIndex(),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [$seo->breadcrumbSchema($breadcrumbs)],
        ]);
    }

    public function show(string $slug, SeoService $seo)
    {
        $post = BlogPost::where('slug', $slug)->where('is_published', true)
            ->with(['category', 'author'])
            ->firstOrFail();

        $post->increment('views');

        $related = BlogPost::where('is_published', true)
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->limit(3)
            ->get();

        $breadcrumbs = [
            ['label' => 'Ana Sayfa', 'url' => route('home')],
            ['label' => 'Blog', 'url' => route('blog.index')],
            ['label' => $post->title, 'url' => null],
        ];

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
            'seo' => $seo->forBlogPost($post),
            'breadcrumbs' => $breadcrumbs,
            'schemas' => [
                $seo->breadcrumbSchema($breadcrumbs),
                $seo->articleSchema($post),
            ],
        ]);
    }
}
