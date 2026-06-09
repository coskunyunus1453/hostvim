<?php

namespace Tests\Feature;

use App\Models\PanelRelease;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelReleaseAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_store_panel_release(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.panel-releases.store'), [
            'version' => '0.2.0',
            'channel' => 'stable',
            'profile' => 'all',
            'title' => 'Test release',
            'changelog' => 'Changelog body',
            'git_tag' => 'v0.2.0',
            'requires_engine_restart' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('panel_releases', [
            'version' => '0.2.0',
            'title' => 'Test release',
            'git_tag' => 'v0.2.0',
        ]);
    }

    public function test_store_shows_validation_errors_when_version_invalid(): void
    {
        $response = $this->actingAs($this->admin())->from(route('admin.panel-releases.create'))
            ->post(route('admin.panel-releases.store'), [
                'version' => 'not-semver',
                'channel' => 'stable',
                'profile' => 'all',
                'title' => 'Bad',
                'changelog' => 'x',
                'git_tag' => 'v0.2.0',
            ]);

        $response->assertRedirect(route('admin.panel-releases.create'));
        $response->assertSessionHasErrors('version');
    }

    public function test_publish_route_accepts_post_without_method_spoof(): void
    {
        $release = PanelRelease::query()->create([
            'version' => '0.3.0',
            'channel' => 'stable',
            'profile' => 'customer',
            'title' => 'Draft',
            'changelog' => 'Notes',
            'artifact_url' => 'https://cdn.example.com/panel-0.3.0.tar.gz',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.panel-releases.publish', $release));

        $response->assertRedirect(route('admin.panel-releases.index'));
        $release->refresh();
        $this->assertTrue($release->is_published);
        $this->assertNotNull($release->published_at);
    }

    public function test_published_release_visible_via_hub_api(): void
    {
        PanelRelease::query()->create([
            'version' => '0.4.0',
            'channel' => 'stable',
            'profile' => 'all',
            'title' => 'Live',
            'changelog' => 'Shipped',
            'artifact_url' => 'https://cdn.example.com/panel-0.4.0.tar.gz',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/panel-updates/check?current=0.1.0&profile=customer&channel=stable');

        $response->assertOk()
            ->assertJsonPath('update_available', true)
            ->assertJsonPath('latest.version', '0.4.0')
            ->assertJsonPath('latest.artifact_url', 'https://cdn.example.com/panel-0.4.0.tar.gz');
    }
}
