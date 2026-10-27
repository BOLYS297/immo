<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CmsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $this->page = Page::create(['slug' => 'home', 'title' => ['fr' => 'Accueil'], 'is_published' => true]);
    }

    public function test_guests_cannot_manage_pages(): void
    {
        $this->get(route('admin.pages.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.pages.sections.store', $this->page), ['type' => 'hero'])->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_a_page(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pages.store'), [
            'slug' => 'a-propos',
            'title' => ['fr' => 'À propos', 'en' => ''],
            'is_published' => '1',
        ])->assertRedirect();

        $page = Page::where('slug', 'a-propos')->firstOrFail();
        $this->assertSame(['fr' => 'À propos'], $page->getTranslations('title'));
    }

    public function test_page_slug_must_be_unique_and_well_formed(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pages.store'), ['slug' => 'home', 'title' => ['fr' => 'X']])->assertSessionHasErrors('slug');
        $this->actingAs($this->admin)->post(route('admin.pages.store'), ['slug' => 'Mauvais Slug', 'title' => ['fr' => 'X']])->assertSessionHasErrors('slug');
    }

    public function test_admin_pages_render(): void
    {
        $section = $this->page->sections()->create(['type' => 'services', 'content' => ['fr' => ['rows' => [['title' => 'IA', 'text' => 'Texte']]]]]);

        $this->actingAs($this->admin)->get(route('admin.pages.index'))->assertOk()->assertSee('Accueil');
        $this->actingAs($this->admin)->get(route('admin.pages.edit', $this->page))->assertOk()->assertSee('Liste des pôles / services');
        $this->actingAs($this->admin)->get(route('admin.sections.edit', $section))->assertOk()->assertSee('value="IA"', false);
    }

    public function test_added_section_goes_to_the_end_of_the_page(): void
    {
        $this->page->sections()->create(['type' => 'hero', 'sort_order' => 0]);

        $this->actingAs($this->admin)->post(route('admin.pages.sections.store', $this->page), ['type' => 'cta_band'])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.pages.sections.store', $this->page), ['type' => 'not_a_block'])->assertSessionHasErrors('type');

        $this->assertSame(['hero', 'cta_band'], $this->page->sections()->pluck('type')->all());
    }

    public function test_section_content_is_validated_and_cleaned(): void
    {
        $section = $this->page->sections()->create(['type' => 'services']);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), [
            'is_visible' => '1',
            'content' => [
                'fr' => ['title' => '  Nos pôles  ', 'unknown' => 'ignoré', 'rows' => [
                    'n3' => ['title' => 'Web', 'text' => '', 'href' => '/services'],
                    'n7' => ['title' => '', 'text' => '', 'href' => ''],
                    'n1' => ['title' => 'IA', 'text' => 'Automatiser', 'href' => 'https://immo.cm'],
                ]],
            ],
        ])->assertSessionHasNoErrors();

        $content = $section->fresh()->contentFor('fr');
        $this->assertSame('Nos pôles', $content['title']);
        $this->assertArrayNotHasKey('unknown', $content);
        $this->assertSame(['Web', 'IA'], array_column($content['rows'], 'title'));

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), [
            'content' => ['fr' => ['rows' => [['title' => 'X', 'href' => 'javascript:alert(1)']]]],
        ])->assertSessionHasErrors('content.fr.rows.0.href');
    }

    public function test_sections_can_be_moved_and_hidden(): void
    {
        $a = $this->page->sections()->create(['type' => 'hero', 'sort_order' => 0]);
        $b = $this->page->sections()->create(['type' => 'statement', 'sort_order' => 1]);

        $this->actingAs($this->admin)->patch(route('admin.sections.move', $b), ['direction' => 'up']);
        $this->assertSame([$b->id, $a->id], $this->page->sections()->pluck('id')->all());

        $this->actingAs($this->admin)->patch(route('admin.sections.move', $b), ['direction' => 'up']);
        $this->assertSame([$b->id, $a->id], $this->page->sections()->pluck('id')->all());

        $this->actingAs($this->admin)->patch(route('admin.sections.visibility', $a));
        $this->assertFalse($a->fresh()->is_visible);

        $this->actingAs($this->admin)->delete(route('admin.sections.destroy', $a));
        $this->assertModelMissing($a);
    }

    public function test_publishing_asks_the_front_to_revalidate_the_page_but_a_draft_does_not(): void
    {
        config(['services.front.url' => 'http://front.test', 'services.front.revalidate_secret' => 'secret']);
        Http::fake();
        $section = $this->page->sections()->create(['type' => 'hero']);
        // Revalidations différées (defer) de la création de la page : exécutées ici, puis oubliées.
        app(DeferredCallbackCollection::class)->invoke();
        Http::fake();

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['content' => ['fr' => ['title' => 'Nouveau']]]);
        Http::assertNothingSent();

        $this->actingAs($this->admin)->post(route('admin.pages.publish', $this->page));
        Http::assertSent(fn (Request $request) => $request->url() === 'http://front.test/api/revalidate'
            && $request->header('x-revalidate-secret') === ['secret']
            && $request['tags'] === ['pages', 'page:home']);
    }

    public function test_no_revalidation_call_without_a_secret(): void
    {
        config(['services.front.revalidate_secret' => null]);
        Http::fake();

        PageSection::create(['page_id' => $this->page->id, 'type' => 'hero']);

        Http::assertNothingSent();
    }
}
