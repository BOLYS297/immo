<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
    }

    private function makePost(array $overrides = []): Post
    {
        return Post::create($overrides + [
            'user_id' => $this->admin->id,
            'slug' => 'mon-article',
            'title' => ['fr' => 'Titre FR', 'en' => 'Title EN'],
            'summary' => ['fr' => 'Résumé', 'en' => 'Summary'],
            'content' => ['fr' => "## Partie\n\nTexte", 'en' => "## Part\n\nText"],
            'tags' => ['fr' => ['IA'], 'en' => ['AI']],
            'reading_minutes' => 4,
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);
    }

    public function test_posts_list_and_detail_in_the_requested_locale(): void
    {
        $this->makePost();
        $this->makePost(['slug' => 'brouillon', 'is_published' => false]);
        $this->makePost(['slug' => 'futur', 'published_at' => now()->addWeek()]);

        $this->getJson('/api/v1/posts?locale=en')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Title EN')
            ->assertJsonPath('data.0.tags', ['AI'])
            ->assertJsonMissingPath('data.0.body');

        $this->getJson('/api/v1/posts/mon-article?locale=en')->assertOk()->assertJsonPath('data.body', "## Part\n\nText");
        $this->getJson('/api/v1/posts/brouillon')->assertNotFound();
        $this->getJson('/api/v1/posts/futur')->assertNotFound();
    }

    public function test_an_incomplete_translation_falls_back_to_the_whole_french_article(): void
    {
        $this->makePost(['title' => ['fr' => 'Titre FR', 'en' => 'Title EN'], 'content' => ['fr' => 'Texte FR']]);

        $this->getJson('/api/v1/posts/mon-article?locale=en')
            ->assertJsonPath('data.locale', 'fr')
            ->assertJsonPath('data.title', 'Titre FR')
            ->assertJsonPath('data.body', 'Texte FR');
    }

    public function test_article_seeder_imports_the_front_articles_once(): void
    {
        $this->seed(ArticleSeeder::class);
        $this->seed(ArticleSeeder::class);

        $this->assertSame(3, Post::count());
        $this->getJson('/api/v1/posts/automatiser-un-processus-avec-n8n?locale=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Automating a business process with n8n: where to start')
            ->assertJsonPath('data.readingMinutes', 4);
    }

    public function test_admin_creates_a_bilingual_post_and_can_clear_a_translation(): void
    {
        $data = [
            'title' => ['fr' => 'Nouvel article', 'en' => 'New article'],
            'summary' => ['fr' => 'Résumé', 'en' => 'Summary'],
            'content' => ['fr' => 'Texte', 'en' => 'Text'],
            'tags' => ['fr' => 'IA, n8n , ', 'en' => 'AI'],
            'meta_title' => ['fr' => '', 'en' => 'SEO EN'],
            'published_at' => '2026-10-20',
            'reading_minutes' => 3,
            'is_published' => '1',
        ];
        $this->actingAs($this->admin)->post(route('admin.posts.store'), $data)->assertSessionHasNoErrors();

        $post = Post::sole();
        $this->assertSame('nouvel-article', $post->slug);
        $this->assertSame(['IA', 'n8n'], $post->getTranslation('tags', 'fr'));

        $data['meta_title']['en'] = '';
        $data['content']['en'] = '';
        $this->actingAs($this->admin)->put(route('admin.posts.update', $post), $data + ['slug' => 'nouvel-article'])->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('', $post->getTranslation('meta_title', 'en', false));
        $this->assertSame('fr', $post->localeFor('en'));
    }

    public function test_post_requires_the_french_version(): void
    {
        $this->actingAs($this->admin)->post(route('admin.posts.store'), [
            'title' => ['en' => 'Only English'], 'summary' => ['en' => 'x'], 'content' => ['en' => 'x'], 'published_at' => '2026-10-20', 'reading_minutes' => 3,
        ])->assertSessionHasErrors(['title.fr', 'summary.fr', 'content.fr']);
    }

    public function test_testimonials_api_returns_published_ones_with_french_fallback(): void
    {
        Testimonial::create(['client_name' => 'Awa', 'role' => 'DG', 'company' => 'Ngono SARL', 'pole' => 'IA & Data', 'content' => ['fr' => 'Très bien', 'en' => 'Great'], 'metric' => ['fr' => '12 h gagnées'], 'rating' => 5, 'is_published' => true]);
        Testimonial::create(['client_name' => 'Brouillon', 'content' => ['fr' => 'x'], 'rating' => 5, 'is_published' => false]);

        $this->getJson('/api/v1/testimonials?locale=en')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quote', 'Great')
            ->assertJsonPath('data.0.metric', '12 h gagnées')
            ->assertJsonPath('data.0.company', 'Ngono SARL');
    }

    public function test_admin_manages_bilingual_testimonials(): void
    {
        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), [
            'client_name' => 'Awa', 'role' => 'DG', 'company' => 'Ngono SARL', 'content' => ['fr' => 'Très bien', 'en' => 'Great'], 'metric' => ['fr' => '', 'en' => ''], 'rating' => 5, 'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $testimonial = Testimonial::sole();
        $this->assertEquals(['fr' => 'Très bien', 'en' => 'Great'], $testimonial->getTranslations('content'));
        $this->actingAs($this->admin)->get(route('admin.testimonials.index'))->assertOk()->assertSee('DG, Ngono SARL');
        $this->actingAs($this->admin)->get(route('admin.testimonials.edit', $testimonial))->assertOk()->assertSee('Great');
        $this->actingAs($this->admin)->get(route('admin.posts.create'))->assertOk();
    }

    public function test_saving_content_revalidates_the_matching_front_tag(): void
    {
        config(['services.front.url' => 'http://front.test', 'services.front.revalidate_secret' => 'secret']);
        Http::fake();

        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), ['client_name' => 'Awa', 'content' => ['fr' => 'Très bien'], 'rating' => 5]);

        Http::assertSent(fn (HttpRequest $r) => $r['tags'] === ['testimonials']);
    }

    public function test_pages_index_lists_published_slugs(): void
    {
        Page::create(['slug' => 'home', 'title' => ['fr' => 'Accueil'], 'is_published' => true])->publish();
        Page::create(['slug' => 'brouillon', 'title' => ['fr' => 'B'], 'is_published' => true]); // jamais publiée

        $this->getJson('/api/v1/pages')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'home');
    }
}
