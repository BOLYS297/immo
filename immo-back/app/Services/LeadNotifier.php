<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use function Illuminate\Support\defer;

/**
 * Prévient l'équipe d'une demande reçue depuis le site : email (LEADS_NOTIFY_EMAIL) et webhook n8n.
 * Envoyé après la réponse : une panne de messagerie ou de n8n n'empêche jamais l'enregistrement.
 */
class LeadNotifier
{
    /**
     * @param  'quote_webhook'|'booking_webhook'|'message_webhook'|null  $webhook  null : email seulement
     * @param  array<string, mixed>  $payload  données transmises telles quelles au workflow n8n
     */
    public static function send(string $subject, string $body, ?string $webhook = null, array $payload = []): void
    {
        $email = config('services.leads.notify_email');
        $url = $webhook ? config("services.leads.{$webhook}") : null;

        defer(function () use ($subject, $body, $email, $url, $payload) {
            if ($email) {
                try {
                    Mail::raw($body, fn ($message) => $message->to($email)->subject($subject));
                } catch (\Throwable $e) {
                    Log::warning('[leads] Email de notification non envoyé : '.$e->getMessage());
                }
            }
            if ($url) {
                try {
                    Http::timeout(15)->post($url, $payload)->throw();
                } catch (\Throwable $e) {
                    Log::warning('[leads] Webhook n8n en échec : '.$e->getMessage());
                }
            }
        });
    }
}
