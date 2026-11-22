<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateTokenRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\TransientToken;

#[Group('Demo support', weight: 3)]
class TokenController extends Controller
{
    private function sessionOnly(Request $request): void
    {
        abort_unless($request->user()->currentAccessToken() instanceof TransientToken, 403);
    }

    /**
     * List personal access tokens.
     *
     * Lists token metadata for the signed-in account. Requires a web session.
     */
    public function index(Request $request): JsonResponse
    {
        $this->sessionOnly($request);

        return response()->json(['data' => $request->user()->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])]);
    }

    /**
     * Create a personal access token.
     *
     * Requires a web session and the account password. Abilities are assigned by role.
     * The token expires after 30 days. The plaintext value appears only in the creation response.
     */
    public function store(CreateTokenRequest $request): JsonResponse
    {
        $abilities = match ($request->user()->role) {
            Role::Callcenter => ['residents:read', 'rides:book', 'dashboard:read'],
            Role::TaxiCompany => ['rides:read', 'dashboard:read'],
            Role::Municipality => ['me:read', 'dashboard:read', 'residents:read'],
        };
        $token = $request->user()->createToken($request->validated('name'), $abilities, now()->addDays(30));

        return response()->json(['token' => $token->plainTextToken, 'expires_at' => $token->accessToken->expires_at], 201);
    }

    /**
     * Revoke a personal access token.
     *
     * Requires a web session. The token must belong to the signed-in account.
     */
    public function destroy(Request $request, int $token): Response
    {
        $this->sessionOnly($request);
        $request->user()->tokens()->findOrFail($token)->delete();

        return response()->noContent();
    }
}
