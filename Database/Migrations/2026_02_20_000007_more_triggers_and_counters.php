<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoreTriggersAndCounters extends Migration
{
    public function up()
    {
        // Add counters.
        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                $cols = [
                    'replies_sent_count' => 'unsignedInteger',
                    'customer_replies_count' => 'unsignedInteger',
                    'pending_set_count' => 'unsignedInteger',
                    'spam_marked_count' => 'unsignedInteger',
                    'deleted_count' => 'unsignedInteger',
                    'customers_merged_count' => 'unsignedInteger',
                    'focus_minutes' => 'unsignedInteger',
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

        $milestones = [5, 10, 25, 50, 100, 250, 500];
        $rarityFor = function($m) {
            return ($m >= 500) ? 'legendary' : (($m >= 250) ? 'epic' : (($m >= 50) ? 'rare' : 'common'));
        };

        // Firsts
        $add('first_reply_sent', 'First Response', 'Send your first reply.', 'reply_sent', 1, 10, 'common', 'fa-reply');
        $add('first_customer_reply', 'They Wrote Back', 'Receive your first customer reply on an assigned ticket.', 'customer_replied', 1, 8, 'common', 'fa-inbox');
        $add('first_pending', 'Triage Mode', 'Set a conversation to pending for the first time.', 'set_pending', 1, 10, 'common', 'fa-hourglass-half');
        $add('first_spam', 'Spam Slayer', 'Mark your first conversation as spam.', 'marked_spam', 1, 15, 'common', 'fa-ban');
        $add('first_delete', 'Cleanup Crew', 'Delete your first conversation.', 'deleted_conversation', 1, 15, 'common', 'fa-trash');
        $add('first_customer_merge', 'Identity Unifier', 'Merge your first customer profile.', 'customer_merged', 1, 20, 'common', 'fa-users');
        $add('first_focus_10', 'Deep Work', 'Spend 10 focused minutes in tickets.', 'focus_time', 10, 20, 'common', 'fa-eye');

        // Milestones
        foreach ($milestones as $m) {
            $rarity = $rarityFor($m);
            $add('reply_sent_'.$m, 'Responder x'.$m, 'Send '.$m.' replies.', 'reply_sent', $m, (int)round(3 * sqrt($m)), $rarity, 'fa-reply');
            $add('customer_reply_'.$m, 'Inbox x'.$m, 'Receive '.$m.' customer replies on assigned tickets.', 'customer_replied', $m, (int)round(2 * sqrt($m)), $rarity, 'fa-inbox');
            $add('pending_'.$m, 'Triage x'.$m, 'Set '.$m.' conversations to pending.', 'set_pending', $m, (int)round(3 * sqrt($m)), $rarity, 'fa-hourglass-half');
            $add('spam_'.$m, 'Spam Slayer x'.$m, 'Mark '.$m.' conversations as spam.', 'marked_spam', $m, (int)round(4 * sqrt($m)), $rarity, 'fa-ban');
            $add('delete_'.$m, 'Cleanup x'.$m, 'Delete '.$m.' conversations.', 'deleted_conversation', $m, (int)round(4 * sqrt($m)), $rarity, 'fa-trash');
            $add('cust_merge_'.$m, 'Unifier x'.$m, 'Merge '.$m.' customer profiles.', 'customer_merged', $m, (int)round(5 * sqrt($m)), $rarity, 'fa-users');
        }

        // Focus minutes milestones (time-based)
        $focusMilestones = [30, 60, 180, 300, 600, 1200, 2400];
        foreach ($focusMilestones as $m) {
            $rarity = ($m >= 2400) ? 'legendary' : (($m >= 1200) ? 'epic' : (($m >= 300) ? 'rare' : 'common'));
            $add('focus_'.$m, 'Focused '.$m.'m', 'Spend '.$m.' focused minutes in tickets.', 'focus_time', $m, (int)round(2 * sqrt($m)), $rarity, 'fa-eye');
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
