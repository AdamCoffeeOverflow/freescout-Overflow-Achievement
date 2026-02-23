<?php

namespace Modules\OverflowAchievement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\OverflowAchievement\Entities\Achievement;
use Modules\OverflowAchievement\Entities\UnlockedAchievement;
use Modules\OverflowAchievement\Entities\UserStat;
use Modules\OverflowAchievement\Services\QuoteService;

class OverflowAchievementController extends Controller
{
    public function my(Request $request)
    {
        if (!Schema::hasTable('overflowachievement_user_stats')) {
            return view('overflowachievement::install_needed');
        }

        $user = $request->user();

        $stat = UserStat::query()->firstOrCreate(['user_id' => $user->id], [
            'xp_total' => 0,
            'level' => 1,
            'closes_count' => 0,
            'first_replies_count' => 0,
            'streak_current' => 0,
            'streak_best' => 0,
        ]);

        $levels = app('overflowachievement.levels');
        $cur_min = $levels->levelMinXp((int)$stat->level);
        $next_min = $levels->nextLevelMinXp((int)$stat->level);

        $recent = UnlockedAchievement::query()
            ->where('user_id', $user->id)
            ->orderByDesc('unlocked_at')
            ->limit(12)
            ->get();

        $recent_keys = $recent->pluck('achievement_key')->toArray();
        $defs = Achievement::query()->whereIn('key', $recent_keys)->get()->keyBy('key');

        return view('overflowachievement::my', [
            'stat' => $stat,
            'cur_min' => $cur_min,
            'next_min' => $next_min,
            'defs' => $defs,
            'recent' => $recent,
        ]);
    }

