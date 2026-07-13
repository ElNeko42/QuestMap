<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CompletionStatus;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\QuestCompletion;
use Illuminate\Support\Facades\DB;

class XpService
{
    /**
     * Level curve: level = floor(sqrt(xp / 100)) + 1.
     * (0-99 XP -> L1, 100-399 -> L2, 400-899 -> L3, ...)
     */
    public function levelForXp(int $xp): int
    {
        return (int) floor(sqrt(max(0, $xp) / 100)) + 1;
    }

    /**
     * Approve a completion: award the quest's XP to the user, recompute their
     * level, increment the quest's completion count and exhaust it if the cap is
     * reached. Runs in a transaction with a pessimistic lock on the quest so
     * concurrent approvals can't over-count or double-exhaust.
     *
     * Idempotent: if the completion is already approved, it is a no-op and the
     * previously awarded XP is returned.
     *
     * @return int XP awarded by this call (0 if it was already approved).
     */
    public function awardForApproval(QuestCompletion $completion): int
    {
        return DB::transaction(function () use ($completion) {
            /** @var Quest $quest */
            $quest = Quest::query()
                ->lockForUpdate()
                ->findOrFail($completion->quest_id);

            // Re-read the completion inside the lock to avoid a lost update.
            $completion->refresh();

            if ($completion->status === CompletionStatus::Approved) {
                return 0;
            }

            $xp = $quest->xp_reward;

            $user = $completion->user()->lockForUpdate()->firstOrFail();
            $user->xp += $xp;
            $user->level = $this->levelForXp((int) $user->xp);
            $user->save();

            $completion->status = CompletionStatus::Approved;
            $completion->xp_awarded = $xp;
            $completion->save();

            $quest->completions_count += 1;
            if ($quest->max_completions !== null && $quest->completions_count >= $quest->max_completions) {
                $quest->status = QuestStatus::Exhausted;
            }
            $quest->save();

            return $xp;
        });
    }
}
