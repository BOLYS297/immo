<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Models\QuoteRequest;
use App\Services\LeadNotifier;
use App\Services\QuoteReferenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    /** POST /api/v1/quotes — formulaire de chiffrage de la page /services du front. */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ().-]{6,30}$/'],
            'company' => ['required', 'string', 'max:100'],
            'domain' => ['required', Rule::in(array_keys(QuoteRequest::DOMAINS))],
            'details' => ['required', 'string', 'min:20', 'max:1500'],
            'locale' => ['nullable', Rule::in(array_keys(config('cms.locales')))],
        ]);

        $quote = DB::transaction(function () use ($validated) {
            // Le site demande un nom complet en un seul champ : il est rangé dans last_name, sans découpage
            // (prénom et nom ne se séparent pas de façon fiable). first_name est vidé pour ne pas doubler l'affichage.
            $prospect = Prospect::firstOrNew(['email' => strtolower(trim($validated['email']))]);
            $prospect->fill([
                'last_name' => trim($validated['name']),
                'first_name' => null,
                'phone' => trim($validated['phone']),
                'company' => trim($validated['company']),
            ])->save();

            return QuoteRequest::create([
                'prospect_id' => $prospect->id,
                'reference' => QuoteReferenceGenerator::generate(),
                'domain' => $validated['domain'],
                'locale' => $validated['locale'] ?? null,
                'description' => trim($validated['details']),
                'status' => QuoteRequest::STATUS_NEW,
                'privacy_consent' => true, // mention d'information affichée sous le formulaire du front
            ]);
        });

        LeadNotifier::send(
            "Nouvelle demande de devis {$quote->reference} — {$validated['company']}",
            "{$validated['name']} <{$validated['email']}> · {$validated['phone']}\nDomaine : {$quote->domain_label}\n\n{$validated['details']}\n\n".route('admin.quotes.show', $quote),
            'quote_webhook',
            ['reference' => $quote->reference] + collect($validated)->only(['name', 'email', 'phone', 'company', 'domain', 'details'])->all(),
        );

        return response()->json(['ok' => true, 'reference' => $quote->reference], 201);
    }
}
