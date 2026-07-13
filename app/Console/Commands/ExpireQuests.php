<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\QuestStatus;
use App\Models\Quest;
use Illuminate\Console\Command;

class ExpireQuests extends Command
{
    protected $signature = 'quests:expire';

    protected $description = 'Mark active quests past their expires_at as expired';

    public function handle(): int
    {
        $count = Quest::query()
            ->where('status', QuestStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => QuestStatus::Expired->value]);

        $this->info("Expired {$count} quest(s).");

        return self::SUCCESS;
    }
}
