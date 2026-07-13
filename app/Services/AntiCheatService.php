<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserLocationHistory;
use App\Support\GeoPoint;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * Basic anti-cheat: reject a location/action when the implied speed relative to
 * the user's previous recorded location exceeds a configured threshold
 * (teleporting / GPS spoofing).
 */
class AntiCheatService
{
    /**
     * Implied speed in km/h between two points over an elapsed time (seconds).
     * Returns 0.0 for non-positive elapsed time (caller decides how to treat it).
     */
    public function impliedSpeedKmh(GeoPoint $from, GeoPoint $to, float $seconds): float
    {
        if ($seconds <= 0) {
            return 0.0;
        }

        $meters = $from->distanceMeters($to);

        return ($meters / $seconds) * 3.6;
    }

    /**
     * Evaluate whether the user is allowed to be at $point at time $at, based on
     * their most recent location history sample. First-ever location is allowed.
     */
    public function check(User $user, GeoPoint $point, CarbonInterface $at): AntiCheatResult
    {
        /** @var UserLocationHistory|null $last */
        $last = UserLocationHistory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if ($last === null || $last->location === null) {
            return AntiCheatResult::allow();
        }

        $seconds = $at->getTimestamp() - $last->recorded_at->getTimestamp();

        // Samples too close in time produce meaningless (huge) speeds from GPS
        // jitter — don't penalize them.
        if ($seconds < config('questmap.min_speed_sample_seconds')) {
            return AntiCheatResult::allow();
        }

        $speed = $this->impliedSpeedKmh($last->location, $point, (float) $seconds);
        $maxSpeed = (float) config('questmap.max_speed_kmh');

        if ($speed > $maxSpeed) {
            Log::warning('Anti-cheat: implausible speed rejected', [
                'user_id' => $user->id,
                'speed_kmh' => round($speed, 1),
                'max_kmh' => $maxSpeed,
                'from' => $last->location->toArray(),
                'to' => $point->toArray(),
                'seconds' => $seconds,
            ]);

            return AntiCheatResult::reject($speed, 'implausible_speed');
        }

        return AntiCheatResult::allow($speed);
    }

    /**
     * Persist a location sample to history and update the user's last_location.
     */
    public function record(User $user, GeoPoint $point, CarbonInterface $at): void
    {
        UserLocationHistory::create([
            'user_id' => $user->id,
            'location' => $point,
            'recorded_at' => $at,
        ]);

        $user->forceFill([
            'last_location' => $point,
            'last_location_at' => $at,
        ])->save();
    }
}
