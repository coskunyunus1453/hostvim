<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'blog_category_id', 'user_id', 'title', 'slug', 'content', 'excerpt',
        'featured_image', 'meta_title', 'meta_description', 'og_image', 'meta_keywords', 'no_index',
        'is_published', 'published_at', 'views',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'no_index' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Verilen tabandan benzersiz bir slug üretir (gerekirse -2, -3 ekler).
     * Boş gelirse başlıktan türetir. $ignoreId mevcut kaydı hariç tutar.
     */
    public static function uniqueSlug(?string $base, ?string $fallbackTitle = null, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base ?: (string) $fallbackTitle);
        if ($slug === '') {
            $slug = 'yazi-'.Str::lower(Str::random(6));
        }

        $original = $slug;
        $i = 2;
        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $original.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
