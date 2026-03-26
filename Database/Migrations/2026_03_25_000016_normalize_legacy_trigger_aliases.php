<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\OverflowAchievement\Support\AchievementCatalog;
use Modules\OverflowAchievement\Support\TriggerCatalog;

class NormalizeLegacyTriggerAliases extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        $rows = DB::table('overflowachievement_achievements')
            ->orderBy('id')
            ->get(['id', 'key', 'title', 'description', 'trigger', 'threshold', 'created_by', 'is_active']);

        if ($rows->isEmpty()) {
            return;
        }

        $existing = [];
        foreach ($rows as $row) {
            $existing[(string) $row->key] = true;
        }

        foreach ($rows as $row) {
            if (!$this->looksBuiltin($row)) {
                continue;
            }

            $legacyKey = trim((string) $row->key);
            $normalizedTrigger = TriggerCatalog::normalizeTrigger((string) $row->trigger);
            $canonicalKey = AchievementCatalog::canonicalKey($legacyKey, $normalizedTrigger, (int) $row->threshold);
            $canonicalKey = trim((string) $canonicalKey);

            $updates = [
                'trigger' => $normalizedTrigger !== '' ? $normalizedTrigger : (string) $row->trigger,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($canonicalKey !== '') {
                $updates['title'] = 'overflowachievement::achievements.' . $canonicalKey . '.title';
                $updates['description'] = 'overflowachievement::achievements.' . $canonicalKey . '.description';
            }

            if ($canonicalKey !== '' && $canonicalKey !== $legacyKey) {
                if (!empty($existing[$canonicalKey])) {
                    $updates['is_active'] = false;
                    DB::table('overflowachievement_achievements')->where('id', $row->id)->update($updates);
                } else {
                    $updates['key'] = $canonicalKey;
                    DB::table('overflowachievement_achievements')->where('id', $row->id)->update($updates);
                    $existing[$canonicalKey] = true;
                }

                $this->migrateUnlockedRows($legacyKey, $canonicalKey);
                continue;
            }

            DB::table('overflowachievement_achievements')->where('id', $row->id)->update($updates);
        }
    }

    protected function looksBuiltin($row)
    {
        if (is_null($row->created_by)) {
            return true;
        }

        foreach ([(string) $row->title, (string) $row->description] as $value) {
            if (strpos($value, 'overflowachievement::achievements.') === 0) {
                return true;
            }
        }

        return false;
    }

    protected function migrateUnlockedRows(string $legacyKey, string $canonicalKey): void
    {
        if ($legacyKey === $canonicalKey || !Schema::hasTable('overflowachievement_unlocked')) {
            return;
        }

        $rows = DB::table('overflowachievement_unlocked')
            ->where('achievement_key', $legacyKey)
            ->orderBy('id')
            ->get(['id', 'user_id']);

        foreach ($rows as $row) {
            $duplicate = DB::table('overflowachievement_unlocked')
                ->where('user_id', $row->user_id)
                ->where('achievement_key', $canonicalKey)
                ->exists();

            if ($duplicate) {
                DB::table('overflowachievement_unlocked')->where('id', $row->id)->delete();
                continue;
            }

            DB::table('overflowachievement_unlocked')
                ->where('id', $row->id)
                ->update([
                    'achievement_key' => $canonicalKey,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down()
    {
        // Keep normalized builtin trigger aliases and keys on rollback.
    }
}
