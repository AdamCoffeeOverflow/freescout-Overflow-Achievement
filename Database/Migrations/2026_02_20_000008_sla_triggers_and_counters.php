<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SlaTriggersAndCounters extends Migration
{
    public function up()
    {
        // Add counters.
        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                $cols = [
                    'sla_first_response_ultra_count' => 'unsignedInteger',
                    'sla_first_response_fast_count'  => 'unsignedInteger',
                    'sla_fast_reply_ultra_count'     => 'unsignedInteger',
                    'sla_fast_reply_count'           => 'unsignedInteger',
                    'sla_resolve_4h_count'           => 'unsignedInteger',
                    'sla_resolve_24h_count'          => 'unsignedInteger',
                ];

                foreach ($cols as $col => $type) {
                    if (!Schema::hasColumn('overflowachievement_user_stats', $col)) {
                        $table->unsignedInteger($col)->default(0);
                    }
                }
            });
        }

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
        $add('sla_first_response_ultra_1', 'Lightning First Response', 'First reply within 5 minutes.', 'sla_first_response_ultra', 1, 30, 'rare', 'fa-bolt');
        $add('sla_first_response_fast_1', 'Fast First Response', 'First reply within 30 minutes.', 'sla_first_response_fast', 1, 20, 'common', 'fa-clock-o');
        $add('sla_fast_reply_ultra_1', 'Lightning Follow-up', 'Reply within 5 minutes after a customer message.', 'sla_fast_reply_ultra', 1, 20, 'rare', 'fa-bolt');
        $add('sla_fast_reply_1', 'Fast Follow-up', 'Reply within 30 minutes after a customer message.', 'sla_fast_reply', 1, 12, 'common', 'fa-clock-o');
        $add('sla_resolve_4h_1', 'Rapid Resolution', 'Close a ticket within 4 hours.', 'sla_resolve_4h', 1, 30, 'rare', 'fa-rocket');
        $add('sla_resolve_24h_1', 'Same-day Resolution', 'Close a ticket within 24 hours.', 'sla_resolve_24h', 1, 20, 'common', 'fa-sun-o');

        $milestones = [5, 10, 25, 50, 100, 250];
        $rarityFor = function($m) {
            return ($m >= 250) ? 'epic' : (($m >= 50) ? 'rare' : 'common');
        };

        foreach ($milestones as $m) {
            $rarity = $rarityFor($m);
            $add('sla_first_response_ultra_'.$m, 'Lightning First x'.$m, 'First reply within 5 minutes, '.$m.' times.', 'sla_first_response_ultra', $m, (int)round(8 * sqrt($m)), $rarity, 'fa-bolt');
            $add('sla_first_response_fast_'.$m, 'Fast First x'.$m, 'First reply within 30 minutes, '.$m.' times.', 'sla_first_response_fast', $m, (int)round(6 * sqrt($m)), $rarity, 'fa-clock-o');
            $add('sla_fast_reply_ultra_'.$m, 'Lightning Follow-up x'.$m, 'Reply within 5 minutes, '.$m.' times.', 'sla_fast_reply_ultra', $m, (int)round(6 * sqrt($m)), $rarity, 'fa-bolt');
            $add('sla_fast_reply_'.$m, 'Fast Follow-up x'.$m, 'Reply within 30 minutes, '.$m.' times.', 'sla_fast_reply', $m, (int)round(5 * sqrt($m)), $rarity, 'fa-clock-o');
            $add('sla_resolve_4h_'.$m, 'Rapid Resolve x'.$m, 'Close within 4 hours, '.$m.' times.', 'sla_resolve_4h', $m, (int)round(7 * sqrt($m)), $rarity, 'fa-rocket');
            $add('sla_resolve_24h_'.$m, 'Same-day x'.$m, 'Close within 24 hours, '.$m.' times.', 'sla_resolve_24h', $m, (int)round(6 * sqrt($m)), $rarity, 'fa-sun-o');
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('overflowachievement_achievements')->insert($chunk);
            }
        }

        DB::table('overflowachievement_achievements')
            ->whereNull('icon_value')
            ->update(['icon_type' => 'fa', 'icon_value' => 'fa-trophy']);
    }

    public function down()
    {
        // Keep user progress safe on downgrade: do not drop columns by default.
    }
}
