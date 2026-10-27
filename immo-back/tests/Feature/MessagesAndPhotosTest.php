<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Media;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Formulaire « Écrivez-nous » → admin Messages ; photo des témoignages ; bouton afficher le mot de passe. */
class MessagesAndPhotosTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = ['X-Front-Key' => 'front-key', 'X-Client-Ip' => '203.0.113.9'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        Mail::fake();
        config(['services.front.api_key' => 'front-key', 'services.leads.notify_email' => 'equipe@example.com']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function message(array $overrides = []): array
    {
        return $overrides + ['name' => 'Awa Ndiaye', 'email' => 'Awa@Example.com', 'phone' => '+237 600 000 000', 'subject' => 'Question', 'message' => 'Bonjour, une question sur vos délais.'];
    }

    public function test_a_site_message_reaches_the_admin_dashboard(): void
    {
        $this->postJson('/api/v1/messages', $this->message(), $this->headers)->assertCreated();

        $message = ContactMessage::firstOrFail();
        $this->assertSame(['awa@example.com', ContactMessage::STATUS_NEW], [$message->email, $message->status]);
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('Awa Ndiaye')->assertSee('Question');
        $this->actingAs($this->admin)->get(route('admin.contact.index'))->assertOk()->assertSee('Awa Ndiaye');
        $this->actingAs($this->admin)->get(route('admin.contact.show', $message))->assertOk()->assertSee('une question sur vos délais');
    }

    public function test_messages_require_the_front_key_and_valid_fields(): void
    {
        $this->postJson('/api/v1/messages', $this->message())->assertUnauthorized();
        $this->postJson('/api/v1/messages', $this->message(['email' => 'pas-un-email', 'message' => 'court']), $this->headers)->assertUnprocessable()->assertJsonValidationErrors(['email', 'message']);
        $this->postJson('/api/v1/messages', $this->message(['phone' => null]), $this->headers)->assertCreated();
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_testimonial_photo_comes_from_the_media_library(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin)->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('portrait.jpg', 400, 400)]]);
        $media = Media::latest('id')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.testimonials.store'), [
            'client_name' => 'Client réel', 'role' => 'DG', 'company' => 'Société', 'photo_id' => $media->id,
            'content' => ['fr' => 'Très bon accompagnement.', 'en' => ''], 'rating' => 5, 'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $photo = $this->getJson('/api/v1/testimonials')->json('data.0.photo');
        $this->assertSame([$media->id, 400], [$photo['id'], $photo['width']]);
        $this->actingAs($this->admin)->get(route('admin.testimonials.edit', Testimonial::first()))->assertSee('data-media-field', false);

        $this->actingAs($this->admin)->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);
    }

    public function test_password_fields_get_the_show_hide_script(): void
    {
        $this->get(route('admin.login'))->assertSee('js/password-toggle.js', false);
        $this->actingAs($this->admin)->get(route('admin.account.edit'))->assertSee('js/password-toggle.js', false);
    }
}
