<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Articles Tech Insights repris du front (data/articles.json, exporté de immo-front/.data/db.json).
 * Un article déjà présent (même slug) n'est jamais modifié : les corrections faites dans l'admin sont conservées.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->orderBy('id')->first();
        if (! $author) {
            $this->command?->warn('ArticleSeeder : aucun administrateur, articles non importés.');

            return;
        }

        $articles = json_decode(file_get_contents(__DIR__.'/data/articles.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach ($articles as $article) {
            if (Post::where('slug', $article['slug'])->exists()) {
                continue;
            }

            $text = fn (string $key) => array_filter(['fr' => $article['fr'][$key] ?? null, 'en' => $article['en'][$key] ?? null], fn ($v) => filled($v));

            Post::create([
                'user_id' => $author->id,
                'slug' => $article['slug'],
                'title' => $text('title'),
                'summary' => $text('excerpt'),
                'content' => $text('body'),
                'tags' => $text('tags'),
                'reading_minutes' => $article['readingMinutes'],
                'published_at' => Carbon::parse($article['date'], config('app.timezone'))->startOfDay(),
                'is_published' => $article['published'],
            ]);
        }
    }
}
