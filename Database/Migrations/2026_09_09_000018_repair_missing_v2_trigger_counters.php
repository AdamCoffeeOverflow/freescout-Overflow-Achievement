<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Laravel 5.5 resolves 2026_02_20_000007_more_triggers_and_counters.php
 * as MoreTriggersAndCounters, while that shipped file declares
 * MoreTriggersAndCountersV2. On normal affected installs the bad migration is
 * already recorded, but an administrator may have removed that ledger row while
 * troubleshooting. All pending migration files are required before migrations
 * execute, so this no-op compatibility class prevents the old pending migration
 * from fatalling in that recovery state. On a fresh install 000003 has already
 * declared the real class and this block does nothing.
 */
if (!class_exists('MoreTriggersAndCounters', false)) {
    class MoreTriggersAndCounters extends Migration
    {
        public function up()
        {
            // The append-only repair below owns the actual recovery.
        }

        public function down()
        {
            // Compatibility shim only.
        }
    }
}

class RepairMissingV2TriggerCounters extends Migration
{
    public function up()
    {
        $this->repairCounters();
        $this->repairAchievements();
    }

    protected function repairCounters()
    {
        if (!Schema::hasTable('overflowachievement_user_stats')) {
            return;
        }

        Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
            if (!Schema::hasColumn('overflowachievement_user_stats', 'replies_sent_count')) {
                $table->unsignedInteger('replies_sent_count')->default(0);
            }
            if (!Schema::hasColumn('overflowachievement_user_stats', 'customer_replies_count')) {
                $table->unsignedInteger('customer_replies_count')->default(0);
            }
            if (!Schema::hasColumn('overflowachievement_user_stats', 'pending_set_count')) {
                $table->unsignedInteger('pending_set_count')->default(0);
            }
            if (!Schema::hasColumn('overflowachievement_user_stats', 'spam_marked_count')) {
                $table->unsignedInteger('spam_marked_count')->default(0);
            }
            if (!Schema::hasColumn('overflowachievement_user_stats', 'deleted_count')) {
                $table->unsignedInteger('deleted_count')->default(0);
            }
            if (!Schema::hasColumn('overflowachievement_user_stats', 'customers_merged_count')) {
                $table->unsignedInteger('customers_merged_count')->default(0);
            }
            if (!Schema::hasColumn('overflowachievement_user_stats', 'focus_minutes')) {
                $table->unsignedInteger('focus_minutes')->default(0);
            }
        });
    }

    protected function repairAchievements()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        $existing = DB::table('overflowachievement_achievements')->pluck('key')->all();
        $existing_map = array_fill_keys($existing, true);
        $now = date('Y-m-d H:i:s');
        $rows = [];

        $add = function ($key, $trigger, $threshold, $xp, $rarity, $icon) use (&$rows, &$existing_map, $now) {
            if (!empty($existing_map[$key])) {
                return;
            }

            $rows[] = [
                'key' => $key,
                'title' => 'overflowachievement::achievements.'.$key.'.title',
                'description' => 'overflowachievement::achievements.'.$key.'.description',
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

            $existing_map[$key] = true;
        };

        $add('first_reply_sent', 'reply_sent', 1, 10, 'common', 'fa-reply');
        $add('first_customer_reply', 'customer_replied', 1, 8, 'common', 'fa-inbox');
        $add('first_pending', 'set_pending', 1, 10, 'common', 'fa-hourglass-half');
        $add('first_spam', 'marked_spam', 1, 15, 'common', 'fa-ban');
        $add('first_delete', 'deleted_conversation', 1, 15, 'common', 'fa-trash');
        $add('first_customer_merge', 'customer_merged', 1, 20, 'common', 'fa-users');
        $add('first_focus_10', 'focus_time', 10, 20, 'common', 'fa-eye');

        $milestones = [5, 10, 25, 50, 100, 250, 500];
        foreach ($milestones as $m) {
            $rarity = ($m >= 500) ? 'legendary' : (($m >= 250) ? 'epic' : (($m >= 50) ? 'rare' : 'common'));

            $add('reply_sent_'.$m, 'reply_sent', $m, (int)round(3 * sqrt($m)), $rarity, 'fa-reply');
            $add('customer_reply_'.$m, 'customer_replied', $m, (int)round(2 * sqrt($m)), $rarity, 'fa-inbox');
            $add('pending_'.$m, 'set_pending', $m, (int)round(3 * sqrt($m)), $rarity, 'fa-hourglass-half');
            $add('spam_'.$m, 'marked_spam', $m, (int)round(4 * sqrt($m)), $rarity, 'fa-ban');
            $add('delete_'.$m, 'deleted_conversation', $m, (int)round(4 * sqrt($m)), $rarity, 'fa-trash');
            $add('cust_merge_'.$m, 'customer_merged', $m, (int)round(5 * sqrt($m)), $rarity, 'fa-users');
        }

        $focus_milestones = [30, 60, 180, 300, 600, 1200, 2400];
        foreach ($focus_milestones as $m) {
            $rarity = ($m >= 2400) ? 'legendary' : (($m >= 1200) ? 'epic' : (($m >= 300) ? 'rare' : 'common'));
            $add('focus_'.$m, 'focus_time', $m, (int)round(2 * sqrt($m)), $rarity, 'fa-eye');
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('overflowachievement_achievements')->insert($chunk);
            }
        }
    }

    public function down()
    {
        // Forward-fix only: preserve counters and achievement progress on rollback.
    }
}
