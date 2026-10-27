<?php

namespace Tests\Feature;

use App\Models\PortalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.front.api_key' => 'front-key']);
        $this->client = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true, 'email' => 'client@example.cm', 'company' => 'Ngono SARL', 'password' => 'motdepasse-client']);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    }

    private function login(string $password = 'motdepasse-client', string $email = 'client@example.cm'): TestResponse
    {
        return $this->postJson('/api/v1/portal/login', compact('email', 'password'), ['X-Front-Key' => 'front-key']);
    }

    private function as(string $token): array
    {
        return ['X-Front-Key' => 'front-key', 'Authorization' => "Bearer {$token}"];
    }

    public function test_client_logs_in_and_reads_its_own_items_only(): void
    {
        $this->client->portalItems()->create(['type' => 'document', 'title' => 'Facture — acompte', 'data' => ['kind' => 'invoice', 'amount' => 150000, 'status' => 'pending']]);
        $other = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true]);
        $other->portalItems()->create(['type' => 'task', 'title' => 'Secret', 'data' => ['owner' => '', 'col' => 0]]);

        $token = $this->login()->assertOk()->assertJsonPath('user.company', 'Ngono SARL')->json('token');

        $this->getJson('/api/v1/portal/me', $this->as($token))
            ->assertOk()
            ->assertJsonPath('preview', false)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.title', 'Facture — acompte')
            ->assertJsonPath('items.0.amount', 150000)
            ->assertJsonPath('items.0.clientId', (string) $this->client->id);
    }

    public function test_login_rejects_wrong_password_admins_and_inactive_clients(): void
    {
        $this->login('mauvais-mot-de-passe')->assertUnauthorized();
        $this->login('password', $this->admin->email)->assertUnauthorized();
        $this->postJson('/api/v1/portal/login', ['email' => 'client@example.cm', 'password' => 'motdepasse-client'])->assertUnauthorized(); // sans clé du front

        $this->client->update(['is_active' => false]);
        $this->login()->assertUnauthorized();
    }

    public function test_deactivating_a_client_cuts_an_open_session(): void
    {
        $token = $this->login()->json('token');
        $this->client->update(['is_active' => false]);

        $this->getJson('/api/v1/portal/me', $this->as($token))->assertForbidden();
    }

    public function test_client_signs_its_document_once(): void
    {
        $doc = $this->client->portalItems()->create(['type' => 'document', 'title' => 'Devis', 'data' => ['kind' => 'quote', 'amount' => null, 'status' => 'toSign']]);
        $token = $this->login()->json('token');

        $this->postJson("/api/v1/portal/documents/{$doc->id}/sign", [], $this->as($token))->assertOk();
        $this->assertSame('signed', $doc->fresh()->data['status']);
        $this->assertSame('client@example.cm', $doc->fresh()->data['signedBy']);

        $this->postJson("/api/v1/portal/documents/{$doc->id}/sign", [], $this->as($token))->assertStatus(400);
    }

    public function test_client_cannot_sign_another_clients_document(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true]);
        $doc = $other->portalItems()->create(['type' => 'document', 'title' => 'Devis', 'data' => ['kind' => 'quote', 'status' => 'toSign']]);

        $this->postJson("/api/v1/portal/documents/{$doc->id}/sign", [], $this->as($this->login()->json('token')))->assertStatus(400);
        $this->assertSame('toSign', $doc->fresh()->data['status']);
    }

    public function test_client_opens_a_ticket(): void
    {
        $token = $this->login()->json('token');

        $this->postJson('/api/v1/portal/tickets', ['title' => 'Site lent', 'message' => 'Depuis ce matin.', 'priority' => 1], $this->as($token))->assertCreated();

        $ticket = PortalItem::sole();
        $this->assertSame(['ticket', $this->client->id], [$ticket->type, $ticket->user_id]);
        $this->assertEquals(['message' => 'Depuis ce matin.', 'priority' => 1, 'status' => 'open', 'reply' => ''], $ticket->data);
    }

    public function test_password_change_requires_the_current_one_and_closes_other_sessions(): void
    {
        $other = $this->login()->json('token');
        $token = $this->login()->json('token');

        $this->putJson('/api/v1/portal/password', ['current' => 'faux', 'next' => 'nouveau-mot-de-passe'], $this->as($token))->assertForbidden();
        $this->putJson('/api/v1/portal/password', ['current' => 'motdepasse-client', 'next' => 'nouveau-mot-de-passe'], $this->as($token))->assertOk();

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $this->client->fresh()->password));
        $this->getJson('/api/v1/portal/me', $this->as($token))->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/portal/me', $this->as($other))->assertUnauthorized();
    }

    public function test_logout_revokes_the_token(): void
    {
        $token = $this->login()->json('token');

        $this->postJson('/api/v1/portal/logout', [], $this->as($token))->assertOk();
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/portal/me', $this->as($token))->assertUnauthorized();
    }

    public function test_admin_preview_gives_a_single_use_read_only_token(): void
    {
        $location = $this->actingAs($this->admin)->post(route('admin.clients.preview', $this->client))
            ->assertRedirectContains(config('services.front.url').'/api/portal/preview?code=')
            ->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $code = $query['code'];
        $this->app['auth']->forgetGuards(); // le serveur Next n'a pas la session de l'admin : seul le jeton compte

        $token = $this->postJson('/api/v1/portal/preview', ['code' => $code], ['X-Front-Key' => 'front-key'])->assertOk()->json('token');
        $this->postJson('/api/v1/portal/preview', ['code' => $code], ['X-Front-Key' => 'front-key'])->assertUnauthorized();

        $this->getJson('/api/v1/portal/me', $this->as($token))->assertOk()->assertJsonPath('preview', true);
        $this->postJson('/api/v1/portal/tickets', ['title' => 'x', 'priority' => 0], $this->as($token))->assertForbidden();
    }

    public function test_admin_manages_clients_and_their_items(): void
    {
        $this->actingAs($this->admin)->post(route('admin.clients.store'), [
            'name' => 'Paul Mbarga', 'company' => 'Mbarga & Fils', 'email' => 'Paul@Mbarga.cm', 'password' => 'temporaire-123', 'is_active' => '1',
        ])->assertRedirect();
        $paul = User::where('email', 'paul@mbarga.cm')->sole();
        $this->assertSame(User::ROLE_CLIENT, $paul->role);
        $this->assertFalse($paul->isAdmin());

        $this->actingAs($this->admin)->post(route('admin.clients.items.store', $paul), [
            'type' => 'document', 'title' => 'Facture — jalon 2', 'data' => ['kind' => 'invoice', 'amount' => '250000', 'status' => 'pending', 'unknown' => 'x'],
        ])->assertSessionHasNoErrors();
        $item = $paul->portalItems()->sole();
        $this->assertEquals(['kind' => 'invoice', 'amount' => 250000, 'status' => 'pending'], $item->data);

        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $item), ['title' => 'Facture', 'data' => ['status' => 'nope']])->assertSessionHasErrors('data.status');

        $this->actingAs($this->admin)->get(route('admin.clients.show', $paul))->assertOk()->assertSee('Facture — jalon 2');
        $this->actingAs($this->admin)->get(route('admin.clients.index'))->assertOk()->assertSee('Mbarga &amp; Fils', false);
    }

    public function test_admin_routes_never_resolve_an_administrator_as_a_client(): void
    {
        $this->actingAs($this->admin)->get(route('admin.clients.show', $this->admin))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.clients.destroy', $this->admin))->assertNotFound();
        $this->assertModelExists($this->admin);
    }

    public function test_changing_the_password_from_the_admin_closes_portal_sessions(): void
    {
        $this->login()->assertOk();
        $this->assertSame(1, $this->client->tokens()->count());

        $this->actingAs($this->admin)->put(route('admin.clients.update', $this->client), [
            'name' => $this->client->name, 'email' => $this->client->email, 'password' => 'nouveau-temporaire', 'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(0, $this->client->tokens()->count());
    }

    public function test_clients_cannot_enter_the_admin(): void
    {
        $this->actingAs($this->client)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }
}
