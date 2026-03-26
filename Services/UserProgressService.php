<?php

namespace Modules\OverflowAchievement\Services;

use Modules\OverflowAchievement\Entities\UserStat;
use Modules\OverflowAchievement\Support\TriggerCatalog;

class UserProgressService
{
    protected LevelService $levels;

    public function __construct(LevelService $levels)
    {
        $this->levels = $levels;
    }

    public function statForUser(int $userId, bool $create = false): UserStat
    {
        if ($create) {
            return UserStat::query()->firstOrCreate(['user_id' => $userId], $this->defaultAttributes($userId));
        }

        $stat = UserStat::query()->where('user_id', $userId)->first();
        return $stat ?: $this->makeDefaultStat($userId);
    }

    public function makeDefaultStat(int $userId = 0): UserStat
    {
        return new UserStat($this->defaultAttributes($userId));
    }

    public function syncDisplayedLevel(UserStat $stat, bool $persist = false): UserStat
    {
        return $this->levels->syncStatLevel($stat, $persist && $stat->exists);
    }

    public function snapshot(UserStat $stat): array
    {
        $stat = $this->syncDisplayedLevel($stat, false);
        $curMin = $this->levels->levelMinXp((int) $stat->level);
        $nextMin = $this->levels->nextLevelMinXp((int) $stat->level);
        $den = max(1, $nextMin - $curMin);
        $progress = (int) round(((max(0, (int) $stat->xp_total) - $curMin) / $den) * 100);
        $progress = max(0, min(100, $progress));

        return [
            'level' => (int) $stat->level,
            'xp_total' => (int) $stat->xp_total,
            'cur_min' => (int) $curMin,
            'next_min' => (int) $nextMin,
            'progress' => (int) $progress,
        ];
    }

    public function countsFromStat(?UserStat $stat): array
    {
        $stat = $stat ?: $this->makeDefaultStat();

        $counts = [];
        $triggerField = [
            'close_conversation' => 'closes_count',
            'first_reply' => 'first_replies_count',
            'note_added' => 'notes_count',
            'assigned' => 'assigned_count',
            'merged' => 'merged_count',
            'moved' => 'moved_count',
            'forwarded' => 'forwarded_count',
            'attachment_added' => 'attachments_count',
            'customer_created' => 'customers_created_count',
            'customer_updated' => 'customer_updates_count',
            'conversation_created' => 'conversations_created_count',
            'subject_changed' => 'subjects_changed_count',
            'reply_sent' => 'replies_sent_count',
            'customer_replied' => 'customer_replies_count',
            'set_pending' => 'pending_set_count',
            'marked_spam' => 'spam_marked_count',
            'deleted_conversation' => 'deleted_count',
            'customer_merged' => 'customers_merged_count',
            'focus_time' => 'focus_minutes',
            'sla_first_response_ultra' => 'sla_first_response_ultra_count',
            'sla_first_response_fast' => 'sla_first_response_fast_count',
            'sla_fast_reply_ultra' => 'sla_fast_reply_ultra_count',
            'sla_fast_reply' => 'sla_fast_reply_count',
            'sla_resolve_4h' => 'sla_resolve_4h_count',
            'sla_resolve_24h' => 'sla_resolve_24h_count',
            'streak_days' => 'streak_current',
            'xp_total' => 'xp_total',
            'actions_total' => 'actions_count',
        ];

        foreach ($triggerField as $trigger => $field) {
            $counts[$trigger] = (int) ($stat->{$field} ?? 0);
        }

        foreach (TriggerCatalog::aliases() as $alias => $canonicalTrigger) {
            if (array_key_exists($canonicalTrigger, $counts) && !array_key_exists($alias, $counts)) {
                $counts[$alias] = (int) $counts[$canonicalTrigger];
            }
        }

        return $counts;
    }

    protected function defaultAttributes(int $userId = 0): array
    {
        return [
            'user_id' => $userId,
            'xp_total' => 0,
            'daily_xp' => 0,
            'level' => 1,
            'closes_count' => 0,
            'first_replies_count' => 0,
            'notes_count' => 0,
            'assigned_count' => 0,
            'merged_count' => 0,
            'moved_count' => 0,
            'forwarded_count' => 0,
            'attachments_count' => 0,
            'customers_created_count' => 0,
            'customer_updates_count' => 0,
            'conversations_created_count' => 0,
            'subjects_changed_count' => 0,
            'replies_sent_count' => 0,
            'customer_replies_count' => 0,
            'pending_set_count' => 0,
            'spam_marked_count' => 0,
            'deleted_count' => 0,
            'customers_merged_count' => 0,
            'focus_minutes' => 0,
            'sla_first_response_ultra_count' => 0,
            'sla_first_response_fast_count' => 0,
            'sla_fast_reply_ultra_count' => 0,
            'sla_fast_reply_count' => 0,
            'sla_resolve_4h_count' => 0,
            'sla_resolve_24h_count' => 0,
            'actions_count' => 0,
            'streak_current' => 0,
            'streak_best' => 0,
        ];
    }
}
