<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\AntiCheatException;
use App\Http\Requests\LocationRequest;
use App\Http\Resources\CompletionResource;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Services\AntiCheatService;
use App\Support\GeoPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeController extends Controller
{
    public function __construct(private readonly AntiCheatService $antiCheat) {}

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Update the user's location: anti-cheat check, then persist to history and
     * last_location. On first location, assign the user to the nearest active
     * tenant whose operating area contains the point.
     */
    public function updateLocation(LocationRequest $request): JsonResponse
    {
        $user = $request->user();
        $point = $request->geoPoint();
        $now = now();

        $result = $this->antiCheat->check($user, $point, $now);
        if (! $result->allowed) {
            throw AntiCheatException::implausibleSpeed($result->speedKmh ?? 0.0);
        }

        $this->antiCheat->record($user, $point, $now);

        if ($user->tenant_id === null) {
            $this->assignTenant($user, $point);
        }

        return response()->json([
            'user' => new UserResource($user->fresh()),
            'implied_speed_kmh' => $result->speedKmh !== null ? round($result->speedKmh, 1) : null,
        ]);
    }

    public function completions(Request $request): AnonymousResourceCollection
    {
        $completions = $request->user()->completions()
            ->with('quest')
            ->latest('submitted_at')
            ->paginate(20);

        return CompletionResource::collection($completions);
    }

    /**
     * Assign the user to the first active tenant whose radius contains the point.
     */
    private function assignTenant($user, GeoPoint $point): void
    {
        $wkt = sprintf('SRID=4326;%s', $point->toWkt());

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()
            ->where('active', true)
            ->whereRaw(
                'ST_DWithin(center_point, ST_GeogFromText(?), radius_km * 1000)',
                [$wkt],
            )
            ->orderByRaw('ST_Distance(center_point, ST_GeogFromText(?))', [$wkt])
            ->first();

        if ($tenant !== null) {
            $user->forceFill(['tenant_id' => $tenant->id])->save();
        }
    }
}
