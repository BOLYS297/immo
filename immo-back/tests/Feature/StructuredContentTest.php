<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Media;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StructuredContentTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config(['services.front.url' => 'http://front.test', 'services.front.revalidate_secret' => 'secret']);
        $this->editor = User::factory()->create(['role' => 'editor', 'is_active' => true]);
    }

    public function test_the_site_services_are_created_by_the_migration(): void
    {
        $services = $this->getJson('/api/v1/services?locale=en')->assertOk()->json('data');

        $this->assertSame(['ia', 'web', 'infra'], array_column($services, 'slug'));
        $this->assertSame('AI & Process Automation', $services[0]['title']);
        $this->assertSame('ia', $services[0]['domain']);
        $this->assertNotEmpty($services[0]['deliverables']);
    }

    public function test_editor_manages_a_bilingual_service(): void
    {
        $this->actingAs($this->editor)->get(route('admin.services.index'))->assertOk()->assertSee('IA &amp; Automatisation de processus', false);

        $this->actingAs($this->editor)->post(route('admin.services.store'), [
            'title' => ['fr' => 'Formation', 'en' => ''],
            'short_description' => ['fr' => 'Former vos équipes.', 'en' => 'Train your teams.'],
            'deliverables' => ['fr' => "Atelier\n\n Supports ", 'en' => ''],
            'cta_label' => ['fr' => 'Nous écrire', 'en' => ''],
            'icon' => 'pen-tool', 'quote_domain' => '', 'sort_order' => 9, 'is_published' => '1',
        ])->assertRedirect(route('admin.services.index'))->assertSessionHasNoErrors();

        $service = Service::where('slug', 'formation')->firstOrFail();
        $this->assertSame(['Atelier', 'Supports'], $service->getTranslation('deliverables', 'fr'));
        $en = collect($this->getJson('/api/v1/services?locale=en')->json('data'))->firstWhere('slug', 'formation');
        $this->assertSame('Formation', $en['title'], 'EN vide = FR');
        $this->assertSame('Train your teams.', $en['description']);
        $this->assertNull($en['domain']);
        Http::assertSent(fn (Request $r) => $r['tags'] === ['services']);

        $this->actingAs($this->editor)->post(route('admin.services.store'), ['title' => ['fr' => 'X'], 'short_description' => ['fr' => 'Y'], 'icon' => 'inconnue', 'sort_order' => 1])->assertSessionHasErrors('icon');
    }

    public function test_unpublished_services_projects_and_faqs_stay_private(): void
    {
        Service::where('slug', 'ia')->update(['is_published' => false]);
        Faq::create(['question' => ['fr' => 'Q ?'], 'answer' => ['fr' => 'R.'], 'is_published' => false]);

        $this->assertNotContains('ia', array_column($this->getJson('/api/v1/services')->json('data'), 'slug'));
        $this->getJson('/api/v1/faqs')->assertJsonCount(0, 'data');
    }

    public function test_a_project_needs_the_client_consent_to_go_online(): void
    {
        $input = [
            'title' => ['fr' => 'Portail logistique', 'en' => ''], 'short_description' => ['fr' => 'Suivi des livraisons.', 'en' => ''],
            'client_name' => 'Client réel', 'technologies' => 'Next.js, Laravel ,', 'sort_order' => 0, 'is_published' => '1',
        ];

        $this->actingAs($this->editor)->post(route('admin.projects.store'), $input)->assertSessionHasErrors('client_consent_at');
        $this->actingAs($this->editor)->post(route('admin.projects.store'), $input + ['client_consent_at' => now()->addDay()->toDateString()])->assertSessionHasErrors('client_consent_at');

        $this->actingAs($this->editor)->post(route('admin.projects.store'), $input + ['client_consent_at' => now()->toDateString()])->assertSessionHasNoErrors();
        $project = $this->getJson('/api/v1/projects?locale=en')->assertOk()->json('data.0');
        $this->assertSame(['Portail logistique', 'Client réel', ['Next.js', 'Laravel']], [$project['title'], $project['client'], $project['technologies']]);

        // En ligne sans accord (modification directe en base) : jamais servi
        Project::query()->update(['client_consent_at' => null]);
        $this->getJson('/api/v1/projects')->assertJsonCount(0, 'data');
        $this->actingAs($this->editor)->get(route('admin.projects.index'))->assertSee('Accord manquant');
    }

    public function test_project_image_comes_from_the_media_library(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('cas.jpg', 1200, 750)]]);
        $media = Media::latest('id')->firstOrFail();
        Project::create(['title' => ['fr' => 'Cas'], 'slug' => 'cas', 'short_description' => ['fr' => 'R'], 'image_id' => $media->id, 'is_published' => true, 'client_consent_at' => now()]);

        $image = $this->getJson('/api/v1/projects')->json('data.0.image');
        $this->assertSame([$media->id, 1200, 750], [$image['id'], $image['width'], $image['height']]);
        $this->actingAs($admin)->get(route('admin.projects.edit', Project::first()))->assertOk()->assertSee('data-media-field', false);
    }

    public function test_faqs_are_bilingual_and_carry_their_french_category(): void
    {
        $this->actingAs($this->editor)->post(route('admin.faqs.store'), [
            'question' => ['fr' => 'Quels délais ?', 'en' => 'What timelines?'],
            'answer' => ['fr' => 'Selon le **cadrage**.', 'en' => ''],
            'category' => ['fr' => 'Méthode', 'en' => 'Method'],
            'sort_order' => 0, 'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $faq = $this->getJson('/api/v1/faqs?locale=en')->json('data.0');
        $this->assertSame(['What timelines?', 'Selon le **cadrage**.', 'Méthode'], [$faq['question'], $faq['answer'], $faq['category']]);
        Http::assertSent(fn (Request $r) => $r['tags'] === ['faqs']);
    }

    public function test_authors_and_sales_cannot_manage_structured_content(): void
    {
        foreach (['author', 'sales'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($user)->get(route('admin.services.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.projects.create'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.faqs.store'), [])->assertForbidden();
        }
    }
}
