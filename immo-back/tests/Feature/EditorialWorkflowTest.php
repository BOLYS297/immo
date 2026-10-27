<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\User;
use App\Services\Preview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = ['X-Front-Key' => 'front-key'];

    private User $editor;

    private Page $page;

    private PageSection $hero;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config(['services.front.api_key' => 'front-key', 'services.front.url' => 'http://front.test']);

        $this->editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
        $this->page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
        $this->hero = $this->page->sections()->create(['type' => 'text', 'content' => ['fr' => ['title' => 'Version 1']]]);
        $this->page->publish($this->editor);
    }

    private function publicTitle(): ?string
    {
        return $this->getJson('/api/v1/pages/offre')->assertOk()->json('data.sections.0.content.title');
    }

    private function editTitle(string $title): void
    {
        $this->actingAs($this->editor)->put(route('admin.sections.update', $this->hero), ['content' => ['fr' => ['title' => $title]], 'is_visible' => '1'])->assertSessionHasNoErrors();
    }

    public function test_changes_stay_in_draft_until_published(): void
    {
        $this->editTitle('Version 2');

        $this->assertSame('Version 1', $this->publicTitle());
        $this->assertTrue($this->page->fresh()->hasUnpublishedChanges());
        $this->actingAs($this->editor)->get(route('admin.pages.edit', $this->page))->assertOk()->assertSee('Modifications non publiées');

        $this->actingAs($this->editor)->post(route('admin.pages.publish', $this->page))->assertRedirect();

        $this->assertSame('Version 2', $this->publicTitle());
        $page = $this->page->fresh();
        $this->assertFalse($page->hasUnpublishedChanges());
        $this->assertSame(2, $page->revisions()->count());
        $this->assertTrue($page->publishedRevision->author->is($this->editor));
    }

    public function test_seo_and_title_are_part_of_the_draft(): void
    {
        $this->actingAs($this->editor)->put(route('admin.pages.update', $this->page), ['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'meta_title' => ['fr' => 'Nouveau SEO'], 'is_published' => '1']);

        $this->assertNull($this->getJson('/api/v1/pages/offre')->json('data.meta.title'));
        $this->page->fresh()->publish();
        $this->assertSame('Nouveau SEO', $this->getJson('/api/v1/pages/offre')->json('data.meta.title'));
    }

    public function test_never_published_pages_are_not_public(): void
    {
        $draft = Page::create(['slug' => 'nouvelle', 'title' => ['fr' => 'Nouvelle'], 'is_published' => true]);

        $this->getJson('/api/v1/pages/nouvelle')->assertNotFound();
        $this->assertNotContains('nouvelle', array_column($this->getJson('/api/v1/pages')->json('data'), 'slug'));

        $draft->publish();
        $this->getJson('/api/v1/pages/nouvelle')->assertOk();
    }

    public function test_discarding_restores_the_live_version(): void
    {
        $this->editTitle('Brouillon raté');
        $this->page->sections()->create(['type' => 'faq']);

        $this->actingAs($this->editor)->post(route('admin.pages.discard', $this->page))->assertRedirect();

        $page = $this->page->fresh();
        $this->assertFalse($page->hasUnpublishedChanges());
        $this->assertSame(1, $page->sections()->count());
        $this->assertSame('Version 1', $page->sections()->first()->contentFor('fr')['title']);
    }

    public function test_an_old_revision_can_be_restored_then_published(): void
    {
        $first = $this->page->publishedRevision;
        $this->editTitle('Version 2');
        $this->page->fresh()->publish($this->editor);

        $this->actingAs($this->editor)->post(route('admin.pages.restore', [$this->page, $first]))->assertRedirect();
        $this->assertSame('Version 2', $this->publicTitle(), 'restaurer ne change pas le site');

        $this->actingAs($this->editor)->post(route('admin.pages.publish', $this->page));
        $this->assertSame('Version 1', $this->publicTitle());
    }

    public function test_revisions_of_another_page_cannot_be_restored(): void
    {
        $other = Page::create(['slug' => 'autre', 'title' => ['fr' => 'Autre'], 'is_published' => true]);
        $revision = $other->publish();

        $this->actingAs($this->editor)->post(route('admin.pages.restore', [$this->page, $revision]))->assertNotFound();
    }

    public function test_old_revisions_are_pruned(): void
    {
        for ($i = 0; $i < Page::KEEP_REVISIONS + 3; $i++) {
            $this->page->publish();
        }

        $this->assertSame(Page::KEEP_REVISIONS, $this->page->revisions()->count());
        $this->assertNotNull($this->page->fresh()->publishedRevision);
    }

    public function test_authors_cannot_publish_pages(): void
    {
        $author = User::factory()->create(['role' => 'author', 'is_active' => true]);

        $this->actingAs($author)->post(route('admin.pages.publish', $this->page))->assertForbidden();
    }

    public function test_preview_shows_the_draft_and_requires_the_front_key(): void
    {
        $this->editTitle('Brouillon');

        $response = $this->actingAs($this->editor)->get(route('admin.pages.preview', $this->page));
        $this->assertMatchesRegularExpression('#^http://front\.test/apercu\?jeton=[A-Za-z0-9]{48}$#', $response->headers->get('Location'));
        $token = explode('jeton=', $response->headers->get('Location'))[1];

        $this->getJson("/api/v1/preview/{$token}")->assertUnauthorized();
        $this->getJson("/api/v1/preview/{$token}", self::KEY)->assertOk()
            ->assertJsonPath('data.kind', 'page')
            ->assertJsonPath('data.version', 'draft')
            ->assertJsonPath('data.page.sections.0.content.title', 'Brouillon');
        $this->getJson('/api/v1/preview/'.str_repeat('a', 48), self::KEY)->assertNotFound();

        // Ancienne version
        $location = $this->actingAs($this->editor)->get(route('admin.pages.preview', [$this->page, 'revision' => $this->page->published_revision_id]))->headers->get('Location');
        $this->getJson('/api/v1/preview/'.explode('jeton=', $location)[1], self::KEY)
            ->assertJsonPath('data.version', 'revision')
            ->assertJsonPath('data.page.sections.0.content.title', 'Version 1');
    }

    public function test_live_preview_shows_unsaved_content_without_saving_it(): void
    {
        $page = $this->actingAs($this->editor)->get(route('admin.sections.edit', $this->hero))->assertOk()->assertSee('Aperçu en direct');
        preg_match('/jeton=([A-Za-z0-9]{48})/', $page->getContent(), $m);
        $token = $m[1];

        $this->actingAs($this->editor)->postJson(route('admin.sections.live', $this->hero), ['preview_token' => $token, 'content' => ['fr' => ['title' => 'En cours de saisie']], 'is_visible' => '1'])->assertOk();

        $this->getJson("/api/v1/preview/{$token}", self::KEY)->assertJsonPath('data.page.sections.0.content.title', 'En cours de saisie');
        $this->assertSame('Version 1', $this->hero->fresh()->contentFor('fr')['title'], 'rien n\'est enregistré');

        // Validation identique à l'enregistrement ; jeton d'une autre page refusé
        $this->actingAs($this->editor)->postJson(route('admin.sections.live', $this->hero), ['preview_token' => $token, 'content' => ['fr' => ['title' => str_repeat('x', 300)]]])->assertUnprocessable();
        $other = Preview::create(['kind' => 'page', 'page_id' => $this->page->id + 99, 'revision_id' => null, 'overrides' => []]);
        $this->actingAs($this->editor)->postJson(route('admin.sections.live', $this->hero), ['preview_token' => $other, 'content' => ['fr' => []]])->assertStatus(410);
    }

    public function test_authors_preview_their_own_draft_posts_only(): void
    {
        $author = User::factory()->create(['role' => 'author', 'is_active' => true]);
        $make = fn (User $u) => Post::create(['user_id' => $u->id, 'slug' => 'a-'.$u->id, 'title' => ['fr' => 'Brouillon de '.$u->id], 'summary' => ['fr' => 'R'], 'content' => ['fr' => 'Texte'], 'published_at' => now(), 'reading_minutes' => 3, 'is_published' => false]);
        $own = $make($author);
        $foreign = $make($this->editor);

        $location = $this->actingAs($author)->get(route('admin.posts.preview', $own))->assertRedirect()->headers->get('Location');
        $this->getJson('/api/v1/preview/'.explode('jeton=', $location)[1], self::KEY)
            ->assertJsonPath('data.kind', 'post')
            ->assertJsonPath('data.published', false)
            ->assertJsonPath('data.post.title', 'Brouillon de '.$author->id);

        $this->actingAs($author)->get(route('admin.posts.preview', $foreign))->assertForbidden();
    }

    public function test_media_used_by_the_live_version_cannot_be_deleted(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('a.jpg', 800, 600)]]);
        $media = Media::latest('id')->firstOrFail();

        $section = $this->page->sections()->create(['type' => 'media_text', 'content' => ['fr' => ['title' => 'Image', 'image' => (string) $media->id]]]);
        $this->page->publish();
        $section->delete(); // retirée du brouillon seulement

        $this->actingAs($admin)->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);
    }
}
