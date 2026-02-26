<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAchievementQuoteTone extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        Schema::table('overflowachievement_achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('overflowachievement_achievements', 'quote_tone')) {
                $table->string('quote_tone', 16)->nullable()->index();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        Schema::table('overflowachievement_achievements', function (Blueprint $table) {
            if (Schema::hasColumn('overflowachievement_achievements', 'quote_tone')) {
                $table->dropIndex(['quote_tone']);
                $table->dropColumn('quote_tone');
            }
        });
    }
}
