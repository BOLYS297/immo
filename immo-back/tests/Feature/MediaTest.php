<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    }

    private function upload(int $width = 1200, int $height = 800): Media
    {
        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('equipe.jpg', $width, $height)]])->assertSessionHasNoErrors();

        return Media::latest('id')->firstOrFail();
    }

    public function test_admin_uploads_images_with_their_dimensions(): void
    {
        $media = $this->upload();

        $this->assertSame(['public', 'equipe.jpg', 1200, 800], [$media->disk, $media->original_name, $media->width, $media->height]);
        $this->assertStringStartsWith('media/'.now()->format('Y/m').'/', $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_uploads_are_restricted_to_safe_image_formats(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $pdf = UploadedFile::fake()->create('devis.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [$svg]])->assertSessionHasErrors('files.0');
        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [$pdf]])->assertSessionHasErrors('files.0');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_guests_cannot_reach_the_media_library(): void
    {
        $this->get(route('admin.media.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('a.jpg')]])->assertRedirect(route('admin.login'));
    }

    public function test_picker_lists_media_as_json_and_uploads_return_json(): void
    {
        $media = $this->upload();

        $this->actingAs($this->admin)->getJson(route('admin.media.index', ['picker' => 1]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $media->id)
            ->assertJsonPath('data.0.name', 'equipe.jpg');

        $this->actingAs($this->admin)->postJson(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('b.png', 300, 300)]])
            ->assertCreated()
            ->assertJsonPath('data.0.width', 300);
    }

    public function test_alt_text_is_bilingual_and_can_be_cleared(): void
    {
        $media = $this->upload();

        $this->actingAs($this->admin)->put(route('admin.media.update', $media), ['alt' => ['fr' => 'L’équipe', 'en' => 'The team']]);
        $this->assertSame('The team', $media->fresh()->getTranslation('alt', 'en', false));

        $this->actingAs($this->admin)->put(route('admin.media.update', $media), ['alt' => ['fr' => 'L’équipe', 'en' => '']]);
        $this->assertSame('', $media->fresh()->getTranslation('alt', 'en', false));
    }

    public function test_image_fields_are_sent_to_the_front_as_urls_with_french_fallback(): void
    {
        $media = $this->upload();
        $media->update(['alt' => ['fr' => 'L’équipe IMMO', 'en' => 'The IMMO team']]);
        $page = Page::create(['slug' => 'demo', 'title' => ['fr' => 'Démo'], 'is_published' => true]);
        $section = $page->sections()->create(['type' => 'media_text']);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), [
            'is_visible' => '1',
            'settings' => ['background' => 'satin', 'image_position' => 'left'],
            'content' => ['fr' => ['image' => (string) $media->id, 'title' => 'Notre équipe'], 'en' => ['image' => '', 'title' => 'Our team']],
        ])->assertSessionHasNoErrors();

        Page::where('slug', 'demo')->firstOrFail()->publish();
        $this->getJson('/api/v1/pages/demo?locale=en')
            ->assertOk()
            ->assertJsonPath('data.sections.0.content.image.url', $media->url())
            ->assertJsonPath('data.sections.0.content.image.width', 1200)
            ->assertJsonPath('data.sections.0.content.image.alt', 'The IMMO team')
            ->assertJsonPath('data.sections.0.settings.image_position', 'left');
    }

    public function test_unknown_media_ids_are_rejected(): void
    {
        $page = Page::create(['slug' => 'demo', 'title' => ['fr' => 'Démo'], 'is_published' => true]);
        $section = $page->sections()->create(['type' => 'media_text']);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['content' => ['fr' => ['image' => '999']]])
            ->assertSessionHasErrors('content.fr.image');
    }

    public function test_an_image_in_use_cannot_be_deleted(): void
    {
        $media = $this->upload();
        $page = Page::create(['slug' => 'demo', 'title' => ['fr' => 'Démo'], 'is_published' => true]);
        $section = $page->sections()->create(['type' => 'cards', 'content' => ['fr' => ['items' => [['title' => 'Carte', 'image' => (string) $media->id]]]]]);

        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);

        $section->delete();
        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $media))->assertSessionHas('success');
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_media_library_and_section_editor_render(): void
    {
        $this->upload();
        $page = Page::create(['slug' => 'demo', 'title' => ['fr' => 'Démo'], 'is_published' => true]);
        $section = $page->sections()->create(['type' => 'media_text']);

        $this->actingAs($this->admin)->get(route('admin.media.index'))->assertOk()->assertSee('equipe.jpg');
        $this->actingAs($this->admin)->get(route('admin.sections.edit', $section))->assertOk()->assertSee('data-media-field', false)->assertSee('id="mediaPicker"', false);
    }
}
