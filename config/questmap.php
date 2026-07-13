<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Geofence & location
    |--------------------------------------------------------------------------
    */

    // Default geofence radius (meters) applied to a quest when not specified.
    'default_geofence_radius_m' => 50,

    // Default search radius (meters) for the /quests/nearby endpoint.
    'default_nearby_radius_m' => 2000,

    // Hard cap for the nearby search radius to keep queries bounded.
    'max_nearby_radius_m' => 20000,

    /*
    |--------------------------------------------------------------------------
    | Anti-cheat
    |--------------------------------------------------------------------------
    */

    // Reject a location/action if the implied speed vs. the previous point
    // exceeds this threshold (km/h).
    'max_speed_kmh' => 150,

    // Ignore the speed check when the two samples are closer together in time
    // than this (seconds); GPS jitter over tiny intervals yields huge speeds.
    'min_speed_sample_seconds' => 5,

    /*
    |--------------------------------------------------------------------------
    | AI validation thresholds (photo_ai)
    |--------------------------------------------------------------------------
    */

    'confidence' => [
        // >= approve  -> approved + award XP
        'approve' => 0.85,
        // >= review   -> manual_review (between review and approve)
        'review' => 0.50,
        // < review    -> rejected
    ],

    // Lifetime (minutes) of the temporary signed photo URL handed to n8n.
    'photo_url_ttl_minutes' => 30,

    /*
    |--------------------------------------------------------------------------
    | Replay protection for internal HMAC routes
    |--------------------------------------------------------------------------
    */

    // Max allowed clock skew (seconds) for the X-Timestamp header.
    'hmac_max_skew_seconds' => 300,

    /*
    |--------------------------------------------------------------------------
    | Rate limits
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        // Max photo submissions per user per day.
        'submits_per_day' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Leaderboard
    |--------------------------------------------------------------------------
    */

    'leaderboard' => [
        'size' => 50,
        'cache_ttl_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | External integrations (n8n)
    |--------------------------------------------------------------------------
    */

    'n8n' => [
        'validation_webhook_url' => env('N8N_VALIDATION_WEBHOOK_URL'),
        'webhook_secret' => env('N8N_WEBHOOK_SECRET'),
    ],

    // Secret shared with n8n for inbound internal routes (HMAC middleware).
    'internal_api_secret' => env('INTERNAL_API_SECRET'),

    // Public base URL used to build callback + signed photo URLs for n8n.
    'public_url' => env('QUESTMAP_PUBLIC_URL', env('APP_URL')),

];
