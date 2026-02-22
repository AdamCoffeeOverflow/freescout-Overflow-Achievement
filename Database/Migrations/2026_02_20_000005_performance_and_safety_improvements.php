<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// IMPORTANT: FreeScout 1.8.x ships an older Laravel. Keep migrations simple and DB-agnostic.
class PerformanceAndSafetyImprovements extends Migration
{
    public function up()
    {
        // Events table improvements
        if (Schema::hasTable('overflowachievement_events')) {
            Schema::table('overflowachievement_events', function (Blueprint $table) {
                // Normalize subjects (customers, etc.) so we don't have to LIKE-search JSON blobs.
                if (!Schema::hasColumn('overflowachievement_events', 'subject_type')) {
                    $table->string('subject_type', 32)->nullable()->index();
                }
                if (!Schema::hasColumn('overflowachievement_events', 'subject_id')) {
                    $table->unsignedBigInteger('subject_id')->nullable()->index();
                }
            });

            // Composite indexes for hot paths.
            Schema::table('overflowachievement_events', function (Blueprint $table) {
                $table->index(['user_id', 'event_type', 'conversation_id'], 'oa_ev_user_type_conv');
                $table->index(['user_id', 'event_type', 'conversation_id', 'created_at'], 'oa_ev_user_type_conv_created');
                $table->index(['user_id', 'event_type', 'subject_type', 'subject_id'], 'oa_ev_user_type_subject');
            });
        }

        // Stats table improvements
        if (Schema::hasTable('overflowachievement_user_stats')) {
            Schema::table('overflowachievement_user_stats', function (Blueprint $table) {
                // Daily XP cap can be enforced without summing events each time.
                if (!Schema::hasColumn('overflowachievement_user_stats', 'daily_xp')) {
                    $table->unsignedInteger('daily_xp')->default(0)->after('xp_total');
                }
                if (!Schema::hasColumn('overflowachievement_user_stats', 'daily_xp_date')) {
                    $table->date('daily_xp_date')->nullable()->after('daily_xp');
                }
            });
        }

        // Unlocked table improvements
        if (Schema::hasTable('overflowachievement_unlocked')) {
            Schema::table('overflowachievement_unlocked', function (Blueprint $table) {
                $table->index(['user_id', 'seen_at'], 'oa_un_user_seen');
                $table->index(['user_id', 'unlocked_at'], 'oa_un_user_unlocked');
            });
        }
    }

    public function down()
    {
        // Non-destructive: avoid dropping columns/indexes in down() to protect user data.
    }
}