    public function achievements(Request $request)
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return view('overflowachievement::install_needed');
        }

        $user = $request->user();

        // Stats are used to show per-achievement progress (modal + cabinet UI).
        $stat = null;
        $counts = [];
        if (Schema::hasTable('overflowachievement_user_stats')) {
            $stat = UserStat::query()->firstOrCreate(['user_id' => $user->id], [
                'xp_total' => 0,
                'level' => 1,
            ]);

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
                'sla_first_response_fast'  => 'sla_first_response_fast_count',
                'sla_fast_reply_ultra'     => 'sla_fast_reply_ultra_count',
                'sla_fast_reply'           => 'sla_fast_reply_count',
                'sla_resolve_4h'           => 'sla_resolve_4h_count',
                'sla_resolve_24h'          => 'sla_resolve_24h_count',
                'streak_days' => 'streak_current',
                'xp_total' => 'xp_total',
                'actions_total' => 'actions_count',
            ];

            foreach ($triggerField as $trigger => $field) {
                $counts[$trigger] = (int)($stat->{$field} ?? 0);
            }
        }

        $defs = Achievement::query()
            ->where('is_active', true)
            ->orderByRaw("case rarity when 'legendary' then 4 when 'epic' then 3 when 'rare' then 2 else 1 end desc")
            ->orderBy('trigger')
            ->orderBy('threshold')
            ->get();

        $unlocked = UnlockedAchievement::query()
            ->where('user_id', $user->id)
            ->whereIn('achievement_key', $defs->pluck('key')->toArray())
            ->get()
            ->keyBy('achievement_key');

        // Pre-resolve quotes per achievement so the cabinet can show them for unlocked trophies.
        // This keeps Blade simple and ensures we don't show quotes for locked items.
        $quotes_by_key = [];
        try {
            /** @var QuoteService $qs */
            $qs = app(QuoteService::class);
            foreach ($defs as $def) {
                $quotes_by_key[$def->key] = $qs->forAchievement($def);
            }
        } catch (\Throwable $e) {
            $quotes_by_key = [];
        }

        return view('overflowachievement::achievements', [
            'defs' => $defs,
            'unlocked' => $unlocked,
            'counts' => $counts,
            'quotes_by_key' => $quotes_by_key,
            'trigger_labels' => (array)config('overflowachievement.triggers.labels', []),
            'trigger_hints'  => (array)config('overflowachievement.triggers.hints', []),
        ]);
    }

    public function leaderboard(Request $request)
    {
        if (!\Option::get('overflowachievement.show_leaderboard', 1)) {
            abort(404);
        }

        if (!Schema::hasTable('overflowachievement_user_stats') || !Schema::hasTable('overflowachievement_achievements')) {
            return view('overflowachievement::install_needed');
        }

        $top = UserStat::query()
            ->orderByDesc('level')
            ->orderByDesc('xp_total')
            ->limit(50)
            ->get();

        // Keep this list short so the page stays snappy and the UI doesn't look "infinite".
        // (If you ever want a full history, implement pagination or a dedicated "All trophies" page.)
        $recent_unlocks_limit = (int)config('overflowachievement.ui.recent_trophies_limit', 5);
        $recent_unlocks_limit = max(1, min(50, $recent_unlocks_limit));

        $recent_unlocks = UnlockedAchievement::query()
            ->orderByDesc('unlocked_at')
            ->limit($recent_unlocks_limit)
            ->get();

        // Load only the users we actually display (performance on large user tables).
        $userIds = $top->pluck('user_id')
            ->merge($recent_unlocks->pluck('user_id'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Do NOT limit this to *active* users, otherwise the leaderboard can show #ID for inactive/disabled users.
        // However, we DO want to exclude pseudo-users created by modules (e.g. Teams) that are stored in `users`
        // but should not appear as agents.
        //
        // Teams module marks team records as:
        // - status = STATUS_DELETED (to hide them from normal user lists)
        // - type   = TYPE_ROBOT
        //
        // So here we include disabled users, but exclude deleted + robot records.
        $usersQuery = \App\User::query()
            ->whereIn('id', $userIds)
            ->where('status', '!=', \App\User::STATUS_DELETED);

        // `type` column/constant may not exist in very old FreeScout installs, so guard it.
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'type') && defined(\App\User::class.'::TYPE_ROBOT')) {
                $usersQuery->where('type', '!=', \App\User::TYPE_ROBOT);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $users = $usersQuery->get()->keyBy('id');

        // Drop any leaderboard rows that don't map to a real agent user.
        // (Prevents showing team pseudo-users or any orphaned stats.)
        $allowedIds = $users->keys()->all();
        $top = $top->whereIn('user_id', $allowedIds)->values();
        $recent_unlocks = $recent_unlocks->whereIn('user_id', $allowedIds)->values();

        $def_keys = $recent_unlocks->pluck('achievement_key')->filter(function ($k) {
            return !str_starts_with((string)$k, 'level_up_');
        })->unique()->values()->toArray();

        $defs = Achievement::query()->whereIn('key', $def_keys)->get()->keyBy('key');

        return view('overflowachievement::leaderboard', [
            'top' => $top,
            'users' => $users,
            'recent_unlocks' => $recent_unlocks,
            'defs' => $defs,
        ]);
    }

    public function unseen(Request $request)
    {
        if (!\Option::get('overflowachievement.enabled', config('overflowachievement.enabled') ? 1 : 0)) {
            return response()->json([
                'ok' => true,
                'enabled' => false,
                'items' => [],
            ]);
        }

        if (!Schema::hasTable('overflowachievement_user_stats') || !Schema::hasTable('overflowachievement_unlocked')) {
            return response()->json([
                'ok' => false,
                'message' => 'OverflowAchievement is not installed (missing DB tables). Run database migrations.',
            ]);
        }

        $user = $request->user();

        $stat = UserStat::query()->firstOrCreate(['user_id' => $user->id], [
            'xp_total' => 0,
            'level' => 1,
        ]);

        $levels = app('overflowachievement.levels');
        $cur_min = $levels->levelMinXp((int)$stat->level);
        $next_min = $levels->nextLevelMinXp((int)$stat->level);
        $den = max(1, $next_min - $cur_min);
        $progress = (int)round((($stat->xp_total - $cur_min) / $den) * 100);
        $progress = max(0, min(100, $progress));

        // Optional: return a single "batch" toast payload to reduce network chatter and
        // client-side work during unlock bursts.
        // Client JS falls back to the legacy "items" response if this mode isn't used.
        // FreeScout may run on a Laravel version where Request::boolean() is not available.
        // Use a backwards-compatible boolean parser.
        $batch_mode = filter_var($request->input('batch'), FILTER_VALIDATE_BOOLEAN)
            || (string)$request->input('mode') === 'batch';

        // Fetch unseen unlocks. If the definitions table exists, join it to avoid a second query.
        // This endpoint is polled, so keeping query count low matters.
        if (Schema::hasTable('overflowachievement_achievements')) {
            $items = UnlockedAchievement::query()
                ->leftJoin('overflowachievement_achievements as a', 'a.key', '=', 'overflowachievement_unlocked.achievement_key')
                ->where('overflowachievement_unlocked.user_id', $user->id)
                ->whereNull('overflowachievement_unlocked.seen_at')
                ->where('overflowachievement_unlocked.achievement_key', 'not like', 'level_up_%')
                ->orderBy('overflowachievement_unlocked.unlocked_at')
                ->limit(10)
                ->get([
                    'overflowachievement_unlocked.*',
                    'a.title as def_title',
                    'a.rarity as def_rarity',
                    'a.icon_type as def_icon_type',
                    'a.icon_value as def_icon_value',
                    'a.xp_reward as def_xp_reward',
                ]);
        } else {
            $items = UnlockedAchievement::query()
                ->where('user_id', $user->id)
                ->whereNull('seen_at')
                // Older versions created synthetic level_up_* rows. We no longer do that,
                // but we still exclude them here so legacy data doesn't pollute the queue.
                ->where('achievement_key', 'not like', 'level_up_%')
                ->orderBy('unlocked_at')
                ->limit(10)
                ->get();
        }

        // Calculate per-item XP/level snapshots so multiple queued toasts feel like real progression.
        // We can infer the "before" xp_total by subtracting the sum of xp_reward for unseen achievements
        // from the current xp_total. Then we replay rewards in unlock order.
        $rewardByRowId = [];
        $totalReward = 0;

        foreach ($items as $row) {
            $xp = 0;
            if (isset($row->def_xp_reward)) {
                $xp = (int)$row->def_xp_reward;
            }
            $rewardByRowId[$row->id] = $xp;
            $totalReward += $xp;
        }

        // "Before" snapshot for smooth client-side XP bar animation.
        // This matters when the page was reloaded and the client has no previous stat.
        $before_xp = max(0, (int)$stat->xp_total - (int)$totalReward);
        $before_lvl = $levels->levelForXp((int)$before_xp);
        $before_cur_min = $levels->levelMinXp((int)$before_lvl);
        $before_next_min = $levels->nextLevelMinXp((int)$before_lvl);
        $before_den = max(1, $before_next_min - $before_cur_min);
        $before_progress = (int)round((($before_xp - $before_cur_min) / $before_den) * 100);
        $before_progress = max(0, min(100, $before_progress));
        $prev_stat = [
            'level' => (int)$before_lvl,
            'xp_total' => (int)$before_xp,
            'cur_min' => (int)$before_cur_min,
            'next_min' => (int)$before_next_min,
            'progress' => (int)$before_progress,
        ];

        $runningXp = max(0, (int)$stat->xp_total - (int)$totalReward);

        $payload = $items->map(function ($row) use ($levels, &$runningXp, $rewardByRowId) {
            $key = (string)$row->achievement_key;
            $is_level_up = false;

            // Prefer joined definition columns when present.
            $title = isset($row->def_title) ? (string)$row->def_title : $key;
            $rarity = isset($row->def_rarity) ? (string)$row->def_rarity : 'common';
            $icon_type = isset($row->def_icon_type) ? (string)$row->def_icon_type : 'fa';
            $icon_value = isset($row->def_icon_value) ? (string)$row->def_icon_value : 'fa-trophy';

            $xp_reward = (int)($rewardByRowId[$row->id] ?? 0);
            // Apply reward before computing snapshot, so the toast shows the updated total.
            $runningXp += $xp_reward;

            $lvl = $levels->levelForXp((int)$runningXp);
            $cur_min_i = $levels->levelMinXp((int)$lvl);
            $next_min_i = $levels->nextLevelMinXp((int)$lvl);
            $den_i = max(1, $next_min_i - $cur_min_i);
            $progress_i = (int)round((($runningXp - $cur_min_i) / $den_i) * 100);
            $progress_i = max(0, min(100, $progress_i));

            return [
                'id' => $row->id,
                'achievement_key' => $key,
                'title' => $title,
                'rarity' => $rarity,
                'icon_type' => $icon_type,
                'icon_value' => $icon_value,
                'xp_reward' => $xp_reward,
                'quote_text' => (string)($row->quote_text ?? ''),
                'quote_author' => (string)($row->quote_author ?? ''),
                // Compatibility: older Laravel versions used by FreeScout may not have optional().
                // unlocked_at is usually a Carbon instance, but can be a string in edge cases.
                'unlocked_at' => (function () use ($row) {
                    if (empty($row->unlocked_at)) {
                        return null;
                    }
                    $ua = $row->unlocked_at;
                    if (is_object($ua) && method_exists($ua, 'toIso8601String')) {
                        return $ua->toIso8601String();
                    }
                    return (string)$ua;
                })(),
                'is_level_up' => $is_level_up,
                // Snapshot for animating the XP bar / totals across multiple achievements
                'stat' => [
                    'level' => (int)$lvl,
                    'xp_total' => (int)$runningXp,
                    'cur_min' => (int)$cur_min_i,
                    'next_min' => (int)$next_min_i,
                    'progress' => (int)$progress_i,
                ],
            ];
        });

        // If batch mode is requested, collapse into a single item while preserving the IDs
        // so the client can mark all rows as seen after dismissal.
        if ($batch_mode) {
            $ids = $items->pluck('id')->map(function ($v) { return (int)$v; })->values()->all();

            if (count($payload) === 1) {
                return response()->json([
                    'ok' => true,
                    'enabled' => true,
                    'ui' => [
                        'confetti' => (bool)\Option::get('overflowachievement.ui.confetti', 1),
                        'effect' => (string)\Option::get('overflowachievement.ui.effect', 'confetti'),
                        'toast_theme' => (string)\Option::get('overflowachievement.ui.toast_theme', 'neon'),
                        'sound_enabled' => (bool)\Option::get('overflowachievement.ui.sound_enabled', config('overflowachievement.ui.sound_enabled') ? 1 : 0),
                        'toast_sticky' => (bool)\Option::get('overflowachievement.ui.toast_sticky', 0),
                        'toast_duration_ms' => (int)\Option::get('overflowachievement.ui.toast_duration_ms', 10000),
                    ],
                    'item' => $payload->first(),
                    'ids' => $ids,
                    'items' => [],
                    'prev_stat' => $prev_stat,
                    'stat' => [
                        'level' => (int)$stat->level,
                        'xp_total' => (int)$stat->xp_total,
                        'cur_min' => (int)$cur_min,
                        'next_min' => (int)$next_min,
                        'progress' => (int)$progress,
                    ],
                ]);
            }

            if (count($payload) > 1) {
                $rank = ['common' => 1, 'rare' => 2, 'epic' => 3, 'legendary' => 4];
                $best = 'common';
                foreach ($payload as $p) {
                    $r = (string)($p['rarity'] ?? 'common');
                    if (($rank[$r] ?? 1) > ($rank[$best] ?? 1)) {
                        $best = $r;
                    }
                }

                return response()->json([
                    'ok' => true,
                    'enabled' => true,
                    'ui' => [
                        'confetti' => (bool)\Option::get('overflowachievement.ui.confetti', 1),
                        'effect' => (string)\Option::get('overflowachievement.ui.effect', 'confetti'),
                        'toast_theme' => (string)\Option::get('overflowachievement.ui.toast_theme', 'neon'),
                        'sound_enabled' => (bool)\Option::get('overflowachievement.ui.sound_enabled', config('overflowachievement.ui.sound_enabled') ? 1 : 0),
                        'toast_sticky' => (bool)\Option::get('overflowachievement.ui.toast_sticky', 0),
                        'toast_duration_ms' => (int)\Option::get('overflowachievement.ui.toast_duration_ms', 10000),
                    ],
                    'item' => [
                        'is_batch' => true,
                        'rarity' => $best,
                        'title' => __('+:count achievements', ['count' => count($payload)]),
                        'batch_items' => $payload,
                    ],
                    'ids' => $ids,
                    'items' => [],
                    'prev_stat' => $prev_stat,
                    'stat' => [
                        'level' => (int)$stat->level,
                        'xp_total' => (int)$stat->xp_total,
                        'cur_min' => (int)$cur_min,
                        'next_min' => (int)$next_min,
                        'progress' => (int)$progress,
                    ],
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'enabled' => true,
            'ui' => [
                // Backward compatible flag + richer UI config
                'confetti' => (bool)\Option::get('overflowachievement.ui.confetti', 1),
                'effect' => (string)\Option::get('overflowachievement.ui.effect', 'confetti'),
                'toast_theme' => (string)\Option::get('overflowachievement.ui.toast_theme', 'neon'),
                'sound_enabled' => (bool)\Option::get('overflowachievement.ui.sound_enabled', config('overflowachievement.ui.sound_enabled') ? 1 : 0),
                // Toast behavior
                'toast_sticky' => (bool)\Option::get('overflowachievement.ui.toast_sticky', 0),
                'toast_duration_ms' => (int)\Option::get('overflowachievement.ui.toast_duration_ms', 10000),
            ],
            'items' => $payload,
            'prev_stat' => $prev_stat,
            'stat' => [
                'level' => (int)$stat->level,
                'xp_total' => (int)$stat->xp_total,
                'cur_min' => (int)$cur_min,
                'next_min' => (int)$next_min,
                'progress' => (int)$progress,
            ],
        ]);
    }

    public function health(Request $request)
    {
        if (!\Option::get('overflowachievement.enabled', config('overflowachievement.enabled') ? 1 : 0)) {
            return response()->json(['ok' => false, 'reason' => 'disabled']);
        }
        // Used by Settings diagnostic. Keep it lightweight and JSON-only.
        if (!Schema::hasTable('overflowachievement_unlocked') || !Schema::hasTable('overflowachievement_user_stats')) {
            return response()->json(['ok' => false, 'reason' => 'missing_tables']);
        }

        return response()->json(['ok' => true, 'user_id' => (int)$request->user()->id]);
    }

    public function markSeen(Request $request)
    {
        if (!Schema::hasTable('overflowachievement_unlocked')) {
            return response()->json(['ok' => false]);
        }

        $user = $request->user();
        $ids = (array)$request->input('ids', []);
        $ids = array_values(array_unique(array_filter(array_map(function ($v) {
            $n = is_numeric($v) ? (int)$v : 0;
            return $n > 0 ? $n : null;
        }, $ids))));

        // Hard limit to avoid accidental large updates.
        if (count($ids) > 50) {
            $ids = array_slice($ids, 0, 50);
        }

        if (!empty($ids)) {
            UnlockedAchievement::query()
                ->where('user_id', $user->id)
                ->whereIn('id', $ids)
                ->update(['seen_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
