<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Listes traduites : chaque élément anglais traduit l'élément français de même rang. */
class SectionTranslationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Page $page;

    private PageSection $cards;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
        $this->cards = $this->page->sections()->create(['type' => 'cards', 'content' => ['fr' => [
            'title' => 'Nos offres',
            'items' => [
                ['title' => 'Audit', 'text' => 'Diagnostic complet', 'buttons' => [['label' => 'Réserver', 'href' => '/contact']]],
                ['title' => 'Formation', 'text' => 'Ateliers sur site'],
                ['title' => 'Support', 'text' => 'Assistance 7j/7'],
            ],
        ]]]);
    }

    private function save(array $en): void
    {
        $this->actingAs($this->admin)->put(route('admin.sections.update', $this->cards), [
            'is_visible' => '1',
            'content' => ['fr' => $this->cards->contentFor('fr'), 'en' => $en],
        ])->assertSessionHasNoErrors();
    }

    private function publishedItems(string $locale): array
    {
        $this->page->publish();

        return collect($this->getJson("/api/v1/pages/offre?locale={$locale}")->assertOk()->json('data.sections'))->firstWhere('type', 'cards')['content']['items'];
    }

    public function test_each_translated_item_completes_the_french_item_of_the_same_rank(): void
    {
        $this->save(['items' => [
            ['title' => 'Audit (EN)', 'text' => '', 'buttons' => [['label' => 'Book', 'href' => '']]],
            ['title' => '', 'text' => ''], // non traduit : garde sa place
            ['title' => '', 'text' => 'Support 24/7'],
            ['title' => '', 'text' => ''], // vide en fin de liste : retiré
        ]]);

        $this->assertCount(3, $this->cards->fresh()->contentFor('en')['items']);

        $items = $this->publishedItems('en');
        $this->assertCount(3, $items);
        $this->assertSame(['Audit (EN)', 'Diagnostic complet'], [$items[0]['title'], $items[0]['text']]);
        $this->assertSame([['label' => 'Book', 'href' => '/contact']], $items[0]['buttons'], 'lien repris du bouton français de même rang');
        $this->assertSame(['Formation', 'Ateliers sur site'], [$items[1]['title'], $items[1]['text']]);
        $this->assertSame(['Support', 'Support 24/7'], [$items[2]['title'], $items[2]['text']]);
    }

    public function test_an_english_only_item_is_kept_and_french_lists_are_unchanged(): void
    {
        $this->save(['items' => [[], [], [], ['title' => 'Consulting']]]);

        $this->assertSame(['Audit', 'Formation', 'Support', 'Consulting'], array_column($this->publishedItems('en'), 'title'));
        $this->assertSame(['Audit', 'Formation', 'Support'], array_column($this->publishedItems('fr'), 'title'));

        // Langue par défaut : un élément vide est retiré, comme avant.
        $this->actingAs($this->admin)->put(route('admin.sections.update', $this->cards), [
            'is_visible' => '1', 'content' => ['fr' => ['items' => [['title' => 'Audit'], ['title' => ''], ['title' => 'Support']]]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['Audit', 'Support'], array_column($this->cards->fresh()->contentFor('fr')['items'], 'title'));
    }

    public function test_the_english_tab_mirrors_the_french_list_with_hints(): void
    {
        $this->actingAs($this->admin)->get(route('admin.sections.edit', $this->cards))
            ->assertOk()
            ->assertSee('name="content[en][items][2][title]"', false) // trois éléments à traduire, sans les créer à la main
            ->assertSee('placeholder="Formation"', false)
            ->assertSee('placeholder="Réserver"', false) // bouton de la carte, traduit au même rang
            ->assertSee('data-repeater-mirror', false)
            ->assertSee('traduction de « Audit »', false)
            ->assertDontSee('name="content[fr][items][0][title]" value="" placeholder', false);
    }
}
