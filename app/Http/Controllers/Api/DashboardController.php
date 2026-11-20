<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Queries\DashboardSummary;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Demo support', weight: 3)]
class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     *
     * Ride totals cover the taxi company's assignments or the callcenter account's bookings.
     * Municipal accounts get aggregate resident, authorization, budget and parcel figures.
     */
    public function __invoke(Request $request, DashboardSummary $summary): DashboardResource
    {
        Gate::authorize('view-dashboard');

        return new DashboardResource($summary->forUser($request->user()));
    }
}
