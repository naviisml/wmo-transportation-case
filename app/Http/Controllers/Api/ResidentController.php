<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListResidentsRequest;
use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Residents', weight: 1)]
class ResidentController extends Controller
{
    /**
     * List Utrecht residents.
     *
     * Lists residents registered in Utrecht, including those without a Wmo authorization.
     * The `search` parameter matches names and resident references. Follow `links.next` for the next page.
     */
    public function index(ListResidentsRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        $residents = Resident::query()
            ->visibleTo($request->user())
            ->with(['address', 'authorization.budgetPeriods'])
            ->search($data['search'] ?? null)
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return ResidentResource::collection($residents);
    }

    /**
     * Get a Utrecht resident.
     *
     * Includes the home address, Wmo authorization and budget periods when available.
     */
    public function show(Request $request, int $resident): ResidentResource
    {
        Gate::authorize('viewAny', Resident::class);
        $model = Resident::query()
            ->visibleTo($request->user())
            ->with(['address', 'authorization.budgetPeriods'])
            ->findOrFail($resident);
        Gate::authorize('view', $model);

        return new ResidentResource($model);
    }
}
