<?php

namespace Tests\Feature;

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPageApiTest extends TestCase
{
    use RefreshDatabase;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->page = Page::create(['slug' => 'home', 'title' => ['fr' => 'Accueil', 'en' => 'Home'], 'is_published' => true]);
    }

    public function test_returns_visible_sections_in_order_for_the_requested_locale(): void
    {
        $this->page->sections()->create(['type' => 'cta_band', 'sort_order' => 2, 'content' => ['fr' => ['title' => 'Parlons'], 'en' => ['title' => 'Let’s talk']]]);
        $this->page->sections()->create(['type' => 'hero', 'sort_order' => 1, 'content' => ['fr' => ['title' => 'Bonjour'], 'en' => ['title' => 'Hello']]]);
        $this->page->sections()->create(['type' => 'statement', 'sort_order' => 0, 'is_visible' => false, 'content' => ['fr' => ['text' => 'Masquée']]]);

        Page::where('slug', 'home')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/home?locale=en')
            ->assertOk()
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.title', 'Home')
            ->assertJsonCount(2, 'data.sections')
            ->assertJsonPath('data.sections.0.type', 'hero')
            ->assertJsonPath('data.sections.0.content.title', 'Hello')
            ->assertJsonPath('data.sections.1.type', 'cta_band');
    }

    public function test_empty_fields_fall_back_to_default_locale_without_mixing_list_items(): void
    {
        $this->page->sections()->create(['type' => 'services', 'content' => [
            'fr' => ['title' => 'Ce que nous faisons', 'all' => 'Tout voir', 'rows' => [['title' => 'IA'], ['title' => 'Web']]],
            'en' => ['title' => '', 'all' => 'See all', 'rows' => [['title' => 'AI']]],
        ]]);

        Page::where('slug', 'home')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/home?locale=en')
            ->assertOk()
            ->assertJsonPath('data.sections.0.content.title', 'Ce que nous faisons')
            ->assertJsonPath('data.sections.0.content.all', 'See all')
            ->assertJsonPath('data.sections.0.content.rows', [['title' => 'AI']]);
    }

    public function test_unknown_locale_uses_default_locale(): void
    {
        Page::where('slug', 'home')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/home?locale=de')->assertOk()->assertJsonPath('data.locale', 'fr')->assertJsonPath('data.title', 'Accueil');
    }

    public function test_empty_section_content_is_an_object_so_the_front_uses_its_defaults(): void
    {
        $this->page->sections()->create(['type' => 'hero']);

        Page::where('slug', 'home')->firstOrFail()->publish();
        $this->assertStringContainsString('"content":{}', $this->get('/api/v1/pages/home')->getContent());
    }

    public function test_sections_of_unknown_type_are_not_sent(): void
    {
        $this->page->sections()->create(['type' => 'removed_block', 'content' => ['fr' => ['title' => 'x']]]);

        Page::where('slug', 'home')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/home')->assertOk()->assertJsonCount(0, 'data.sections');
    }

    public function test_unpublished_or_missing_pages_return_404(): void
    {
        $this->getJson('/api/v1/pages/home')->assertNotFound(); // jamais publiée

        $this->page->publish();
        $this->page->update(['is_published' => false]); // mise hors ligne

        $this->getJson('/api/v1/pages/home')->assertNotFound();
        $this->getJson('/api/v1/pages/nope')->assertNotFound();
    }

    public function test_seeder_creates_the_home_page_once(): void
    {
        $this->page->delete();

        $this->seed(PageSeeder::class);
        $this->seed(PageSeeder::class);

        $this->assertSame(1, Page::where('slug', 'home')->count());
        Page::where('slug', 'home')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/home?locale=en')
            ->assertOk()
            ->assertJsonCount(8, 'data.sections')
            ->assertJsonPath('data.sections.0.content.title', 'Turn your ideas into digital experiences.');
    }
}
