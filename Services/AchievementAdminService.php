<?php

namespace Modules\OverflowAchievement\Services;

use Modules\OverflowAchievement\Entities\Achievement;
use Modules\OverflowAchievement\Entities\UserStat;
use Modules\OverflowAchievement\Support\QuoteCatalog;

class AchievementAdminService
{
    public function manageTabViewData(): array
    {
        $quoteLibrary = (array) QuoteCatalog::get('library', []);
        $quoteBuckets = (array) QuoteCatalog::get('buckets', []);

        $mailboxes = [];
        try {
            if (class_exists('\\App\\Mailbox')) {
                $mailboxes = \App\Mailbox::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->toArray();
            }
        } catch (\Throwable $e) {
            $mailboxes = [];
        }

        return [
            'achievements' => Achievement::query()
                ->orderBy('trigger')
                ->orderBy('threshold')
                ->get(),
            'quote_library' => $quoteLibrary,
            'quote_buckets' => $quoteBuckets,
            'mailboxes' => $mailboxes,
        ];
    }

    public function buildLevelRepairSummary(array $userIds, bool $repair = false, bool $invalidOnly = true): array
    {
        $levelService = new LevelService();
        $statsQuery = UserStat::query()
            ->whereIn('user_id', $userIds)
            ->orderBy('user_id');
        if ($repair) {
            $statsQuery->lockForUpdate();
        }
        $stats = $statsQuery->get(['user_id', 'xp_total', 'level']);

        $summary = [
            'selected_users' => count($userIds),
            'rows_found' => 0,
            'invalid_rows' => 0,
            'updated_rows' => 0,
            'examples' => [],
        ];

        foreach ($stats as $stat) {
            $summary['rows_found']++;

            $xpTotal = (int) $stat->xp_total;
            $storedLevel = max(1, (int) $stat->level);
            $expectedLevel = $levelService->levelForXp($xpTotal);
            $isInvalid = $storedLevel !== $expectedLevel;

            if ($isInvalid) {
                $summary['invalid_rows']++;
                if (count($summary['examples']) < 10) {
                    $summary['examples'][] = [
                        'user_id' => (int) $stat->user_id,
                        'xp_total' => $xpTotal,
                        'stored_level' => $storedLevel,
                        'expected_level' => $expectedLevel,
                    ];
                }
            }

            if (!$repair || ($invalidOnly && !$isInvalid)) {
                continue;
            }

            if ($storedLevel !== $expectedLevel) {
                $stat->level = $expectedLevel;
                $stat->save();
                $summary['updated_rows']++;
            }
        }

        return $summary;
    }
}
