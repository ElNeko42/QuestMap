<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompletionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Top users of a tenant ranked by XP earned from approved completions in the
     * given period. Cached for a few minutes. Result rows: rank, user_id, name,
     * level, period_xp.
     *
     * @param  'week'|'month'|'all'  $period
     * @return Collection<int, array<string, mixed>>
     */
    public function top(int $tenantId, string $period): Collection
    {
        $size = (int) config('questmap.leaderboard.size');
        $ttl = (int) config('questmap.leaderboard.cache_ttl_seconds');

        $key = "leaderboard:{$tenantId}:{$period}";

        return Cache::remember($key, $ttl, function () use ($tenantId, $period, $size) {
            $since = $this->periodStart($period);

            $rows = DB::table('quest_completions as qc')
                ->join('users as u', 'u.id', '=', 'qc.user_id')
                ->where('u.tenant_id', $tenantId)
                ->where('qc.status', CompletionStatus::Approved->value)
                ->when($since !== null, fn ($q) => $q->where('qc.updated_at', '>=', $since))
                ->groupBy('u.id', 'u.name', 'u.level')
                ->select('u.id as user_id', 'u.name', 'u.level')
                ->selectRaw('SUM(qc.xp_awarded) as period_xp')
                ->orderByDesc('period_xp')
                ->orderBy('u.id')
                ->limit($size)
                ->get();

            return $rows->values()->map(fn ($row, $i) => [
                'rank' => $i + 1,
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'level' => (int) $row->level,
                'period_xp' => (int) $row->period_xp,
            ]);
        });
    }

    private function periodStart(string $period): ?CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'week' => $now->startOfWeek(),
            'month' => $now->startOfMonth(),
            default => null, // 'all'
        };
    }
}
