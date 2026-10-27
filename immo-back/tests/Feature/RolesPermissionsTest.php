<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RolesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasse2026';

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function member(string $role, array $attributes = []): User
    {
        return User::factory()->create($attributes + ['role' => $role, 'is_active' => true]);
    }

    private function makePost(User $author, bool $published = false): Post
    {
        return Post::create([
            'user_id' => $author->id, 'slug' => 'article-'.uniqid(), 'title' => ['fr' => 'Titre'], 'summary' => ['fr' => 'Résumé'],
            'content' => ['fr' => 'Texte'], 'published_at' => now(), 'reading_minutes' => 3, 'is_published' => $published,
        ]);
    }

    private function postInput(array $overrides = []): array
    {
        return array_replace_recursive([
            'title' => ['fr' => 'Mon article', 'en' => ''], 'summary' => ['fr' => 'Résumé', 'en' => ''], 'content' => ['fr' => 'Texte', 'en' => ''],
            'published_at' => now()->toDateString(), 'reading_minutes' => 4, 'is_published' => '1',
        ], $overrides);
    }

    public function test_each_role_reaches_only_its_sections(): void
    {
        $matrix = [
            // rôle => [rubriques autorisées, rubriques interdites]
            'editor' => [['admin.pages.index', 'admin.media.index', 'admin.menus.index', 'admin.posts.index', 'admin.services.index', 'admin.faqs.index'], ['admin.quotes.index', 'admin.clients.index', 'admin.settings.index', 'admin.users.index']],
            'author' => [['admin.posts.index', 'admin.posts.create', 'admin.account.edit'], ['admin.pages.index', 'admin.media.index', 'admin.menus.index', 'admin.quotes.index', 'admin.settings.index', 'admin.users.index', 'admin.services.index']],
            'sales' => [['admin.quotes.index', 'admin.bookings.index', 'admin.contact.index', 'admin.clients.index'], ['admin.pages.index', 'admin.posts.index', 'admin.settings.index', 'admin.users.index', 'admin.menus.index']],
            'admin' => [['admin.quotes.index', 'admin.pages.index', 'admin.settings.index', 'admin.users.index', 'admin.menus.index'], []],
        ];

        foreach ($matrix as $role => [$allowed, $denied]) {
            $user = $this->member($role);
            $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
            foreach ($allowed as $route) {
                $this->actingAs($user)->get(route($route))->assertOk();
            }
            foreach ($denied as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden()->assertSee('pas ouverte à votre rôle');
            }
        }
    }

    public function test_sidebar_and_dashboard_hide_what_the_role_cannot_use(): void
    {
        $this->actingAs($this->member('author'))->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Mes articles')
            ->assertDontSee('Demandes de devis')
            ->assertDontSee('Dernières demandes de devis')
            ->assertDontSee('Réglages du site')
            ->assertDontSee('Utilisateurs & rôles');

        $this->actingAs($this->member('sales'))->get(route('admin.dashboard'))
            ->assertSee('Demandes de devis')
            ->assertDontSee('Pages & Sections');
    }

    public function test_clients_and_inactive_members_cannot_use_the_admin(): void
    {
        $this->actingAs($this->member(User::ROLE_CLIENT))->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->actingAs($this->member('editor', ['is_active' => false]))->get(route('admin.pages.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($this->member('user'))->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_legacy_super_admin_keeps_full_access(): void
    {
        $this->actingAs($this->member('super_admin'))->get(route('admin.users.index'))->assertOk();
    }

    public function test_author_writes_drafts_only_on_own_posts(): void
    {
        $author = $this->member('author');
        $other = $this->member('editor');

        // Création : brouillon forcé, même en cochant « publié »
        $this->actingAs($author)->post(route('admin.posts.store'), $this->postInput())->assertRedirect();
        $own = Post::where('user_id', $author->id)->firstOrFail();
        $this->assertFalse($own->is_published);

        // Liste : ses articles seulement
        $foreign = $this->makePost($other);
        $this->actingAs($author)->get(route('admin.posts.index'))->assertSee('Mon article')->assertDontSee(route('admin.posts.edit', $foreign));

        // Pas d'accès aux articles des autres
        $this->actingAs($author)->get(route('admin.posts.edit', $foreign))->assertForbidden();
        $this->actingAs($author)->delete(route('admin.posts.destroy', $foreign))->assertForbidden();

        // Une fois publié par un éditeur, l'article n'est plus modifiable par son auteur
        $own->update(['is_published' => true]);
        $this->actingAs($author)->put(route('admin.posts.update', $own), $this->postInput(['title' => ['fr' => 'Modifié en douce']]))->assertForbidden();
        $this->assertSame('Mon article', $own->fresh()->getTranslation('title', 'fr'));
    }

    public function test_editor_publishes_any_post(): void
    {
        $draft = $this->makePost($this->member('author'));

        $this->actingAs($this->member('editor'))->put(route('admin.posts.update', $draft), $this->postInput())->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($draft->fresh()->is_published);
    }

    public function test_admin_creates_and_updates_team_members(): void
    {
        $admin = $this->member('admin');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Rédacteur');
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Awa Rédactrice', 'email' => 'awa@example.com', 'role' => 'author',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $awa = User::where('email', 'awa@example.com')->firstOrFail();
        $this->assertSame('author', $awa->role);
        $this->assertTrue($awa->isStaff());

        // Promotion, sans changer le mot de passe
        $hash = $awa->password;
        $this->actingAs($admin)->put(route('admin.users.update', $awa), ['name' => 'Awa', 'email' => 'awa@example.com', 'role' => 'editor', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('editor', $awa->fresh()->role);
        $this->assertSame($hash, $awa->fresh()->password);

        // Mots de passe faibles refusés ; les clients ne sont pas des membres de l'équipe
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'author', 'password' => 'court', 'password_confirmation' => 'court'])->assertSessionHasErrors('password');
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'client', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD])->assertSessionHasErrors('role');
        $this->actingAs($admin)->get(route('admin.users.edit', $this->member(User::ROLE_CLIENT)))->assertNotFound();
    }

    public function test_admins_cannot_lock_themselves_or_the_team_out(): void
    {
        $admin = $this->member('admin');

        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'author', 'is_active' => '1'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'is_active' => '0'])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->isAdmin());

        // Un autre administrateur, lui, peut le faire ; le compte rétrogradé perd aussitôt l'accès aux utilisateurs.
        $second = $this->member('admin');
        $this->actingAs($second)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'editor', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('editor', $admin->fresh()->role);
        $this->actingAs($admin->fresh())->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_member_updates_own_account_with_current_password(): void
    {
        $user = $this->member('author', ['password' => self::PASSWORD]);

        $this->actingAs($user)->put(route('admin.account.update'), ['name' => 'Nouveau nom', 'email' => $user->email])->assertSessionHasNoErrors();
        $this->assertSame('Nouveau nom', $user->fresh()->name);

        $this->actingAs($user)->put(route('admin.account.update'), ['name' => 'N', 'email' => $user->email, 'password' => 'NouveauSecret2026', 'password_confirmation' => 'NouveauSecret2026'])->assertSessionHasErrors('current_password');
        $this->actingAs($user)->put(route('admin.account.update'), ['name' => 'N', 'email' => 'autre@example.com', 'current_password' => 'faux'])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put(route('admin.account.update'), [
            'name' => 'N', 'email' => $user->email, 'current_password' => self::PASSWORD, 'password' => 'NouveauSecret2026', 'password_confirmation' => 'NouveauSecret2026',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(password_verify('NouveauSecret2026', $user->fresh()->password));
    }
}
