<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    private QuoteRequest $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true,
        ]);

        $cat = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
        $service = Service::create([
            'service_category_id' => $cat->id,
            'title' => 'Web App',
            'slug' => 'web-app',
            'short_description' => 'Description',
            'detailed_content' => 'Content',
            'icon' => 'bi-laptop',
            'is_published' => true,
        ]);

        $prospect = Prospect::create([
            'last_name' => 'Dupont',
            'email' => 'dupont@test.com',
            'phone' => '0102030405',
        ]);

        $this->quote = QuoteRequest::create([
            'prospect_id' => $prospect->id,
            'reference' => 'DEV-202610-0001',
            'description' => 'Projet de test pour admin',
            'status' => QuoteRequest::STATUS_NEW,
            'privacy_consent' => true,
        ]);
        $this->quote->services()->attach($service->id);
    }

    public function test_guests_cannot_access_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_users_cannot_access_admin(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.dashboard'));
        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_access_dashboard_and_quotes(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('DEV-202610-0001');

        $quotesIndexResponse = $this->actingAs($this->admin)->get(route('admin.quotes.index'));
        $quotesIndexResponse->assertStatus(200);
        $quotesIndexResponse->assertSee('DEV-202610-0001');

        $quoteShowResponse = $this->actingAs($this->admin)->get(route('admin.quotes.show', $this->quote->id));
        $quoteShowResponse->assertStatus(200);
        $quoteShowResponse->assertSee('dupont@test.com');
    }

    public function test_admin_can_update_quote_status(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.quotes.status', $this->quote->id), [
            'status' => QuoteRequest::STATUS_IN_PROGRESS,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('quote_requests', [
            'id' => $this->quote->id,
            'status' => QuoteRequest::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_admin_can_update_internal_notes(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.quotes.notes', $this->quote->id), [
            'internal_notes' => 'Appel téléphonique concluant. Envoi de la proposition pour 12k€.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('quote_requests', [
            'id' => $this->quote->id,
            'internal_notes' => 'Appel téléphonique concluant. Envoi de la proposition pour 12k€.',
        ]);
    }
}
