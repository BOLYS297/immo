<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Image de fond des sections, bloc Équipe avec photos, suppression d'une page, animation du logo. */
class SectionLookTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Page $page;

    private Media $media;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('fond.jpg', 1600, 900)]]);
        $this->media = Media::latest('id')->firstOrFail();
    }

    public function test_a_section_gets_a_background_image_with_an_overlay(): void
    {
        $section = $this->page->sections()->create(['type' => 'text', 'content' => ['fr' => ['title' => 'Titre']]]);

        $this->actingAs($this->admin)->get(route('admin.sections.edit', $section))->assertOk()->assertSee('Image de fond (facultative)')->assertSee('Revenir aux réglages par défaut');
        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), [
            'content' => ['fr' => ['title' => 'Titre']], 'is_visible' => '1',
            'settings' => ['background' => 'satin', 'background_image' => (string) $this->media->id, 'overlay' => 'light'],
        ])->assertSessionHasNoErrors();

        $this->page->publish();
        $settings = $this->getJson('/api/v1/pages/offre')->json('data.sections.0.settings');
        $this->assertSame([$this->media->id, 1600, 'light'], [$settings['background_image']['id'], $settings['background_image']['width'], $settings['overlay']]);

        // Image utilisée en fond : protégée dans la médiathèque
        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $this->media))->assertSessionHas('error');
        $this->assertModelExists($this->media);
    }

    public function test_background_image_must_exist_and_can_be_removed(): void
    {
        $section = $this->page->sections()->create(['type' => 'cards', 'settings' => ['background_image' => (string) $this->media->id]]);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['is_visible' => '1', 'settings' => ['background_image' => '999999']])->assertSessionHasErrors('settings.background_image');
        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['is_visible' => '1', 'settings' => ['background_image' => '']])->assertSessionHasNoErrors();
        $this->assertNull($section->fresh()->resolvedSettings()['background_image']);
        $this->assertSame('dark', $section->fresh()->resolvedSettings()['overlay'], 'voile par défaut');
    }

    public function test_team_members_block_with_photos(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pages.sections.store', $this->page), ['type' => 'team_members'])->assertRedirect();
        $section = $this->page->sections()->where('type', 'team_members')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), [
            'is_visible' => '1',
            'content' => ['fr' => ['title' => 'L’équipe', 'members' => [['photo' => (string) $this->media->id, 'name' => 'Awa', 'role' => 'IA & Data', 'bio' => 'Associée', 'link' => 'https://www.linkedin.com/in/awa']]]],
            'settings' => ['columns' => '3', 'photo_shape' => 'square'],
        ])->assertSessionHasNoErrors();

        $this->page->publish();
        $member = $this->getJson('/api/v1/pages/offre')->json('data.sections.0.content.members.0');
        $this->assertSame(['Awa', $this->media->id], [$member['name'], $member['photo']['id']]);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['is_visible' => '1', 'content' => ['fr' => ['members' => [['name' => 'X', 'link' => 'javascript:alert(1)']]]]])->assertSessionHasErrors();
    }

    public function test_an_ordinary_page_can_be_deleted_from_its_edit_screen(): void
    {
        $this->actingAs($this->admin)->get(route('admin.pages.edit', $this->page))->assertSee('Supprimer cette page');
        $this->actingAs($this->admin)->get(route('admin.pages.edit', Page::create(['slug' => 'contact', 'title' => ['fr' => 'Contact']])))->assertDontSee('Supprimer cette page');

        $this->actingAs($this->admin)->delete(route('admin.pages.destroy', $this->page))->assertRedirect(route('admin.pages.index'));
        $this->assertModelMissing($this->page);
    }

    public function test_logo_animation_setting_is_validated_and_exposed(): void
    {
        $input = ['settings' => []];
        foreach (SettingService::fields() as $key => $field) {
            $input['settings'][$key] = ($field['translatable'] ?? false) ? ['fr' => SettingService::formValue($key, 'fr'), 'en' => SettingService::formValue($key, 'en')] : SettingService::formValue($key);
        }

        $this->assertSame('auto', $this->getJson('/api/v1/site')->json('data.settings.logo_animation'));
        $this->actingAs($this->admin)->post(route('admin.settings.update'), array_replace_recursive($input, ['settings' => ['logo_animation' => 'toujours']]))->assertSessionHasErrors('settings.logo_animation');
        $this->actingAs($this->admin)->post(route('admin.settings.update'), array_replace_recursive($input, ['settings' => ['logo_animation' => 'always']]))->assertSessionHasNoErrors();
        $this->assertSame('always', $this->getJson('/api/v1/site')->json('data.settings.logo_animation'));
    }
}
