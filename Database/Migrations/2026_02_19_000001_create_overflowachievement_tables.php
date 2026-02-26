<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOverflowachievementTables extends Migration
{
    public function up()
    {
        Schema::create('overflowachievement_user_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('streak_current')->default(0);
            $table->unsignedInteger('streak_best')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('xp_total');
            $table->index('level');
        });

        Schema::create('overflowachievement_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('event_type', 64)->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->integer('xp_delta')->default(0);
            // Keep it DB-agnostic and safe (MySQL + PostgreSQL)
            $table->longText('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('overflowachievement_unlocked', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('achievement_key', 64)->index();
            $table->timestamp('unlocked_at');
            $table->timestamp('seen_at')->nullable()->index();
            $table->string('quote_id', 32)->nullable();
            $table->string('quote_text', 255)->nullable();
            $table->string('quote_author', 120)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('overflowachievement_unlocked');
        Schema::dropIfExists('overflowachievement_events');
        Schema::dropIfExists('overflowachievement_user_stats');
    }
}
