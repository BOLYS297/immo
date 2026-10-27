<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SystemPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->seed(PageSeeder::class);
        $this->editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
    }

    public function test_seeder_creates_every_system_page_with_the_site_texts(): void
    {
        $this->assertEqualsCanonicalizing(config('cms.system_pages'), Page::pluck('slug')->all());

        $services = $this->getJson('/api/v1/pages/services?locale=en')->assertOk()->json('data');
        $this->assertSame(['page_header', 'service_domains', 'use_cases', 'methodology', 'quote_form'], array_column($services['sections'], 'type'));
        $this->assertSame('Services & Solutions — IMMO Digital', $services['meta']['title']);
        $this->assertStringContainsString("\n", $services['sections'][1]['content']['domains'][0]['deliverables'], 'livrables : un par ligne');

        $contact = $this->getJson('/api/v1/pages/contact')->json('data.sections.0');
        $this->assertSame(['booking', 'page'], [$contact['type'], $contact['settings']['layout']]);
        $this->assertSame('all', $this->getJson('/api/v1/pages/tech-insights')->json('data.sections.1.settings.count'));
    }

    public function test_seeder_never_overwrites_a_page_edited_in_the_admin(): void
    {
        $page = Page::where('slug', 'a-propos')->firstOrFail();
        $page->sections()->first()->update(['content' => ['fr' => ['title' => 'Titre retouché']]]);

        $this->seed(PageSeeder::class);

        $this->assertSame(4, $page->sections()->count());
        $this->assertSame('Titre retouché', $page->sections()->first()->contentFor('fr')['title']);
    }

    public function test_system_pages_cannot_be_deleted_renamed_or_unpublished(): void
    {
        $page = Page::where('slug', 'services')->firstOrFail();

        $this->actingAs($this->editor)->delete(route('admin.pages.destroy', $page))->assertRedirect()->assertSessionHas('error');
        $this->assertModelExists($page);

        $this->actingAs($this->editor)->put(route('admin.pages.update', $page), [
            'slug' => 'nos-services', 'title' => ['fr' => 'Solutions', 'en' => ''], 'meta_title' => ['fr' => 'Nouveau titre SEO'], 'is_published' => '0',
        ])->assertSessionHasNoErrors();

        $page->refresh();
        $this->assertSame('services', $page->slug);
        $this->assertTrue($page->is_published);
        $this->assertSame('Nouveau titre SEO', $page->getTranslation('meta_title', 'fr'), 'le SEO reste modifiable');

        $this->actingAs($this->editor)->get(route('admin.pages.index'))->assertSee('système');
    }

    public function test_ordinary_pages_can_still_be_deleted(): void
    {
        $page = Page::create(['slug' => 'offre-noel', 'title' => ['fr' => 'Offre'], 'is_published' => true]);

        $this->actingAs($this->editor)->delete(route('admin.pages.destroy', $page))->assertRedirect(route('admin.pages.index'));
        $this->assertModelMissing($page);
    }

    public function test_new_blocks_are_editable_and_validated(): void
    {
        $page = Page::where('slug', 'recommandations')->firstOrFail();
        $callout = $page->sections()->where('type', 'callout')->firstOrFail();

        $this->actingAs($this->editor)->get(route('admin.sections.edit', $callout))->assertOk()->assertSee('Notre règle de publication');

        $this->actingAs($this->editor)->put(route('admin.sections.update', $callout), [
            'content' => ['fr' => ['title' => 'Règle', 'href' => 'javascript:alert(1)'], 'en' => []],
            'settings' => ['spacing' => 'compact'],
            'is_visible' => '1',
        ])->assertSessionHasErrors('content.fr.href');

        $team = Page::where('slug', 'a-propos')->firstOrFail()->sections()->where('type', 'team')->firstOrFail();
        $this->actingAs($this->editor)->put(route('admin.sections.update', $team), [
            'content' => ['fr' => ['title' => 'Notre équipe', 'members' => [['title' => 'IA', 'text' => 'Texte']]], 'en' => []],
            'is_visible' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertEquals([['title' => 'IA', 'text' => 'Texte']], $team->fresh()->contentFor('fr')['members']);
    }
}
