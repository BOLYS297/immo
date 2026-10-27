<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalItem;
use App\Models\User;
use App\Services\LeadNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Espace client du front Next.js. Le serveur Next garde le jeton Sanctum dans son cookie de session
 * (httpOnly) et appelle ces routes de serveur à serveur ; le navigateur ne voit jamais le jeton.
 * Jeton « portal:write » à la connexion, « portal:read » seul pour l'aperçu administrateur.
 */
class PortalController extends Controller
{
    /** POST /api/v1/portal/login */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $user = User::clients()->where('email', strtolower(trim($credentials['email'])))->first();

        // Hash vérifié même sans compte : même durée de réponse, l'existence d'un email ne se devine pas.
        $valid = Hash::check($credentials['password'], $user?->password ?? '$2y$12$'.str_repeat('a', 53));
        if (! $user || ! $valid || ! $user->isClient()) {
            return response()->json(['error' => 'invalid'], 401);
        }

        return response()->json($this->issueToken($user, ['portal:read', 'portal:write'], now()->addDays(config('portal.session_days'))));
    }

    /** POST /api/v1/portal/preview — échange le code à usage unique créé depuis l'admin contre un jeton en lecture seule. */
    public function preview(Request $request): JsonResponse
    {
        $code = (string) $request->input('code');
        $userId = strlen($code) === 40 ? Cache::pull("portal-preview:{$code}") : null;
        $user = $userId ? User::clients()->find($userId) : null;

        if (! $user) {
            return response()->json(['error' => 'invalid'], 401);
        }

        return response()->json($this->issueToken($user, ['portal:read'], now()->addMinutes(30)));
    }

    /** GET /api/v1/portal/me — le client et tous ses éléments. */
    public function me(Request $request): JsonResponse
    {
        $user = $this->client($request);

        return response()->json([
            'user' => $this->userPayload($user),
            'preview' => ! $request->user()->tokenCan('portal:write'),
            'items' => $user->portalItems()->latest()->get()->map(fn (PortalItem $item) => $item->toPortalArray()),
        ]);
    }

    /** POST /api/v1/portal/logout */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    /** POST /api/v1/portal/documents/{item}/sign — acceptation en ligne d'un document « à signer ». */
    public function sign(Request $request, PortalItem $item): JsonResponse
    {
        $user = $this->client($request, write: true);

        if ($item->user_id !== $user->id || $item->type !== 'document' || $item->field('status') !== 'toSign') {
            return response()->json(['error' => 'invalid'], 400);
        }

        $item->update(['data' => ['status' => 'signed', 'signedAt' => now()->toIso8601String(), 'signedBy' => $user->email] + $item->data]);

        LeadNotifier::send("Document signé — {$item->title}", "{$user->name} ({$user->company}) <{$user->email}> a signé « {$item->title} ».\n\n".route('admin.clients.show', $user));

        return response()->json(['ok' => true]);
    }

    /** POST /api/v1/portal/tickets */
    public function ticket(Request $request): JsonResponse
    {
        $user = $this->client($request, write: true);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'integer', 'between:0,2'],
        ]);

        $item = $user->portalItems()->create([
            'type' => 'ticket',
            'title' => trim($validated['title']),
            'data' => ['message' => trim($validated['message'] ?? ''), 'priority' => (int) $validated['priority'], 'status' => 'open', 'reply' => ''],
        ]);

        $priority = config('portal.types.ticket.fields.priority.options')[$item->data['priority']];
        LeadNotifier::send("Nouveau ticket ({$priority}) — {$item->title}", "{$user->name} ({$user->company}) <{$user->email}>\n\n{$item->data['message']}\n\n".route('admin.clients.show', $user));

        return response()->json(['ok' => true, 'id' => (string) $item->id], 201);
    }

    /** PUT /api/v1/portal/password — les autres connexions du client sont fermées. */
    public function password(Request $request): JsonResponse
    {
        $user = $this->client($request, write: true);

        $validated = $request->validate([
            'current' => ['required', 'string'],
            'next' => ['required', 'string', 'min:10', 'max:100'],
        ]);

        if (! Hash::check($validated['current'], $user->password)) {
            return response()->json(['error' => 'wrong'], 403);
        }

        $user->update(['password' => $validated['next']]);
        $user->tokens()->whereKeyNot($request->user()->currentAccessToken()->getKey())->delete();

        return response()->json(['ok' => true]);
    }

    /** Client actif authentifié par le jeton ; un compte désactivé perd l'accès immédiatement. */
    private function client(Request $request, bool $write = false): User
    {
        $user = $request->user();
        abort_unless($user?->isClient(), 403, 'Compte client inactif.');
        abort_if($write && ! $user->tokenCan('portal:write'), 403, 'Aperçu en lecture seule.');

        return $user;
    }

    private function issueToken(User $user, array $abilities, \DateTimeInterface $expiresAt): array
    {
        return [
            'token' => $user->createToken('portal', $abilities, $expiresAt)->plainTextToken,
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'user' => $this->userPayload($user),
        ];
    }

    private function userPayload(User $user): array
    {
        return ['id' => (string) $user->id, 'name' => $user->name, 'company' => (string) $user->company, 'email' => $user->email];
    }
}
