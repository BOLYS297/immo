<?php

namespace Tests\Feature;

use App\Models\ClientProject;
use App\Models\PortalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $admin;

    private ClientProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.front.api_key' => 'front-key']);
        $this->client = User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true, 'email' => 'client@example.cm', 'company' => 'Ngono SARL', 'password' => 'motdepasse-client']);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $this->project = ClientProject::factory()->for($this->client, 'client')->create(['name' => 'Site vitrine']);
    }

    /** @param  array<string, mixed>  $data */
    private function item(ClientProject $project, string $type, string $title, array $data): PortalItem
    {
        return $project->items()->create(['user_id' => $project->user_id, 'type' => $type, 'title' => $title, 'data' => $data]);
    }

    private function login(string $password = 'motdepasse-client', string $email = 'client@example.cm'): TestResponse
    {
        return $this->postJson('/api/v1/portal/login', compact('email', 'password'), ['X-Front-Key' => 'front-key']);
    }

    private function as(string $token): array
    {
        return ['X-Front-Key' => 'front-key', 'Authorization' => "Bearer {$token}"];
    }

    public function test_client_logs_in_and_reads_its_own_projects_and_items_only(): void
    {
        $this->item($this->project, 'document', 'Facture — acompte', ['kind' => 'invoice', 'amount' => 150000, 'status' => 'pending']);
        $this->travel(1)->minute();
        $app = ClientProject::factory()->for($this->client, 'client')->create(['name' => 'Application mobile', 'status' => 'paused']);
        $this->item($app, 'task', 'Maquettes', ['owner' => 'Design', 'col' => 1]);
        $other = ClientProject::factory()->create();
        $this->item($other, 'task', 'Secret', ['owner' => '', 'col' => 0]);

        $token = $this->login()->assertOk()->assertJsonPath('user.company', 'Ngono SARL')->json('token');

        $this->getJson('/api/v1/portal/me', $this->as($token))
            ->assertOk()
            ->assertJsonPath('preview', false)
            ->assertJsonCount(2, 'projects')
            ->assertJsonPath('projects.0.name', 'Application mobile') // le plus récent d'abord
            ->assertJsonPath('projects.0.status', 'paused')
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.1.title', 'Facture — acompte')
            ->assertJsonPath('items.1.amount', 150000)
            ->assertJsonPath('items.1.projectId', (string) $this->project->id)
            ->assertJsonPath('items.1.file', null)
            ->assertJsonPath('items.0.projectId', (string) $app->id)
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
        $doc = $this->item($this->project, 'document', 'Devis', ['kind' => 'quote', 'amount' => null, 'status' => 'toSign']);
        $token = $this->login()->json('token');

        $this->postJson("/api/v1/portal/documents/{$doc->id}/sign", [], $this->as($token))->assertOk();
        $this->assertSame('signed', $doc->fresh()->data['status']);
        $this->assertSame('client@example.cm', $doc->fresh()->data['signedBy']);

        $this->postJson("/api/v1/portal/documents/{$doc->id}/sign", [], $this->as($token))->assertStatus(400);
    }

    public function test_client_cannot_sign_another_clients_document(): void
    {
        $doc = $this->item(ClientProject::factory()->create(), 'document', 'Devis', ['kind' => 'quote', 'status' => 'toSign']);

        $this->postJson("/api/v1/portal/documents/{$doc->id}/sign", [], $this->as($this->login()->json('token')))->assertStatus(400);
        $this->assertSame('toSign', $doc->fresh()->data['status']);
    }

    public function test_client_opens_a_ticket_on_one_of_its_projects(): void
    {
        $token = $this->login()->json('token');
        $ticket = ['title' => 'Site lent', 'message' => 'Depuis ce matin.', 'priority' => 1];

        $this->postJson('/api/v1/portal/tickets', $ticket, $this->as($token))->assertJsonValidationErrors('project');
        $this->postJson('/api/v1/portal/tickets', $ticket + ['project' => ClientProject::factory()->create()->id], $this->as($token))->assertJsonValidationErrors('project');
        $this->postJson('/api/v1/portal/tickets', $ticket + ['project' => $this->project->id], $this->as($token))->assertCreated();

        $created = PortalItem::sole();
        $this->assertSame(['ticket', $this->client->id, $this->project->id], [$created->type, $created->user_id, $created->client_project_id]);
        $this->assertEquals(['message' => 'Depuis ce matin.', 'priority' => 1, 'status' => 'open', 'reply' => ''], $created->data);
    }

    public function test_document_file_is_private_to_its_client(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)->post(route('admin.client-projects.items.store', $this->project), [
            'type' => 'document', 'title' => 'Contrat', 'data' => ['kind' => 'contract', 'status' => 'toSign', 'file' => UploadedFile::fake()->create('contrat-signé.pdf', 120, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $doc = PortalItem::sole();
        $path = $doc->file()['path'];
        Storage::disk('local')->assertExists($path);
        $this->assertStringStartsWith("portal/{$this->client->id}/", $path);
        $this->app['auth']->forgetGuards();

        $token = $this->login()->json('token');
        $this->getJson('/api/v1/portal/me', $this->as($token))
            ->assertJsonPath('items.0.file', ['name' => 'contrat-signé.pdf'])
            ->assertDontSee(basename($path)); // le chemin sur le disque reste côté serveur
        $this->get("/api/v1/portal/documents/{$doc->id}/file", $this->as($token))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        // Autre client : introuvable. Sans jeton : refusé.
        User::factory()->create(['role' => User::ROLE_CLIENT, 'is_active' => true, 'email' => 'autre@example.cm', 'password' => 'motdepasse-autre']);
        $this->app['auth']->forgetGuards();
        $otherToken = $this->login('motdepasse-autre', 'autre@example.cm')->json('token');
        $this->getJson("/api/v1/portal/documents/{$doc->id}/file", $this->as($otherToken))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/v1/portal/documents/{$doc->id}/file", ['X-Front-Key' => 'front-key'])->assertUnauthorized();

        // L'admin le remplace, puis le retire ; supprimer l'élément efface son fichier.
        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $doc), [
            'title' => 'Contrat', 'data' => ['kind' => 'contract', 'status' => 'toSign', 'file' => UploadedFile::fake()->create('v2.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);
        $path = $doc->fresh()->file()['path'];
        $this->assertSame('v2.pdf', $doc->fresh()->file()['name']);

        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $doc), ['title' => 'Contrat', 'data' => ['kind' => 'contract', 'status' => 'toSign'], 'remove' => ['file' => '1']]);
        Storage::disk('local')->assertMissing($path);
        $this->assertNull($doc->fresh()->file());

        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $doc), ['title' => 'Contrat', 'data' => ['kind' => 'contract', 'status' => 'toSign', 'file' => UploadedFile::fake()->create('v3.pdf', 50, 'application/pdf')]]);
        $path = $doc->fresh()->file()['path'];
        $this->actingAs($this->admin)->delete(route('admin.portal-items.destroy', $doc))->assertRedirect();
        Storage::disk('local')->assertMissing($path);
    }

    public function test_document_file_rejects_other_formats(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)->post(route('admin.client-projects.items.store', $this->project), [
            'type' => 'document', 'title' => 'Page', 'data' => ['kind' => 'invoice', 'status' => 'pending', 'file' => UploadedFile::fake()->create('page.html', 2, 'text/html')],
        ])->assertSessionHasErrors('data.file');
        $this->assertSame(0, PortalItem::count());
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
        $this->postJson('/api/v1/portal/tickets', ['title' => 'x', 'priority' => 0, 'project' => $this->project->id], $this->as($token))->assertForbidden();
    }

    public function test_admin_manages_clients_projects_and_their_items(): void
    {
        $this->actingAs($this->admin)->post(route('admin.clients.store'), [
            'name' => 'Paul Mbarga', 'company' => 'Mbarga & Fils', 'email' => 'Paul@Mbarga.cm', 'password' => 'temporaire-123', 'is_active' => '1',
        ])->assertRedirect();
        $paul = User::where('email', 'paul@mbarga.cm')->sole();
        $this->assertSame(User::ROLE_CLIENT, $paul->role);
        $this->assertFalse($paul->isAdmin());
        $this->actingAs($this->admin)->get(route('admin.clients.show', $paul))->assertOk()->assertSee('Aucun projet');

        $this->actingAs($this->admin)->post(route('admin.clients.projects.store', $paul), ['name' => 'Site e-commerce', 'status' => 'active', 'summary' => 'Boutique en ligne'])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.clients.projects.store', $paul), ['name' => 'Refonte', 'status' => 'nope'])->assertSessionHasErrors('status');
        $shop = $paul->clientProjects()->sole();

        $this->actingAs($this->admin)->post(route('admin.client-projects.items.store', $shop), [
            'type' => 'document', 'title' => 'Facture — jalon 2', 'data' => ['kind' => 'invoice', 'amount' => '250000', 'status' => 'pending', 'url' => 'https://drive.example.com/f', 'unknown' => 'x'],
        ])->assertSessionHasNoErrors();
        $item = $shop->items()->sole();
        $this->assertSame($paul->id, $item->user_id);
        $this->assertEquals(['kind' => 'invoice', 'amount' => 250000, 'status' => 'pending', 'url' => 'https://drive.example.com/f'], $item->data);

        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $item), ['title' => 'Facture', 'data' => ['status' => 'nope']])->assertSessionHasErrors('data.status');

        // Un élément change de projet, mais jamais vers le projet d'un autre client.
        $this->actingAs($this->admin)->post(route('admin.clients.projects.store', $paul), ['name' => 'Application', 'status' => 'active']);
        $app = $paul->clientProjects()->where('name', 'Application')->sole();
        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $item), ['title' => 'Facture', 'data' => ['kind' => 'invoice'], 'project' => $this->project->id])->assertSessionHasErrors('project');
        $this->actingAs($this->admin)->put(route('admin.portal-items.update', $item), ['title' => 'Facture', 'data' => ['kind' => 'invoice'], 'project' => $app->id])->assertSessionHasNoErrors();
        $this->assertSame($app->id, $item->fresh()->client_project_id);

        $this->actingAs($this->admin)->get(route('admin.clients.show', ['client' => $paul, 'projet' => $app->id]))->assertOk()->assertSee('Facture')->assertSee('Site e-commerce');
        $this->actingAs($this->admin)->get(route('admin.clients.index'))->assertOk()->assertSee('Mbarga &amp; Fils', false);

        $this->actingAs($this->admin)->put(route('admin.client-projects.update', $app), ['name' => 'Application mobile', 'status' => 'done'])->assertSessionHasNoErrors();
        $this->assertSame(['Application mobile', 'done'], [$app->fresh()->name, $app->fresh()->status]);

        // Supprimer un projet supprime ses éléments, pas ceux des autres projets.
        $this->item($shop, 'task', 'Catalogue', ['owner' => '', 'col' => 0]);
        $this->actingAs($this->admin)->delete(route('admin.client-projects.destroy', $app))->assertRedirect();
        $this->assertModelMissing($item);
        $this->assertSame(['Catalogue'], $paul->portalItems()->pluck('title')->all());
    }

    public function test_admin_shows_tasks_by_kanban_column(): void
    {
        $this->item($this->project, 'task', 'Charte graphique', ['owner' => 'Design', 'col' => 3]);

        $this->actingAs($this->admin)->get(route('admin.clients.show', $this->client))
            ->assertOk()
            ->assertSeeInOrder(['Backlog', 'Sprint en cours', 'En revue', 'Validé', 'Charte graphique', 'Modifier', 'Supprimer']);
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
