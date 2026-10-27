<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Phase 9 : droit de publication, file de relecture, journal d'activité, SEO des pages. */
class GovernanceAndSeoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true, 'name' => 'Awa Admin']);
        $this->page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
        $this->page->sections()->create(['type' => 'text', 'content' => ['fr' => ['title' => 'V1']]]);
        $this->page->publish();
    }

    private function member(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    // ─── Rôles ───

    public function test_web_editor_composes_pages_but_cannot_publish_or_delete(): void
    {
        $writer = $this->member('web_editor');
        $section = $this->page->sections()->first();

        $this->actingAs($writer)->put(route('admin.sections.update', $section), ['content' => ['fr' => ['title' => 'V2']], 'is_visible' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($writer)->get(route('admin.pages.edit', $this->page))->assertOk()->assertSee('En attente de publication par un éditeur')->assertDontSee('Publier les modifications');
        $this->actingAs($writer)->post(route('admin.pages.publish', $this->page))->assertForbidden();
        $this->actingAs($writer)->delete(route('admin.pages.destroy', $this->page))->assertForbidden();
        $this->actingAs($writer)->put(route('admin.pages.update', $this->page), ['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => '0'])->assertSessionHasNoErrors();
        $this->assertTrue($this->page->fresh()->is_published, 'mise hors ligne réservée au droit de publication');
        $this->actingAs($writer)->get(route('admin.pages.preview', $this->page))->assertRedirect();
        $this->actingAs($writer)->get(route('admin.menus.index'))->assertForbidden();

        // L'éditeur voit la page dans sa file de relecture
        $this->actingAs($this->member('editor'))->get(route('admin.dashboard'))->assertSee('À relire et publier')->assertSee('Offre');
    }

    // ─── Journal d'activité ───

    public function test_team_actions_are_logged_with_field_names_only(): void
    {
        $section = $this->page->sections()->first();
        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['content' => ['fr' => ['title' => 'Texte secret']], 'is_visible' => '1']);
        $this->actingAs($this->admin)->post(route('admin.pages.publish', $this->page));

        $logs = ActivityLog::orderBy('id')->get();
        $this->assertSame(['updated', 'published'], $logs->pluck('action')->all(), 'publication = une seule ligne');
        $this->assertSame('PageSection', $logs[0]->subject_type);
        $this->assertContains('content', $logs[0]->details['fields']);
        $this->assertStringNotContainsString('Texte secret', json_encode($logs->toArray()));
        $this->assertSame('Awa Admin', $logs[1]->user_name);
    }

    public function test_settings_and_role_changes_are_logged(): void
    {
        $member = $this->member('author');
        $this->actingAs($this->admin)->put(route('admin.users.update', $member), ['name' => $member->name, 'email' => $member->email, 'role' => 'editor', 'is_active' => '1']);
        $role = ActivityLog::where('action', 'role_changed')->firstOrFail();
        $this->assertEquals(['from' => 'author', 'to' => 'editor'], $role->details);

        $input = ['settings' => []];
        foreach (\App\Services\SettingService::fields() as $key => $field) {
            $input['settings'][$key] = ($field['translatable'] ?? false) ? ['fr' => \App\Services\SettingService::formValue($key, 'fr'), 'en' => \App\Services\SettingService::formValue($key, 'en')] : \App\Services\SettingService::formValue($key);
        }
        $input['settings']['contact_email'] = 'equipe@example.com';
        $this->actingAs($this->admin)->post(route('admin.settings.update'), $input);
        $this->assertContains('contact_email', ActivityLog::where('action', 'settings_updated')->latest('id')->firstOrFail()->details['fields']);
    }

    public function test_logins_logouts_and_failed_attempts_are_logged(): void
    {
        $user = User::factory()->create(['role' => 'editor', 'is_active' => true, 'email' => 'ed@example.com', 'password' => 'Secret2026Secret']);

        $this->post(route('admin.login.submit'), ['email' => 'ed@example.com', 'password' => 'mauvais']);
        $this->post(route('admin.login.submit'), ['email' => 'ed@example.com', 'password' => 'Secret2026Secret']);
        $this->post(route('admin.logout'));

        $this->assertSame(['auth.failed', 'auth.login', 'auth.logout'], ActivityLog::orderBy('id')->pluck('action')->all());
        $this->assertSame(['email' => 'ed@example.com'], ActivityLog::where('action', 'auth.failed')->first()->details);
        $this->assertTrue(ActivityLog::where('action', 'auth.login')->first()->user->is($user));
    }

    public function test_public_forms_and_console_are_not_logged(): void
    {
        $this->page->publish(); // hors requête de l'équipe (seeder, commande)
        Page::create(['slug' => 'auto', 'title' => ['fr' => 'Auto']]);

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_only_admins_read_the_journal_with_filters(): void
    {
        $this->actingAs($this->admin)->post(route('admin.pages.publish', $this->page));
        $this->actingAs($this->member('editor'))->get(route('admin.activity.index'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.activity.index'))->assertOk()->assertSee('Awa Admin')->assertSee('a publié')->assertSee('« Offre »', false);
        $this->actingAs($this->admin)->get(route('admin.activity.index', ['action' => 'deleted']))->assertOk()->assertSee('Aucune action enregistrée pour ces filtres');
        $this->actingAs($this->admin)->get(route('admin.activity.index', ['action' => 'inventée']))->assertSessionHasErrors('action');
    }

    public function test_old_entries_are_pruned_after_twelve_months(): void
    {
        ActivityLog::create(['action' => 'created', 'user_name' => 'X'])->forceFill(['created_at' => now()->subMonths(13)])->save();
        ActivityLog::create(['action' => 'created', 'user_name' => 'Y']);

        $this->artisan('model:prune', ['--model' => [ActivityLog::class]])->assertSuccessful();
        $this->assertSame(['Y'], ActivityLog::pluck('user_name')->all());
    }

    // ─── SEO ───

    public function test_share_image_and_noindex_are_published_with_the_page(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('partage.jpg', 1200, 630)]]);
        $media = Media::latest('id')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.pages.update', $this->page), ['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'og_image_id' => $media->id, 'noindex' => '1', 'is_published' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($this->getJson('/api/v1/pages/offre')->json('data.meta.image'), 'brouillon : rien ne change en ligne');
        $this->assertFalse($this->getJson('/api/v1/pages')->json('data.0.noindex'));

        $this->actingAs($this->admin)->post(route('admin.pages.publish', $this->page));
        $meta = $this->getJson('/api/v1/pages/offre')->json('data.meta');
        $this->assertSame([$media->id, true], [$meta['image']['id'], $meta['noindex']]);
        $this->assertTrue($this->getJson('/api/v1/pages')->json('data.0.noindex'));

        // Image de partage protégée dans la médiathèque
        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);
    }

    public function test_revisions_older_than_the_seo_fields_show_no_phantom_changes(): void
    {
        $revision = $this->page->publishedRevision;
        $snapshot = $revision->snapshot;
        unset($snapshot['og_image_id'], $snapshot['noindex']);
        $revision->update(['snapshot' => $snapshot]);

        $this->assertFalse($this->page->fresh()->hasUnpublishedChanges());
    }
}
