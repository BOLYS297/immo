<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Prospect;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FrontFormsApiTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = ['X-Front-Key' => 'front-key', 'X-Client-Ip' => '203.0.113.7'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.front.api_key' => 'front-key']);
        Carbon::setTestNow('2026-10-21 10:00:00');
    }

    private function quote(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Awa Ngono',
            'email' => 'Awa@Example.cm',
            'phone' => '+237 678 261 382',
            'company' => 'Ngono SARL',
            'domain' => 'ia',
            'details' => 'Automatiser nos relances clients et la facturation mensuelle.',
            'locale' => 'fr',
        ];
    }

    private function booking(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Awa Ngono',
            'email' => 'awa@example.cm',
            'company' => 'Ngono SARL',
            'need' => 'web',
            'deadline' => 'month',
            'slot' => '2026-10-22T09:00:00+01:00',
            'locale' => 'en',
        ];
    }

    public function test_form_endpoints_require_the_front_key(): void
    {
        $this->postJson('/api/v1/quotes', $this->quote())->assertUnauthorized();
        $this->postJson('/api/v1/quotes', $this->quote(), ['X-Front-Key' => 'wrong'])->assertUnauthorized();
        $this->getJson('/api/v1/bookings/taken')->assertUnauthorized();

        config(['services.front.api_key' => null]);
        $this->postJson('/api/v1/bookings', $this->booking(), $this->headers)->assertUnauthorized();
    }

    public function test_quote_is_stored_with_its_prospect(): void
    {
        $response = $this->postJson('/api/v1/quotes', $this->quote(), $this->headers)->assertCreated();

        $quote = QuoteRequest::with('prospect')->sole();
        $response->assertJson(['ok' => true, 'reference' => $quote->reference]);
        $this->assertSame(['ia', 'fr', QuoteRequest::STATUS_NEW], [$quote->domain, $quote->locale, $quote->status]);
        $this->assertSame(['awa@example.cm', 'Awa Ngono', '+237 678 261 382', 'Ngono SARL'], [$quote->prospect->email, $quote->prospect->full_name, $quote->prospect->phone, $quote->prospect->company]);
    }

    public function test_returning_prospect_is_updated_not_duplicated(): void
    {
        Prospect::create(['email' => 'awa@example.cm', 'first_name' => 'Awa', 'last_name' => 'Ngono', 'phone' => '000000']);

        $this->postJson('/api/v1/quotes', $this->quote(['name' => 'Awa N. Ngono']), $this->headers)->assertCreated();

        $prospect = Prospect::sole();
        $this->assertSame(['Awa N. Ngono', '+237 678 261 382'], [$prospect->full_name, $prospect->phone]);
    }

    public function test_quote_validation(): void
    {
        $this->postJson('/api/v1/quotes', $this->quote(['phone' => '']), $this->headers)->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->postJson('/api/v1/quotes', $this->quote(['phone' => 'appelez-moi']), $this->headers)->assertJsonValidationErrors('phone');
        $this->postJson('/api/v1/quotes', $this->quote(['domain' => 'marketing', 'details' => 'trop court']), $this->headers)->assertJsonValidationErrors(['domain', 'details']);
        $this->assertDatabaseCount('quote_requests', 0);
    }

    public function test_quote_notifies_the_team_by_email_and_webhook(): void
    {
        config(['services.leads.notify_email' => 'equipe@immo.cm', 'services.leads.quote_webhook' => 'https://n8n.test/quote']);
        Mail::fake();
        Http::fake();

        $this->postJson('/api/v1/quotes', $this->quote(), $this->headers)->assertCreated();

        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://n8n.test/quote' && $r['phone'] === '+237 678 261 382' && str_starts_with($r['reference'], 'DEV-'));
    }

    public function test_booking_is_stored_in_utc_and_its_slot_becomes_taken(): void
    {
        $this->postJson('/api/v1/bookings', $this->booking(), $this->headers)->assertCreated();

        $booking = Booking::sole();
        $this->assertSame('2026-10-22 08:00', $booking->slot->utc()->format('Y-m-d H:i'));
        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->status);

        $this->getJson('/api/v1/bookings/taken', $this->headers)->assertOk()->assertExactJson(['slots' => ['2026-10-22T09:00:00+01:00']]);
    }

    public function test_a_slot_cannot_be_booked_twice_until_cancelled(): void
    {
        $this->postJson('/api/v1/bookings', $this->booking(), $this->headers)->assertCreated();
        $this->postJson('/api/v1/bookings', $this->booking(['email' => 'autre@example.cm']), $this->headers)->assertStatus(409)->assertJson(['error' => 'taken']);

        Booking::sole()->update(['status' => Booking::STATUS_CANCELLED]);

        $this->getJson('/api/v1/bookings/taken', $this->headers)->assertExactJson(['slots' => []]);
        $this->postJson('/api/v1/bookings', $this->booking(['email' => 'autre@example.cm']), $this->headers)->assertCreated();
    }

    public function test_booking_validation(): void
    {
        $this->postJson('/api/v1/bookings', $this->booking(['slot' => '2026-10-20T09:00:00+01:00']), $this->headers)->assertJsonValidationErrors('slot');
        $this->postJson('/api/v1/bookings', $this->booking(['slot' => '2027-01-20T09:00:00+01:00']), $this->headers)->assertJsonValidationErrors('slot');
        $this->postJson('/api/v1/bookings', $this->booking(['need' => 'x', 'deadline' => 'y', 'email' => 'pas-un-email']), $this->headers)->assertJsonValidationErrors(['need', 'deadline', 'email']);
    }

    public function test_forms_are_rate_limited_per_visitor_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/quotes', [], $this->headers)->assertUnprocessable();
        }
        $this->postJson('/api/v1/quotes', [], $this->headers)->assertTooManyRequests();
        $this->postJson('/api/v1/quotes', [], ['X-Client-Ip' => '198.51.100.2'] + $this->headers)->assertUnprocessable();
    }

    public function test_admin_can_list_and_cancel_bookings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $booking = Booking::create($this->booking(['slot' => Carbon::parse('2026-10-22T09:00:00+01:00')->utc(), 'status' => Booking::STATUS_CONFIRMED]));

        $this->get(route('admin.bookings.index'))->assertRedirect(route('admin.login'));
        $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk()->assertSee('Ngono SARL')->assertSee('09:00');

        $this->actingAs($admin)->patch(route('admin.bookings.status', $booking), ['status' => Booking::STATUS_CANCELLED])->assertSessionHas('success');
        $this->assertNull($booking->fresh()->slot_key);
    }

    public function test_cancelled_booking_cannot_be_reactivated_once_its_slot_is_taken(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $slot = Carbon::parse('2026-10-22T09:00:00+01:00')->utc();
        $cancelled = Booking::create($this->booking(['slot' => $slot, 'status' => Booking::STATUS_CANCELLED]));
        Booking::create($this->booking(['slot' => $slot, 'status' => Booking::STATUS_CONFIRMED]));

        $this->actingAs($admin)->patch(route('admin.bookings.status', $cancelled), ['status' => Booking::STATUS_CONFIRMED])->assertSessionHas('error');
        $this->assertSame(Booking::STATUS_CANCELLED, $cancelled->fresh()->status);
    }
}
