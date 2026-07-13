<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\QuestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\QuestBatchRequest;
use App\Models\Quest;
use App\Models\Tenant;
use App\Support\GeoPoint;
use Illuminate\Http\JsonResponse;

class QuestBatchController extends Controller
{
    /**
     * Batch-create quests with deduplication by dedup_hash. A quest whose
     * dedup_hash already exists is skipped (not duplicated). If no dedup_hash is
     * supplied, one is derived from tenant + title + rounded coordinates.
     */
    public function store(QuestBatchRequest $request): JsonResponse
    {
        $tenant = $this->resolveTenant($request);

        $created = [];
        $skipped = [];

        foreach ($request->validated('quests') as $data) {
            $hash = $data['dedup_hash'] ?? $this->deriveHash($tenant->id, $data);

            if (Quest::withTrashed()->where('dedup_hash', $hash)->exists()) {
                $skipped[] = $hash;

                continue;
            }

            $quest = Quest::create([
                'tenant_id' => $tenant->id,
                'creator_type' => $data['creator_type'] ?? 'ai',
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'location' => new GeoPoint((float) $data['lat'], (float) $data['lng']),
                'geofence_radius_m' => $data['geofence_radius_m'] ?? config('questmap.default_geofence_radius_m'),
                'validation_type' => $data['validation_type'],
                'validation_prompt' => $data['validation_prompt'] ?? null,
                'xp_reward' => $data['xp_reward'],
                'starts_at' => $data['starts_at'] ?? now(),
                'expires_at' => $data['expires_at'] ?? null,
                'max_completions' => $data['max_completions'] ?? null,
                'dedup_hash' => $hash,
                'status' => QuestStatus::Active,
            ]);

            $created[] = $quest->id;
        }

        return response()->json([
            'created_count' => count($created),
            'skipped_count' => count($skipped),
            'created_ids' => $created,
        ], 201);
    }

    private function resolveTenant(QuestBatchRequest $request): Tenant
    {
        if ($request->filled('tenant_id')) {
            return Tenant::findOrFail($request->validated('tenant_id'));
        }

        return Tenant::where('slug', $request->validated('tenant_slug'))->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function deriveHash(int $tenantId, array $data): string
    {
        return hash('sha256', implode('|', [
            $tenantId,
            mb_strtolower(trim($data['title'])),
            round((float) $data['lat'], 5),
            round((float) $data['lng'], 5),
        ]));
    }
}
