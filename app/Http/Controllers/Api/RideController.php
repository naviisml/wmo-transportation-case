<?php

namespace App\Http\Controllers\Api;

use App\Actions\BookRide;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BookRideRequest;
use App\Http\Requests\Api\ListRidesRequest;
use App\Http\Resources\RideResource;
use App\Models\Ride;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\IgnoreParam;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
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

    /**
     * Book a ride.
     *
     * The Wmo authorization must be active at booking and on the ride date.
     * The resident's home address determines the parcel. The ride date determines
     * which taxi assignment and budget period apply. A successful booking debits that budget immediately.
     * Send `scheduled_at` as a future ISO 8601 timestamp with a UTC offset or `Z`.
     *
     * The same account, key and booking details return the existing ride with HTTP 201.
     */
    #[HeaderParameter(
        'Idempotency-Key',
        description: 'A unique key for the booking within the authenticated account. Reuse it when retrying the same booking.',
        required: true,
        type: 'string',
        example: 'booking-example-001',
    )]
    #[IgnoreParam('idempotency_key', in: 'body')]
    #[Response(
        409,
        description: 'Inactive authorization, missing budget period, insufficient allowance, missing parcel or assignment, or a key reused for a different booking.',
        type: 'array{message: string}',
        examples: ['message' => 'Insufficient distance budget.'],
    )]
    #[BodyParameter('origin', example: 'Voorbeeldstraat 1, Utrecht')]
    #[BodyParameter('destination', example: 'Demo ontmoetingscentrum, Utrecht')]
    #[BodyParameter('distance_meters', description: 'Ride distance in whole meters.', example: 12000)]
    public function store(BookRideRequest $request, BookRide $action): JsonResponse
    {
        $data = $request->validated();
        $key = $data['idempotency_key'];
        unset($data['idempotency_key']);
        $ride = $action->handle($request->user(), $data, $key);

        return (new RideResource($ride->load(['resident', 'taxiCompany', 'budgetPeriod'])))->response()->setStatusCode(201);
    }
}
