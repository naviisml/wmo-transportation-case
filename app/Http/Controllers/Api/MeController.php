<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Demo support', weight: 3)]
class MeController extends Controller
{
    /**
     * Get the authenticated account.
     */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
