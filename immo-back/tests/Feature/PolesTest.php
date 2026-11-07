<?php

namespace Tests\Feature;

use App\Models\ClientProject;
use App\Models\Media;
use App\Models\Page;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\Poles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Chaque service publié est un pôle : chiffrage, rendez-vous, avis, tâches du portail. Icône d'un service. */
class PolesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private array $headers = ['X-Front-Key' => 'front-key', 'X-Client-Ip' => '203.0.113.7'];

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        config(['services.front.api_key' => 'front-key']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function formation(): Service
    {
        return Service::create(['slug' => 'formation', 'title' => ['fr' => 'Formation', 'en' => 'Training'], 'short_description' => ['fr' => 'Former vos équipes.'], 'icon' => 'graduation-cap', 'sort_order' => 9, 'is_published' => true]);
    }

    public function test_published_services_are_the_poles_of_quotes_and_bookings(): void
    {
        $service = $this->formation();
        $this->assertSame('Formation', Poles::options()['formation']);
        $this->assertSame('Training', Poles::options('en')['formation']);

        $quote = ['name' => 'Awa Ngono', 'email' => 'awa@example.cm', 'phone' => '+237 600 000 000', 'company' => 'Ngono SARL', 'details' => 'Former dix personnes à nos nouveaux outils.', 'locale' => 'fr'];
        $this->postJson('/api/v1/quotes', $quote + ['domain' => 'formation'], $this->headers)->assertCreated();
        $created = QuoteRequest::latest('id')->firstOrFail();
        $this->assertSame('Formation', $created->domain_label);
        $this->assertSame([$service->id], $created->services()->pluck('services.id')->all());

        $this->postJson('/api/v1/quotes', $quote + ['domain' => 'marketing'], $this->headers)->assertJsonValidationErrors('domain');

        Carbon::setTestNow('2026-10-21 10:00:00');
        $booking = ['deadline' => 'month', 'slot' => '2026-10-22T09:00:00+01:00', 'name' => 'Awa', 'email' => 'awa@example.cm', 'company' => 'Ngono SARL'];
        $this->postJson('/api/v1/bookings', $booking + ['need' => 'marketing'], $this->headers)->assertJsonValidationErrors('need');
        $this->postJson('/api/v1/bookings', $booking + ['need' => 'formation'], $this->headers)->assertSuccessful();
    }

    public function test_without_published_services_the_historic_poles_remain(): void
    {
        Service::query()->update(['is_published' => false]);
        Poles::flush();

        $this->assertSame(Poles::LEGACY, Poles::options());
        $this->assertSame('Web & Mobile', Poles::label('web'), 'ancienne clé toujours lisible');
        $this->assertSame('Texte libre', Poles::label('Texte libre'));
    }

    public function test_a_service_icon_is_a_lucide_name_or_an_uploaded_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('icone.png', 256, 256)]]);
        $media = Media::latest('id')->firstOrFail();
        $service = $this->formation();

        $input = ['title' => ['fr' => 'Formation', 'en' => ''], 'short_description' => ['fr' => 'Former vos équipes.', 'en' => ''], 'sort_order' => 9, 'is_published' => '1'];
        $this->actingAs($this->admin)->get(route('admin.services.edit', $service))->assertOk()->assertSee('lucide.dev/icons')->assertSee('data-icon-preview', false);
        $this->actingAs($this->admin)->put(route('admin.services.update', $service), $input + ['icon' => 'rocket', 'icon_media_id' => $media->id])->assertSessionHasNoErrors();

        $api = collect($this->getJson('/api/v1/services')->json('data'))->firstWhere('slug', 'formation');
        $this->assertSame('rocket', $api['icon']);
        $this->assertSame($media->id, $api['iconImage']['id']);

        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);

        $this->actingAs($this->admin)->put(route('admin.services.update', $service), $input + ['icon' => 'Rocket Ship'])->assertSessionHasErrors('icon');
    }

    public function test_a_testimonial_pole_is_a_service(): void
    {
        $input = ['client_name' => 'Awa', 'content' => ['fr' => 'Très bien', 'en' => ''], 'rating' => 5, 'is_published' => '1'];
        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), $input + ['pole' => 'nope'])->assertSessionHasErrors('pole');
        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), $input + ['pole' => 'ia'])->assertSessionHasNoErrors();

        $this->assertSame('AI & Process Automation', $this->getJson('/api/v1/testimonials?locale=en')->json('data.0.pole'));

        // Ancienne valeur libre : conservée tant qu'on ne la change pas.
        $legacy = Testimonial::create(['client_name' => 'Paul', 'pole' => 'IA & Data', 'content' => ['fr' => 'Bien'], 'rating' => 5, 'is_published' => true]);
        $this->actingAs($this->admin)->put(route('admin.testimonials.update', $legacy), $input + ['client_name' => 'Paul', 'pole' => 'IA & Data'])->assertSessionHasNoErrors();
        $this->assertSame('IA & Data', $legacy->fresh()->pole);
    }

    public function test_a_task_pole_is_a_service_shown_in_the_client_language(): void
    {
        $project = ClientProject::factory()->create();
        $client = $project->client;
        $client->update(['password' => 'motdepasse-client']);

        $this->actingAs($this->admin)->post(route('admin.client-projects.items.store', $project), ['type' => 'task', 'title' => 'Workflow', 'data' => ['pole' => 'nope', 'col' => 0]])->assertSessionHasErrors('data.pole');
        $this->actingAs($this->admin)->post(route('admin.client-projects.items.store', $project), ['type' => 'task', 'title' => 'Workflow', 'data' => ['pole' => 'ia', 'owner' => 'Awa', 'col' => 1]])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->get(route('admin.clients.show', $client))->assertOk()->assertSee('IA &amp; Automatisation de processus', false);
        $this->app['auth']->forgetGuards();

        $token = $this->postJson('/api/v1/portal/login', ['email' => $client->email, 'password' => 'motdepasse-client'], $this->headers)->json('token');
        $this->getJson('/api/v1/portal/me?locale=en', $this->headers + ['Authorization' => "Bearer {$token}"])
            ->assertJsonPath('items.0.pole', 'ia')
            ->assertJsonPath('items.0.poleLabel', 'AI & Process Automation')
            ->assertJsonPath('items.0.owner', 'Awa');
    }

    public function test_the_quote_form_preselects_one_of_the_services(): void
    {
        $this->formation();
        $page = Page::create(['slug' => 'offre', 'title' => ['fr' => 'Offre'], 'is_published' => true]);
        $section = $page->sections()->create(['type' => 'quote_form', 'content' => ['fr' => []]]);

        $this->actingAs($this->admin)->get(route('admin.sections.edit', $section))->assertOk()->assertSee('<option value="formation"', false);
        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['is_visible' => '1', 'content' => ['fr' => []], 'settings' => ['default_domain' => 'formation']])->assertSessionHasNoErrors();
        $this->assertSame('formation', $section->fresh()->resolvedSettings()['default_domain']);

        $this->actingAs($this->admin)->put(route('admin.sections.update', $section), ['is_visible' => '1', 'content' => ['fr' => []], 'settings' => ['default_domain' => 'nope']])->assertSessionHasErrors('settings.default_domain');
    }
}
