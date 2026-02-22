<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NewTriggersCreatedSubject extends Migration
{
    public function up()
    {
        // Add counters.
        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                $cols = [
                    'conversations_created_count' => 'unsignedInteger',
                    'subjects_changed_count' => 'unsignedInteger',
                ];

                foreach ($cols as $col => $type) {
                    if (!Schema::hasColumn('overflowachievement_user_stats', $col)) {
                        $table->unsignedInteger($col)->default(0);
                    }
                }
            });
        }

        // Seed achievements for the new triggers.
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $existing = DB::table('overflowachievement_achievements')->pluck('key')->all();
        $existingMap = array_fill_keys($existing, true);

        $rows = [];
        $add = function($key, $title, $desc, $trigger, $threshold, $xp, $rarity, $icon) use (&$rows, $now, $existingMap) {
            if (!empty($existingMap[$key])) {
                return;
            }
            $rows[] = [
                'key' => $key,
                'title' => $title,
                'description' => $desc,
                'trigger' => $trigger,
                'threshold' => $threshold,
                'xp_reward' => $xp,
                'rarity' => $rarity,
                'icon_type' => 'fa',
                'icon_value' => $icon,
                'is_active' => true,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        // Firsts
        $add('first_created', 'Proactive', 'Create your first conversation.', 'conversation_created', 1, 20, 'common', 'fa-envelope');
        $add('first_subject_edit', 'Subject Surgeon', 'Edit a conversation subject for the first time.', 'subject_changed', 1, 15, 'common', 'fa-header');

        // Milestones
        $milestones = [5, 10, 25, 50, 100, 250, 500];
        $rarityFor = function($m) {
            return ($m >= 500) ? 'legendary' : (($m >= 250) ? 'epic' : (($m >= 50) ? 'rare' : 'common'));
        };

        foreach ($milestones as $m) {
            $rarity = $rarityFor($m);
            $add('creator_'.$m, 'Proactive x'.$m, 'Create '.$m.' conversations.', 'conversation_created', $m, (int)round(7 * sqrt($m)), $rarity, 'fa-envelope');
            $add('subject_'.$m, 'Subject Surgeon x'.$m, 'Edit conversation subjects '.$m.' times.', 'subject_changed', $m, (int)round(4 * sqrt($m)), $rarity, 'fa-header');
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('overflowachievement_achievements')->insert($chunk);
            }
        }

        // Ensure icons exist.
        DB::table('overflowachievement_achievements')
            ->whereNull('icon_value')
            ->update(['icon_type' => 'fa', 'icon_value' => 'fa-trophy']);
    }

    public function down()
    {
        // Keep user progress safe on downgrade: do not drop columns by default.
    }
}
