<?php

namespace Modules\OverflowAchievement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\OverflowAchievement\Entities\Achievement;
use Modules\OverflowAchievement\Entities\Event;
use Modules\OverflowAchievement\Entities\UnlockedAchievement;
use Modules\OverflowAchievement\Entities\UserStat;
use Modules\OverflowAchievement\Services\QuoteService;

class AchievementAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            abort(403);
        }
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->input('achievement', []);

        $key = (string)($data['key'] ?? '');
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_\-]/', '_', $key);

        if (!$key) {
            // Generate from title
            $key = Str::slug((string)($data['title'] ?? 'achievement'), '_');
        }

        $title = trim((string)($data['title'] ?? ''));
        if (!$title) {
            return redirect()->back()->with('error', __('Title is required.'));
        }

        $trigger = trim((string)($data['trigger'] ?? 'xp_total'));
        $threshold = max(1, (int)($data['threshold'] ?? 1));

        $rarity = in_array(($data['rarity'] ?? 'common'), ['common','rare','epic','legendary']) ? $data['rarity'] : 'common';

        // Quote (optional). If not provided, auto-assign a unique quote id from the library.
        $quote_id = trim((string)($data['quote_id'] ?? ''));
        $quote_text = trim((string)($data['quote_text'] ?? ''));
        $quote_author = trim((string)($data['quote_author'] ?? ''));
        $quote_tone = trim((string)($data['quote_tone'] ?? ''));

        // Normalize tone.
        if (!in_array($quote_tone, ['funny','epic','philosophical'], true)) {
            $quote_tone = '';
        }

        $mailbox_id = isset($data['mailbox_id']) ? (int)$data['mailbox_id'] : 0;
        $mailbox_id = $mailbox_id > 0 ? $mailbox_id : null;

        if ($quote_text === '' && $quote_id === '') {
            $quote_id = $this->nextAvailableQuoteId(null, $rarity, $quote_tone, $mailbox_id);
        }

        try {
            $achievement = Achievement::create([
                'key' => $key,
                'title' => $title,
                'description' => trim((string)($data['description'] ?? '')),
                'trigger' => $trigger,
                'threshold' => $threshold,
                'xp_reward' => max(0, (int)($data['xp_reward'] ?? 0)),
                'rarity' => $rarity,
                'icon_type' => in_array(($data['icon_type'] ?? 'fa'), ['fa','img']) ? $data['icon_type'] : 'fa',
                'icon_value' => trim((string)($data['icon_value'] ?? 'fa-trophy')),
                'is_active' => !empty($data['is_active']),
                'created_by' => (int)$request->user()->id,
                'mailbox_id' => $mailbox_id,
                'quote_id' => $quote_id ?: null,
                'quote_text' => $quote_text ?: null,
                'quote_author' => $quote_author ?: null,
                'quote_tone' => $quote_tone ?: null,
            ]);
        } catch (\Throwable $e) {
            // Usually a duplicate key (unique constraint). Provide a human message.
            return redirect()->back()->with('error', __('Could not create achievement. The key may already exist.'));
        }

        // Icon upload (optional)
        if ($request->hasFile('icon_file') && $request->file('icon_file')->isValid()) {
            $this->handleIconUpload($request, $achievement);
        }

        return redirect()->back()->with('success', __('Achievement created.'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $achievement = Achievement::query()->findOrFail($id);
        $data = $request->input('achievement', []);

        $achievement->title = trim((string)($data['title'] ?? $achievement->title));
        $achievement->description = trim((string)($data['description'] ?? $achievement->description));
        $achievement->trigger = trim((string)($data['trigger'] ?? $achievement->trigger));
        $achievement->threshold = max(1, (int)($data['threshold'] ?? $achievement->threshold));
        $achievement->xp_reward = max(0, (int)($data['xp_reward'] ?? $achievement->xp_reward));
        $achievement->rarity = in_array(($data['rarity'] ?? $achievement->rarity), ['common','rare','epic','legendary']) ? $data['rarity'] : $achievement->rarity;
        $achievement->icon_type = in_array(($data['icon_type'] ?? $achievement->icon_type), ['fa','img']) ? $data['icon_type'] : $achievement->icon_type;
        $achievement->icon_value = trim((string)($data['icon_value'] ?? $achievement->icon_value));

        // Optional mailbox association (used for mailbox-aware quote vibe).
        if (array_key_exists('mailbox_id', $data)) {
            $mbid = (int)$data['mailbox_id'];
            $achievement->mailbox_id = $mbid > 0 ? $mbid : null;
        }

        // Quote (optional). Keep stable unless explicitly changed.
        if (array_key_exists('quote_id', $data) || array_key_exists('quote_text', $data) || array_key_exists('quote_author', $data) || array_key_exists('quote_tone', $data)) {
            $quote_id = trim((string)($data['quote_id'] ?? $achievement->quote_id));
            $quote_text = trim((string)($data['quote_text'] ?? $achievement->quote_text));
            $quote_author = trim((string)($data['quote_author'] ?? $achievement->quote_author));
            $quote_tone = trim((string)($data['quote_tone'] ?? $achievement->quote_tone));

            if (!in_array($quote_tone, ['funny','epic','philosophical'], true)) {
                $quote_tone = '';
            }

            // If user cleared everything, reassign a quote id.
            if ($quote_id === '' && $quote_text === '') {
                $quote_id = $this->nextAvailableQuoteId((int)$achievement->id, (string)$achievement->rarity, $quote_tone, $achievement->mailbox_id);
            }

            $achievement->quote_id = $quote_id ?: null;
            $achievement->quote_text = $quote_text ?: null;
            $achievement->quote_author = $quote_author ?: null;
            $achievement->quote_tone = $quote_tone ?: null;
        }
        $achievement->is_active = !empty($data['is_active']);
        $achievement->save();

        if ($request->hasFile('icon_file') && $request->file('icon_file')->isValid()) {
            $this->handleIconUpload($request, $achievement);
        }

        return redirect()->back()->with('success', __('Achievement updated.'));
    }

    /**
     * Pick the next available quote id from the library, avoiding ids already assigned to other achievements.
     *
     * @param int|null $ignore_achievement_id When updating, ignore the current record.
     */
    protected function nextAvailableQuoteId(int $ignore_achievement_id = null, string $rarity = 'common', string $tone = '', $mailbox_id = null): ?string
    {
        try {
            $query = Achievement::query()->whereNotNull('quote_id');
            if ($ignore_achievement_id) {
                $query->where('id', '!=', $ignore_achievement_id);
            }
            $used = $query->pluck('quote_id')->toArray();

            /** @var QuoteService $quotes */
            $quotes = app(QuoteService::class);
            return $quotes->pickIdForRarityTone($rarity, $tone, array_filter($used), $mailbox_id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureAdmin($request);

        $achievement = Achievement::query()->findOrFail($id);
        $achievement->delete();

        return redirect()->back()->with('success', __('Achievement deleted.'));
    }

    /**
     * Admin maintenance: re-assign ALL trophies to use the bundled icon pack.
     *
     * This is intentionally deterministic (same key => same icon) and idempotent.
     * Useful when icons were customized, broken URLs were stored, or after cache/build issues.
     */
    public function reassignIcons(Request $request)
    {
        $this->ensureAdmin($request);

        $confirm = (int)($request->input('reassign.confirm') ?? 0);
        if ($confirm !== 1) {
            return redirect()->back()->with('error', __('Please confirm icon re-assignment.'));
        }

        if (!Schema::hasTable('overflowachievement_achievements')) {
            return redirect()->back()->with('error', __('Achievement tables are missing. Run database migrations first.'));
        }

        // Count available bundled icons.
        $packPath = base_path('Modules/OverflowAchievement/Public/icons/pack');
        if (!is_dir($packPath)) {
            // Fallback for installations where Modules path is different.
            $packPath = __DIR__ . '/../../Public/icons/pack';
        }

        $files = glob(rtrim($packPath, '/').'/icon_*.png') ?: [];
        $count = count($files);
        if ($count <= 0) {
            return redirect()->back()->with('error', __('Icon pack folder is missing or empty.'));
        }

        $updated = 0;
        Achievement::query()->select(['id','key'])->orderBy('id')->chunk(200, function ($rows) use ($count, &$updated) {
            foreach ($rows as $a) {
                $key = (string)$a->key;
                // crc32 may return signed int in PHP; normalize to unsigned via sprintf.
                $hash = (int)sprintf('%u', crc32($key));
                $idx = ($hash % $count) + 1; // 1..$count
                $fn = sprintf('icon_%03d.png', $idx);

                Achievement::query()->where('id', $a->id)->update([
                    'icon_type' => 'img',
                    'icon_value' => $fn,
                ]);
                $updated++;
            }
        });

        // Clear module-level caches to force UI refresh.
        try {
            \Cache::forget('_overflowachievement.vars');
            \Cache::forget('_overflowachievement.settings');
        } catch (\Throwable $e) {
            // ignore
        }

        return redirect()->back()->with('success', __('Re-assigned icons for :count trophies.', ['count' => $updated]));
    }

    /**
     * Reset all achievement progress for a user or for all users.
     */
    public function reset(Request $request)
    {
        $this->ensureAdmin($request);

        if (!Schema::hasTable('overflowachievement_user_stats')) {
            return redirect()->back()->with('error', __('Achievement tables are missing. Run database migrations first.'));
        }

        $target = (string)($request->input('reset.user_id_custom') ?: ($request->input('reset.user_id') ?? 'me'));

        // Require explicit confirmation to avoid accidental resets.
        $confirm = strtoupper(trim((string)$request->input('reset.confirm', '')));
        if ($target === 'all') {
            if ($confirm !== 'RESET ALL') {
                return redirect()->back()->with('error', __('To reset ALL users, type RESET ALL in the confirmation field.'));
            }
        } else {
            if ($confirm !== 'RESET') {
                return redirect()->back()->with('error', __('To reset progress, type RESET in the confirmation field.'));
            }
        }
        $userIds = [];
        if ($target === 'all') {
            // Reset must include users who have not generated stats rows yet.
            if (class_exists('\\App\\User') && defined('\\App\\User::TYPE_USER')) {
                $userIds = \App\User::query()
                    ->where('type', \App\User::TYPE_USER)
                    ->pluck('id')
                    ->toArray();
            } else {
                // Fallback: be conservative.
                $userIds = UserStat::query()->pluck('user_id')->toArray();
            }
        } elseif (ctype_digit($target)) {
            $userIds = [(int)$target];
        } else {
            $userIds = [(int)$request->user()->id];
        }

        if (empty($userIds)) {
            return redirect()->back()->with('success', __('Nothing to reset.'));
        }

        DB::transaction(function () use ($userIds) {
            // Delete related rows first.
            if (Schema::hasTable('overflowachievement_unlocked')) {
                UnlockedAchievement::query()->whereIn('user_id', $userIds)->delete();
            }
            if (Schema::hasTable('overflowachievement_events')) {
                Event::query()->whereIn('user_id', $userIds)->delete();
            }

            // Reset stats (be defensive about columns across versions).
            // We reset all known counters and also any *_count columns that may be introduced later.
            $table = (new UserStat())->getTable();
            $columns = Schema::getColumnListing($table);
            $updates = [];

            foreach ($columns as $col) {
                if ($col === 'user_id' || $col === 'created_at') {
                    continue;
                }
                if ($col === 'updated_at') {
                    $updates[$col] = now();
                    continue;
                }
                if ($col === 'level') {
                    $updates[$col] = 1;
                    continue;
                }

                // Null out last activity fields.
                if (in_array($col, ['last_activity_at', 'last_activity_date'], true) || Str::startsWith($col, 'last_')) {
                    $updates[$col] = null;
                    continue;
                }

                // Reset counters & numeric trackers.
                if (in_array($col, ['daily_xp'], true)) {
                    $updates[$col] = 0;
                    continue;
                }
                if (in_array($col, ['daily_xp_date'], true)) {
                    $updates[$col] = null;
                    continue;
                }

                if (
                    in_array($col, ['xp_total', 'streak_current', 'streak_best'], true) ||
                    Str::endsWith($col, '_count') ||
                    Str::endsWith($col, '_total')
                ) {
                    $updates[$col] = 0;
                }
            }

            if (!empty($updates)) {
                UserStat::query()->whereIn('user_id', $userIds)->update($updates);
            }
        });

        return redirect()->back()->with('success', __('Achievement progress reset.'));
    }

    /**
     * Create a fake unseen unlock for visual testing.
     */
    public function testToast(Request $request)
    {
        $this->ensureAdmin($request);

        if (!Schema::hasTable('overflowachievement_unlocked') || !Schema::hasTable('overflowachievement_user_stats')) {
            return redirect()->back()->with('error', __('Achievement tables are missing. Run database migrations first.'));
        }

        $target = (string)($request->input('test.user_id_custom') ?: ($request->input('test.user_id') ?? 'me'));
        $userId = $target === 'me' ? (int)$request->user()->id : (int)$target;

        // Pick an existing achievement if available.
        $def = null;
        if (Schema::hasTable('overflowachievement_achievements')) {
            $def = Achievement::query()->where('is_active', true)->inRandomOrder()->first();
        }
        $key = $def ? (string)$def->key : 'test_trophy';

        // Ensure stat exists.
        UserStat::query()->firstOrCreate(['user_id' => $userId], ['xp_total' => 0, 'level' => 1]);

        UnlockedAchievement::create([
            'user_id' => $userId,
            'achievement_key' => $key,
            'unlocked_at' => now(),
            'seen_at' => null,
            'quote_id' => 'test',
            'quote_text' => 'This is a test unlock. The universe is weird, enjoy the confetti.',
            'quote_author' => 'OverflowAchievement',
        ]);

        return redirect()->back()->with('success', __('Test notification queued. Reload as the target user to see it.'));
    }

    /**
     * Return a JSON payload to preview the toast instantly (no DB insert required).
     */
    public function testPreview(Request $request)
    {
        $this->ensureAdmin($request);

        // Build a fake achievement-like object.
        $def = null;
        if (Schema::hasTable('overflowachievement_achievements')) {
            $def = Achievement::query()->where('is_active', true)->inRandomOrder()->first();
        }

        $item = [
            'id' => 0,
            'key' => $def ? (string)$def->key : 'test_trophy',
            'title' => $def ? (string)$def->title : 'Test Trophy',
            'rarity' => $def ? (string)$def->rarity : 'epic',
            'icon_type' => $def ? (string)$def->icon_type : 'fa',
            'icon_value' => $def ? (string)$def->icon_value : 'fa-trophy',
            'quote_text' => 'This is a live preview. If your brain smiles, the UI is doing its job.',
            'quote_author' => 'OverflowAchievement',
            'is_level_up' => true,
        ];

        $stat = [
            'level' => 7,
            'xp_total' => 1337,
            'cur_min' => 1200,
            'next_min' => 1600,
            'progress' => 34,
        ];

        return response()->json([
            'ok' => true,
            'item' => $item,
            'stat' => $stat,
            'ui' => [
                'confetti' => (bool)\Option::get('overflowachievement.ui.confetti', 1),
                'effect' => (string)\Option::get('overflowachievement.ui.effect', 'confetti'),
                'toast_theme' => (string)\Option::get('overflowachievement.ui.toast_theme', 'neon'),
                'sound_enabled' => (bool)\Option::get('overflowachievement.ui.sound_enabled', config('overflowachievement.ui.sound_enabled') ? 1 : 0),
            ],
        ]);
    }

    protected function handleIconUpload(Request $request, Achievement $achievement): void
    {
        $file = $request->file('icon_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');

        // SECURITY: Do not allow SVG uploads (can embed scripts).
        if (!in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
            return;
        }

        // Keep uploads small; these are icons.
        if (method_exists($file, 'getSize') && (int)$file->getSize() > 512 * 1024) {
            return;
        }

        // Best-effort MIME check.
        if (method_exists($file, 'getMimeType')) {
            $mime = (string)$file->getMimeType();
            if ($mime && strpos($mime, 'image/') !== 0) {
                return;
            }
        }

        if (!in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
            return;
        }

        $publicDir = public_path('modules/overflowachievement/icons/custom');
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0775, true);
        }

        $name = $achievement->key.'-'.bin2hex(random_bytes(4)).'-'.time().'.'.$ext;
        $file->move($publicDir, $name);

        $achievement->icon_type = 'img';
        $achievement->icon_value = '/modules/overflowachievement/icons/custom/'.$name;
        $achievement->save();
    }
}
