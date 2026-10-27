<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsBlocksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $this->page = Page::create(['slug' => 'demo', 'title' => ['fr' => 'Démo'], 'is_published' => true]);
    }

    public function test_presentation_settings_are_saved_and_sent_with_defaults(): void
    {
        $cards = $this->page->sections()->create(['type' => 'cards', 'sort_order' => 0]);
        $this->page->sections()->create(['type' => 'text', 'sort_order' => 1]);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $cards), [
            'is_visible' => '1',
            'settings' => ['background' => 'dark', 'spacing' => 'compact', 'columns' => '2', 'unknown' => 'x'],
            'content' => ['fr' => ['title' => 'Nos offres', 'items' => [['title' => 'Audit', 'text' => '**Rapide** et [clair](/contact)']]]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(['background' => 'dark', 'spacing' => 'compact', 'columns' => '2', 'background_image' => null, 'overlay' => 'dark'], $cards->fresh()->settings);

        Page::where('slug', 'demo')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/demo')
            ->assertOk()
            ->assertJsonPath('data.sections.0.settings', ['background' => 'dark', 'spacing' => 'compact', 'columns' => '2', 'background_image' => null, 'overlay' => 'dark'])
            ->assertJsonPath('data.sections.0.content.items.0.text', '**Rapide** et [clair](/contact)')
            ->assertJsonPath('data.sections.1.settings', ['background' => 'satin', 'spacing' => 'normal', 'align' => 'left', 'background_image' => null, 'overlay' => 'dark']);
    }

    public function test_dynamic_and_functional_blocks_send_their_settings(): void
    {
        $this->page->sections()->create(['type' => 'posts_list', 'settings' => ['count' => '6'], 'content' => ['fr' => ['title' => 'À lire']]]);
        $this->page->sections()->create(['type' => 'quote_form', 'settings' => ['default_domain' => 'infra'], 'sort_order' => 1]);
        $this->page->sections()->create(['type' => 'booking', 'sort_order' => 2]);

        Page::where('slug', 'demo')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/demo')
            ->assertOk()
            ->assertJsonPath('data.sections.0.settings.count', '6')
            ->assertJsonPath('data.sections.1.settings.default_domain', 'infra')
            ->assertJsonPath('data.sections.2.settings.background', 'satin');

        $section = $this->page->sections()->where('type', 'quote_form')->first();
        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['settings' => ['default_domain' => 'marketing']])
            ->assertSessionHasErrors('settings.default_domain');
    }

    public function test_invalid_setting_values_are_rejected(): void
    {
        $section = $this->page->sections()->create(['type' => 'stats']);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['settings' => ['background' => 'rouge']])
            ->assertSessionHasErrors('settings.background');
    }

    public function test_blocks_without_settings_send_an_empty_object(): void
    {
        $this->page->sections()->create(['type' => 'hero']);

        Page::where('slug', 'demo')->firstOrFail()->publish();
        $this->assertStringContainsString('"settings":{}', $this->get('/api/v1/pages/demo')->getContent());
    }

    public function test_section_editor_shows_settings_and_rich_text_help(): void
    {
        $section = $this->page->sections()->create(['type' => 'faq', 'settings' => ['background' => 'sky']]);

        $this->actingAs($this->admin)->get(route('admin.sections.edit', $section))
            ->assertOk()
            ->assertSee('Présentation')
            ->assertSee('<option value="sky" selected>', false)
            ->assertSee('**gras**');

        $this->actingAs($this->admin)->get(route('admin.pages.edit', $this->page))
            ->assertOk()
            ->assertSee('<optgroup label="Blocs génériques">', false);
    }
}
