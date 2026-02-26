<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// IMPORTANT: FreeScout 1.8.x uses an older Laravel where the migration class name
// must match the filename (without timestamp) in StudlyCase.
// add_achievement_definitions_and_counters => AddAchievementDefinitionsAndCounters
class AddAchievementDefinitionsAndCounters extends Migration
{
    public function up()
    {
        // Add counters to stats (safe if table exists)
        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                if (!Schema::hasColumn('overflowachievement_user_stats', 'closes_count')) {
                    $table->unsignedInteger('closes_count')->default(0)->after('level');
                }
                if (!Schema::hasColumn('overflowachievement_user_stats', 'first_replies_count')) {
                    $table->unsignedInteger('first_replies_count')->default(0)->after('closes_count');
                }
                if (!Schema::hasColumn('overflowachievement_user_stats', 'last_activity_date')) {
                    $table->date('last_activity_date')->nullable()->after('last_activity_at');
                }
            });
        }

        if (!Schema::hasTable('overflowachievement_achievements')) {
            Schema::create('overflowachievement_achievements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('key', 64)->unique();
                $table->string('title', 120);
                $table->string('description', 255)->nullable();
                $table->string('trigger', 64)->index();
                $table->unsignedInteger('threshold')->default(1);
                $table->unsignedInteger('xp_reward')->default(0);
                $table->string('rarity', 16)->default('common'); // common|rare|epic|legendary
                $table->string('icon_type', 8)->default('fa'); // fa|img
                $table->string('icon_value', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['trigger', 'threshold']);
            });
        }

        // Seed default achievements if empty.
        if (Schema::hasTable('overflowachievement_achievements')) {
            $count = (int)DB::table('overflowachievement_achievements')->count();
            if ($count === 0) {
                $now = date('Y-m-d H:i:s');

                $rows = [];

                $add = function($key, $title, $desc, $trigger, $threshold, $xp, $rarity, $icon) use (&$rows, $now) {
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
                $add('hello_world', 'Hello, World', 'Send your first reply.', 'first_reply', 1, 25, 'common', 'fa-comment');
                $add('first_close', 'First Close', 'Close your first conversation.', 'close_conversation', 1, 35, 'common', 'fa-check');

                // Close milestones
                $milestones = [5,10,25,50,100,200,500,1000];
                foreach ($milestones as $m) {
                    $rarity = ($m >= 500) ? 'legendary' : (($m >= 200) ? 'epic' : (($m >= 50) ? 'rare' : 'common'));
                    $xp = (int)round(10 * sqrt($m));
                    // FontAwesome in FreeScout is FA4.x; use compatible icon names.
                    $add('closer_'.$m, 'Closer x'.$m, 'Close '.$m.' conversations.', 'close_conversation', $m, $xp, $rarity, 'fa-check-circle');
                }

                // First reply milestones
                foreach ($milestones as $m) {
                    $rarity = ($m >= 500) ? 'legendary' : (($m >= 200) ? 'epic' : (($m >= 50) ? 'rare' : 'common'));
                    $xp = (int)round(8 * sqrt($m));
                    $add('responder_'.$m, 'Responder x'.$m, 'Send '.$m.' first replies.', 'first_reply', $m, $xp, $rarity, 'fa-reply');
                }

                // Streak milestones
                $streaks = [3,7,14,30,60,100];
                foreach ($streaks as $d) {
                    $rarity = ($d >= 60) ? 'legendary' : (($d >= 30) ? 'epic' : (($d >= 14) ? 'rare' : 'common'));
                    $xp = (int)round(20 * log($d + 1));
                    $add('streak_'.$d, 'Streak: '.$d.' days', 'Be active for '.$d.' days in a row.', 'streak_days', $d, $xp, $rarity, 'fa-fire');
                }

                // XP milestones
                $xps = [250,500,1000,2500,5000,10000,25000,50000];
                foreach ($xps as $x) {
                    $rarity = ($x >= 25000) ? 'legendary' : (($x >= 10000) ? 'epic' : (($x >= 2500) ? 'rare' : 'common'));
                    $xp = (int)round(15 * log($x/100 + 1));
                    $add('xp_'.$x, 'XP Hoarder '.$x, 'Reach '.$x.' total XP.', 'xp_total', $x, $xp, $rarity, 'fa-star');
                }

                // Insert in chunks for safety.
                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table('overflowachievement_achievements')->insert($chunk);
                }
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('overflowachievement_achievements')) {
            Schema::dropIfExists('overflowachievement_achievements');
        }

        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                if (Schema::hasColumn('overflowachievement_user_stats', 'closes_count')) {
                    $table->dropColumn('closes_count');
                }
                if (Schema::hasColumn('overflowachievement_user_stats', 'first_replies_count')) {
                    $table->dropColumn('first_replies_count');
                }
                if (Schema::hasColumn('overflowachievement_user_stats', 'last_activity_date')) {
                    $table->dropColumn('last_activity_date');
                }
            });
        }
    }
}
