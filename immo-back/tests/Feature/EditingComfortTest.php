<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EditingComfortTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $this->page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
        foreach (['hero', 'text', 'faq'] as $order => $type) {
            $this->page->sections()->create(['type' => $type, 'sort_order' => $order, 'content' => ['fr' => ['title' => ucfirst($type)]]]);
        }
        $this->page->publish();
    }

    private function ids(): array
    {
        return $this->page->sections()->pluck('id')->all();
    }

    public function test_sections_are_reordered_by_drag_and_drop_as_a_draft(): void
    {
        [$hero, $text, $faq] = $this->ids();

        $this->actingAs($this->editor)->patchJson(route('admin.pages.sections.reorder', $this->page), ['ids' => [$faq, $hero, $text]])->assertOk();

        $this->assertSame([$faq, $hero, $text], $this->ids());
        $this->assertTrue($this->page->fresh()->hasUnpublishedChanges(), 'nouvel ordre = brouillon');
        $this->assertSame('hero', $this->getJson('/api/v1/pages/offre')->json('data.sections.0.type'), 'le site garde l\'ordre publié');
    }

    public function test_reorder_requires_exactly_the_sections_of_the_page(): void
    {
        [$hero, $text, $faq] = $this->ids();
        $other = Page::create(['slug' => 'autre', 'title' => ['fr' => 'Autre']])->sections()->create(['type' => 'hero']);

        $this->actingAs($this->editor)->patchJson(route('admin.pages.sections.reorder', $this->page), ['ids' => [$faq, $hero]])->assertUnprocessable();
        $this->actingAs($this->editor)->patchJson(route('admin.pages.sections.reorder', $this->page), ['ids' => [$faq, $hero, $other->id]])->assertUnprocessable();
        $this->actingAs($this->editor)->patchJson(route('admin.pages.sections.reorder', $this->page), ['ids' => [$faq, $faq, $hero]])->assertUnprocessable();
        $this->assertSame([$hero, $text, $faq], $this->ids());
    }

    public function test_a_section_is_duplicated_right_below_with_all_its_content(): void
    {
        [$hero, $text, $faq] = $this->ids();
        $source = $this->page->sections()->find($text);
        $source->update(['content' => ['fr' => ['title' => 'Texte FR'], 'en' => ['title' => 'Text EN']], 'settings' => ['background' => 'dark'], 'is_visible' => false]);

        $this->actingAs($this->editor)->post(route('admin.sections.duplicate', $source))->assertRedirect()->assertSessionHas('success');

        $ids = $this->ids();
        $this->assertCount(4, $ids);
        $this->assertSame([$hero, $text], array_slice($ids, 0, 2));
        $this->assertSame($faq, $ids[3]);
        $copy = $this->page->sections()->find($ids[2]);
        $this->assertEquals(['fr' => ['title' => 'Texte FR'], 'en' => ['title' => 'Text EN']], $copy->getTranslations('content'));
        $this->assertSame(['background' => 'dark'], $copy->settings);
        $this->assertFalse($copy->is_visible);
    }

    public function test_menu_items_are_reordered_within_their_level_only(): void
    {
        $header = MenuItem::where('menu', 'header')->whereNull('parent_id')->orderBy('sort_order')->pluck('id')->all();

        $reversed = array_reverse($header);
        $this->actingAs($this->editor)->patchJson(route('admin.menus.reorder'), ['ids' => $reversed])->assertOk();
        $this->assertSame($reversed, MenuItem::where('menu', 'header')->whereNull('parent_id')->orderBy('sort_order')->pluck('id')->all());

        // Mélange de niveaux (lien d'une colonne du pied de page) ou ordre incomplet : refusé
        $footerLink = MenuItem::where('menu', 'footer')->whereNotNull('parent_id')->value('id');
        $this->actingAs($this->editor)->patchJson(route('admin.menus.reorder'), ['ids' => [...$header, $footerLink]])->assertStatus(422);
        $this->actingAs($this->editor)->patchJson(route('admin.menus.reorder'), ['ids' => array_slice($header, 1)])->assertStatus(422);
    }

    public function test_editor_screens_load_the_comfort_tools(): void
    {
        $section = $this->page->sections()->first();

        $this->actingAs($this->editor)->get(route('admin.pages.edit', $this->page))->assertOk()
            ->assertSee('data-sortable="'.route('admin.pages.sections.reorder', $this->page).'"', false)
            ->assertSee('Dupliquer')
            ->assertSee('data-recommended="60"', false)
            ->assertSee('js/admin-editor.js', false)
            ->assertSee('name="csrf-token"', false);
        $this->actingAs($this->editor)->get(route('admin.sections.edit', $section))->assertSee('data-dirty-guard', false);
    }

    public function test_authors_cannot_reorder_or_duplicate(): void
    {
        $author = User::factory()->create(['role' => 'author', 'is_active' => true]);

        $this->actingAs($author)->patchJson(route('admin.pages.sections.reorder', $this->page), ['ids' => $this->ids()])->assertForbidden();
        $this->actingAs($author)->post(route('admin.sections.duplicate', $this->page->sections()->first()))->assertForbidden();
        $this->actingAs($author)->patchJson(route('admin.menus.reorder'), ['ids' => [1]])->assertForbidden();
    }
}
