<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        config(['services.front.revalidate_secret' => 'secret', 'services.front.url' => 'http://front.test']);
        Http::fake();
    }

    private function settings(array $overrides = []): array
    {
        $input = [];
        foreach (SettingService::fields() as $key => $field) {
            $input[$key] = ($field['translatable'] ?? false) ? ['fr' => SettingService::formValue($key, 'fr'), 'en' => SettingService::formValue($key, 'en')] : SettingService::formValue($key);
        }

        return ['settings' => array_replace_recursive($input, $overrides)];
    }

    public function test_site_api_returns_defaults_and_seeded_menus(): void
    {
        $data = $this->getJson('/api/v1/site?locale=en')->assertOk()->json('data');

        $this->assertSame('Unlock the potential of your ideas', $data['settings']['site_slogan']);
        $this->assertSame("+237 678 261 382\n+237 694 072 875", $data['settings']['contact_phones']);
        $this->assertNull($data['settings']['contact_email']);
        $this->assertSame(['Solutions', 'About', 'Testimonials', 'Resources', 'Contact', 'Client portal'], array_column($data['menus']['header'], 'label'));
        $this->assertSame('mobile', $data['menus']['header'][5]['display']);
        $this->assertSame(['Solutions', 'Company', 'Resources'], array_column($data['menus']['footer'], 'label'));
        $this->assertSame('/services?domaine=ia#chiffrage', $data['menus']['footer'][0]['links'][0]['url']);
        $this->assertCount(2, $data['menus']['legal']);
    }

    public function test_admin_updates_settings_and_front_is_revalidated(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertOk()->assertSee('Réseaux sociaux')->assertSee('+237 694 072 875');

        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->settings([
            'site_slogan' => ['fr' => 'Nouveau slogan', 'en' => ''],
            'contact_email' => 'contact@example.com',
            'contact_phones' => " +237 600 000 000 \r\n\r\n+237 611 111 111 ",
            'social_linkedin' => 'https://www.linkedin.com/company/example',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $fr = $this->getJson('/api/v1/site?locale=fr')->json('data.settings');
        $en = $this->getJson('/api/v1/site?locale=en')->json('data.settings');
        $this->assertSame('Nouveau slogan', $fr['site_slogan']);
        $this->assertSame('Nouveau slogan', $en['site_slogan'], 'EN vide = version FR');
        $this->assertSame("+237 600 000 000\n+237 611 111 111", $fr['contact_phones']);
        $this->assertSame('https://www.linkedin.com/company/example', $fr['social_linkedin']);

        Http::assertSent(fn (Request $r) => $r->url() === 'http://front.test/api/revalidate' && $r['tags'] === ['site']);
    }

    public function test_a_cleared_setting_stays_empty_instead_of_falling_back_to_its_default(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->settings(['contact_phones' => '']))->assertSessionHasNoErrors();

        $this->assertNull($this->getJson('/api/v1/site')->json('data.settings.contact_phones'));
    }

    public function test_settings_are_validated(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->settings([
            'contact_email' => 'pas-un-email',
            'social_x' => 'javascript:alert(1)',
            'header_cta_url' => '//evil.example',
            'contact_whatsapp' => 'abc',
        ]))->assertSessionHasErrors(['settings.contact_email', 'settings.social_x', 'settings.header_cta_url', 'settings.contact_whatsapp']);
    }

    public function test_admin_manages_menu_items(): void
    {
        $this->actingAs($this->admin)->get(route('admin.menus.index'))->assertOk()->assertSee('Pied de page')->assertSee('/tech-insights');

        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'header'), [
            'label' => ['fr' => 'Blog', 'en' => ''], 'url' => 'https://blog.example.com', 'display' => 'desktop', 'new_tab' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $item = MenuItem::where('menu', 'header')->latest('id')->firstOrFail();
        $this->assertSame(6, $item->sort_order);
        $this->assertTrue($item->new_tab);

        $this->actingAs($this->admin)->patch(route('admin.menus.items.move', $item), ['direction' => 'up'])->assertRedirect();
        $this->assertSame('Blog', $this->getJson('/api/v1/site?locale=en')->json('data.menus.header.5.label'));

        $this->actingAs($this->admin)->get(route('admin.menus.items.edit', $item))->assertOk()->assertSee('value="Blog"', false);
        $this->actingAs($this->admin)->put(route('admin.menus.items.update', $item), ['label' => ['fr' => 'Le blog', 'en' => 'Blog'], 'url' => '/tech-insights', 'display' => 'all'])->assertSessionHasNoErrors();
        $this->assertSame('Le blog', $item->fresh()->getTranslation('label', 'fr'));

        $this->actingAs($this->admin)->delete(route('admin.menus.items.destroy', $item))->assertRedirect();
        $this->assertModelMissing($item);
        Http::assertSent(fn (Request $r) => $r['tags'] === ['site']);
    }

    public function test_footer_columns_group_links_and_cascade_on_delete(): void
    {
        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'footer'), ['label' => ['fr' => 'Réseaux'], 'url' => '/ignored'])->assertSessionHasNoErrors();
        $column = MenuItem::where('menu', 'footer')->whereNull('parent_id')->latest('id')->firstOrFail();
        $this->assertNull($column->url, 'une colonne n\'a pas de lien');

        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'footer'), ['label' => ['fr' => 'Carrières'], 'parent_id' => $column->id])->assertSessionHasErrors('url');
        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'footer'), ['label' => ['fr' => 'Carrières'], 'parent_id' => $column->id, 'url' => '/carrieres'])->assertSessionHasNoErrors();

        $footer = $this->getJson('/api/v1/site')->json('data.menus.footer');
        $this->assertSame(['label' => 'Réseaux', 'links' => [['label' => 'Carrières', 'url' => '/carrieres', 'newTab' => false, 'display' => 'all']]], end($footer));

        $this->actingAs($this->admin)->delete(route('admin.menus.items.destroy', $column));
        $this->assertDatabaseMissing('menu_items', ['url' => '/carrieres']);
    }

    public function test_menu_links_reject_unsafe_urls_and_foreign_parents(): void
    {
        $headerItem = MenuItem::where('menu', 'header')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'header'), ['label' => ['fr' => 'X'], 'url' => 'javascript:alert(1)'])->assertSessionHasErrors('url');
        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'footer'), ['label' => ['fr' => 'X'], 'url' => '/x', 'parent_id' => $headerItem->id])->assertSessionHasErrors('parent_id');
        $this->actingAs($this->admin)->post(route('admin.menus.items.store', 'inconnu'), ['label' => ['fr' => 'X'], 'url' => '/x'])->assertNotFound();
    }

    public function test_guests_cannot_manage_settings_or_menus(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.menus.items.store', 'header'), ['label' => ['fr' => 'X'], 'url' => '/x'])->assertRedirect(route('admin.login'));
    }
}
