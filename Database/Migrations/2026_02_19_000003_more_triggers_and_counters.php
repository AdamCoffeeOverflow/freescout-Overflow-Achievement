<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MoreTriggersAndCounters extends Migration
{
    public function up()
    {
        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                $cols = [
                    'notes_count' => 'unsignedInteger',
                    'assigned_count' => 'unsignedInteger',
                    'merged_count' => 'unsignedInteger',
                    'moved_count' => 'unsignedInteger',
                    'forwarded_count' => 'unsignedInteger',
                    'attachments_count' => 'unsignedInteger',
                    'customers_created_count' => 'unsignedInteger',
                    'customer_updates_count' => 'unsignedInteger',
                    'actions_count' => 'unsignedInteger',
                ];

                foreach ($cols as $col => $type) {
                    if (!Schema::hasColumn('overflowachievement_user_stats', $col)) {
                        $table->unsignedInteger($col)->default(0);
                    }
                }
            });
        }

        // Seed more achievements (only missing keys).
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

        // Firsts (new triggers)
        $add('first_note', 'Internal Note', 'Add your first internal note.', 'note_added', 1, 15, 'common', 'fa-comment');
        $add('first_assign', 'Ownership Taken', 'Change an assignment for the first time.', 'assigned', 1, 15, 'common', 'fa-user');
        $add('first_forward', 'Forward Thinker', 'Forward your first conversation.', 'forwarded', 1, 20, 'common', 'fa-share');
        $add('first_move', 'Mailbox Hopper', 'Move a conversation to another mailbox.', 'moved', 1, 20, 'common', 'fa-exchange');
        $add('first_attachment', 'Paperclip Power', 'Add your first attachment.', 'attachment_added', 1, 15, 'common', 'fa-paperclip');
        $add('first_customer', 'Customer Creator', 'Create your first customer profile.', 'customer_created', 1, 25, 'common', 'fa-user');

        // Milestones helper
        $milestones = [5, 10, 25, 50, 100, 250, 500];
        $rarityFor = function($m) {
            return ($m >= 500) ? 'legendary' : (($m >= 250) ? 'epic' : (($m >= 50) ? 'rare' : 'common'));
        };

        foreach ($milestones as $m) {
            $rarity = $rarityFor($m);
            $add('notekeeper_'.$m, 'Note Keeper x'.$m, 'Add '.$m.' internal notes.', 'note_added', $m, (int)round(6 * sqrt($m)), $rarity, 'fa-comment');
            $add('owner_'.$m, 'Dispatcher x'.$m, 'Change assignment '.$m.' times.', 'assigned', $m, (int)round(6 * sqrt($m)), $rarity, 'fa-users');
            $add('forwarder_'.$m, 'Forwarder x'.$m, 'Forward '.$m.' conversations.', 'forwarded', $m, (int)round(7 * sqrt($m)), $rarity, 'fa-share');
            $add('mover_'.$m, 'Mover x'.$m, 'Move '.$m.' conversations.', 'moved', $m, (int)round(6 * sqrt($m)), $rarity, 'fa-exchange');
            $add('merger_'.$m, 'Merger x'.$m, 'Merge '.$m.' conversations.', 'merged', $m, (int)round(8 * sqrt($m)), $rarity, 'fa-compress');
            $add('attachments_'.$m, 'Attachment Artist x'.$m, 'Add '.$m.' attachments.', 'attachment_added', $m, (int)round(5 * sqrt($m)), $rarity, 'fa-paperclip');
            $add('customers_'.$m, 'Customer Builder x'.$m, 'Create '.$m.' customers.', 'customer_created', $m, (int)round(7 * sqrt($m)), $rarity, 'fa-user');
            $add('profile_polish_'.$m, 'Profile Polish x'.$m, 'Update customer profiles '.$m.' times.', 'customer_updated', $m, (int)round(4 * sqrt($m)), $rarity, 'fa-pencil');
        }

        // “All-around” activity milestones
        $actions = [50, 100, 250, 500, 1000, 2500];
        foreach ($actions as $m) {
            $rarity = ($m >= 2500) ? 'legendary' : (($m >= 1000) ? 'epic' : (($m >= 250) ? 'rare' : 'common'));
            $add('actions_'.$m, 'Flow State '.$m, 'Perform '.$m.' rewarding actions.', 'actions_total', $m, (int)round(10 * log($m + 1)), $rarity, 'fa-bolt');
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('overflowachievement_achievements')->insert($chunk);
            }
        }

        // Ensure older achievements have an icon (best-effort).
        DB::table('overflowachievement_achievements')
            ->whereNull('icon_value')
            ->update(['icon_type' => 'fa', 'icon_value' => 'fa-trophy']);
    }

    public function down()
    {
        // Keep user progress safe on downgrade: do not drop columns by default.
    }
}
