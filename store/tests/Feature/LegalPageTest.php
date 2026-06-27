<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_legal_page_renders_and_marks_empty_company_fields(): void
    {
        Page::query()->forceCreate([
            'slug' => 'mesafeli-test',
            'title' => 'Mesafeli Test',
            'content' => '<p>Satici: [[firma_unvan]] - [[firma_eposta]]</p>',
            'is_published' => true,
        ]);

        $res = $this->get('/sayfa/mesafeli-test');

        $res->assertOk();
        $res->assertSee('Mesafeli Test');
        // Firma bilgisi girilmemisken yer tutucu "[doldurulacak]" ile isaretlenir.
        $res->assertSee('doldurulacak');
        // Ham yer tutucu kullaniciya asla sizmamali.
        $res->assertDontSee('[[firma_unvan]]', false);
        $res->assertDontSee('[[firma_eposta]]', false);
    }

    public function test_unpublished_page_returns_404(): void
    {
        Page::query()->forceCreate([
            'slug' => 'gizli-test',
            'title' => 'Gizli',
            'content' => '<p>x</p>',
            'is_published' => false,
        ]);

        $this->get('/sayfa/gizli-test')->assertNotFound();
    }

    public function test_missing_page_returns_404(): void
    {
        $this->get('/sayfa/olmayan-sayfa')->assertNotFound();
    }
}
