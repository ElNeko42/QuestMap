<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quest;
use App\Support\GeoPoint;
use Illuminate\Database\Eloquent\Collection;

class QuestService
{
    /**
     * Active quests within $radiusMeters of $point, nearest first. Each returned
     * Quest carries a computed `dist_m` attribute (meters, float).
     *
     * @return Collection<int, Quest>
     */
    public function nearby(
        GeoPoint $point,
        int $radiusMeters,
        ?int $tenantId = null,
        ?string $category = null,
    ): Collection {
        $wkt = sprintf('SRID=4326;%s', $point->toWkt());

        $query = Quest::query()
            ->active()
            ->select('quests.*')
            ->selectRaw(
                'ST_Distance(location, ST_GeogFromText(?)) AS dist_m',
                [$wkt],
            )
            ->whereRaw(
                'ST_DWithin(location, ST_GeogFromText(?), ?)',
                [$wkt, $radiusMeters],
            )
            ->orderBy('dist_m');

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($category !== null) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    /**
     * Server-side geofence check for a quest using PostGIS ST_DWithin against the
     * quest's own geofence_radius_m. Never trust a client-computed distance.
     */
    public function isWithinGeofence(Quest $quest, GeoPoint $point): bool
    {
        $wkt = sprintf('SRID=4326;%s', $point->toWkt());

        return (bool) Quest::query()
            ->whereKey($quest->getKey())
            ->whereRaw(
                'ST_DWithin(location, ST_GeogFromText(?), ?)',
                [$wkt, $quest->geofence_radius_m],
            )
            ->exists();
    }

    /**
     * Exact distance in meters between the quest and a point (PostGIS).
     */
    public function distanceMeters(Quest $quest, GeoPoint $point): float
    {
        $wkt = sprintf('SRID=4326;%s', $point->toWkt());

        $row = Quest::query()
            ->whereKey($quest->getKey())
            ->selectRaw('ST_Distance(location, ST_GeogFromText(?)) AS dist_m', [$wkt])
            ->first();

        return (float) ($row->dist_m ?? 0.0);
    }
}
