<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListRidesRequest;
use App\Http\Resources\RideResource;
use App\Models\Ride;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Rides', weight: 2)]
class RideController extends Controller
{
    /**
     * List assigned rides.
     *
     * Returns rides assigned to the authenticated taxi company. The `from` and `to` dates
     * are inclusive and use Amsterdam time.
     */
    public function index(ListRidesRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();

        $rides = Ride::query()
            ->visibleTo($request->user())
            ->with(['resident', 'taxiCompany'])
            ->when($data['from'] ?? null, fn ($query, $date) => $query->where(
                'scheduled_at', '>=', CarbonImmutable::parse($date, 'Europe/Amsterdam')->utc(),
            ))
            ->when($data['to'] ?? null, fn ($query, $date) => $query->where(
                'scheduled_at', '<', CarbonImmutable::parse($date, 'Europe/Amsterdam')->addDay()->utc(),
            ))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return RideResource::collection($rides);
    }
}
