<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAchievementMailboxId extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        Schema::table('overflowachievement_achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('overflowachievement_achievements', 'mailbox_id')) {
                $table->integer('mailbox_id')->nullable()->index();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        Schema::table('overflowachievement_achievements', function (Blueprint $table) {
            if (Schema::hasColumn('overflowachievement_achievements', 'mailbox_id')) {
                $table->dropColumn('mailbox_id');
            }
        });
    }
}
