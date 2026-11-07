<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Listes de boutons des sections (champ « buttons ») et conversion des anciens boutons uniques. */
class SectionButtonsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
    }

    public function test_a_card_and_a_section_take_several_buttons(): void
    {
        $cards = $this->page->sections()->create(['type' => 'cards', 'content' => ['fr' => []]]);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $cards), [
            'is_visible' => '1',
            'content' => ['fr' => ['items' => [['title' => 'Audit', 'buttons' => [
                ['label' => 'Réserver', 'href' => '/contact'],
                ['label' => '', 'href' => 'https://example.com/brochure.pdf'],
                ['label' => 'Sans lien', 'href' => ''], // ignoré
            ]]]]],
        ])->assertSessionHasNoErrors();

        $this->assertEquals([['label' => 'Réserver', 'href' => '/contact'], ['label' => '', 'href' => 'https://example.com/brochure.pdf']], $cards->fresh()->contentFor('fr')['items'][0]['buttons']);

        $tooMany = array_fill(0, 5, ['label' => 'Lien', 'href' => '/contact']);
        $this->actingAs($this->admin)->put(route('admin.sections.update', $cards), ['is_visible' => '1', 'content' => ['fr' => ['items' => [['title' => 'Audit', 'buttons' => $tooMany]]]]])
            ->assertSessionHasErrors('content.fr.items.0.buttons');

        $band = $this->page->sections()->create(['type' => 'cta_band', 'content' => ['fr' => []]]);
        $this->actingAs($this->admin)->put(route('admin.sections.update', $band), [
            'is_visible' => '1',
            'content' => ['fr' => ['title' => 'Parlons-en', 'buttons' => [['label' => 'Devis', 'href' => '/contact'], ['label' => 'Solutions', 'href' => '/services'], ['label' => 'Appeler', 'href' => 'https://wa.me/237600000000']]]],
        ])->assertSessionHasNoErrors();
        $this->page->publish();

        $published = collect($this->getJson('/api/v1/pages/offre')->assertOk()->json('data.sections'))->firstWhere('type', 'cta_band');
        $this->assertSame(['Devis', 'Solutions', 'Appeler'], array_column($published['content']['buttons'], 'label'));
    }

    public function test_the_section_editor_renders_nested_button_lists(): void
    {
        $cards = $this->page->sections()->create(['type' => 'cards', 'content' => ['fr' => ['items' => [['title' => 'Audit', 'buttons' => [['label' => 'Réserver', 'href' => '/contact']]]]]]]);

        $this->actingAs($this->admin)->get(route('admin.sections.edit', $cards))
            ->assertOk()
            ->assertSee('name="content[fr][items][0][buttons][0][href]"', false)
            ->assertSee('name="content[fr][items][0][buttons][__SUB__][label]"', false) // modèle des boutons d'une carte existante
            ->assertSee('name="content[fr][items][__INDEX__][buttons][__SUB__][label]"', false); // modèle d'une nouvelle carte
    }

    public function test_legacy_single_buttons_are_converted_to_lists(): void
    {
        $content = [
            'fr' => ['title' => 'Parlons-en', 'primary' => 'Devis', 'primary_href' => '/devis', 'secondary' => 'Solutions', 'secondary_href' => ''],
            'en' => ['title' => 'Let’s talk', 'primary' => 'Quote', 'secondary' => ''],
        ];
        $band = $this->page->sections()->create(['type' => 'cta_band', 'content' => $content]);
        $team = $this->page->sections()->create(['type' => 'team_members', 'content' => ['fr' => ['members' => [['name' => 'Awa', 'link' => 'https://www.linkedin.com/in/awa'], ['name' => 'Paul', 'link' => '']]]]]);
        $cards = $this->page->sections()->create(['type' => 'cards', 'content' => ['fr' => ['items' => [['title' => 'Audit', 'link_label' => '', 'href' => '/contact'], ['title' => 'Sans lien', 'link_label' => 'Voir', 'href' => '']]]]]);
        $revision = $this->page->revisions()->create(['snapshot' => ['sections' => [['type' => 'callout', 'content' => ['fr' => ['title' => 'Règle', 'link_label' => 'En savoir plus', 'href' => '/regles']]]]]]);

        (require database_path('migrations/2026_11_07_110000_convert_section_buttons_to_lists.php'))->up();

        $band->refresh();
        $this->assertEquals([['label' => 'Devis', 'href' => '/devis'], ['label' => 'Solutions', 'href' => '/services']], $band->contentFor('fr')['buttons']);
        $this->assertEquals([['label' => 'Quote', 'href' => '/devis']], $band->contentFor('en')['buttons'], 'lien vide en anglais : celui de la version française');
        $this->assertArrayNotHasKey('primary', $band->contentFor('fr'));

        $members = $team->fresh()->contentFor('fr')['members'];
        $this->assertEquals([['network' => 'linkedin', 'url' => 'https://www.linkedin.com/in/awa']], $members[0]['socials']);
        $this->assertSame(['name' => 'Paul'], $members[1]);

        $items = $cards->fresh()->contentFor('fr')['items'];
        $this->assertEquals([['label' => '', 'href' => '/contact']], $items[0]['buttons']);
        $this->assertSame(['title' => 'Sans lien'], $items[1]);

        $snapshot = json_decode(DB::table('page_revisions')->where('id', $revision->id)->value('snapshot'), true);
        $this->assertEquals([['label' => 'En savoir plus', 'href' => '/regles']], $snapshot['sections'][0]['content']['fr']['buttons']);
    }
}
