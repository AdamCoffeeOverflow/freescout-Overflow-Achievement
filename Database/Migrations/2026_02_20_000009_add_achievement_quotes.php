<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddAchievementQuotes extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        Schema::table('overflowachievement_achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('overflowachievement_achievements', 'quote_id')) {
                $table->string('quote_id', 16)->nullable()->index();
            }
            if (!Schema::hasColumn('overflowachievement_achievements', 'quote_text')) {
                $table->text('quote_text')->nullable();
            }
            if (!Schema::hasColumn('overflowachievement_achievements', 'quote_author')) {
                $table->string('quote_author', 255)->nullable();
            }
        });

        // Backfill quote_id for existing achievements (unique until the library is exhausted).
        try {
            $library = (array)config('overflowachievement.quotes.library', []);
            $ids = [];
            foreach ($library as $q) {
                if (!empty($q['id'])) {
                    $ids[] = $q['id'];
                }
            }
            if (empty($ids)) {
                return;
            }

            $rows = DB::table('overflowachievement_achievements')
                ->select('id', 'quote_id')
                ->orderBy('id')
                ->get();

            $used = [];
            foreach ($rows as $r) {
                if (!empty($r->quote_id)) {
                    $used[$r->quote_id] = true;
                }
            }

            $cursor = 0;
            foreach ($rows as $r) {
                if (!empty($r->quote_id)) {
                    continue;
                }

                // Find next unused quote id.
                $pick = null;
                $tries = 0;
                while ($tries < count($ids)) {
                    $candidate = $ids[$cursor % count($ids)];
                    $cursor++;
                    $tries++;
                    if (empty($used[$candidate])) {
                        $pick = $candidate;
                        break;
                    }
                }

                // If exhausted, allow reuse (still deterministic per key at runtime).
                if ($pick === null) {
                    $pick = $ids[$cursor % count($ids)];
                    $cursor++;
                }

                DB::table('overflowachievement_achievements')
                    ->where('id', $r->id)
                    ->update(['quote_id' => $pick]);

                $used[$pick] = true;
            }
        } catch (\Throwable $e) {
            // Never block migration on quote backfill.
        }
    }

    public function down()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        Schema::table('overflowachievement_achievements', function (Blueprint $table) {
            if (Schema::hasColumn('overflowachievement_achievements', 'quote_author')) {
                $table->dropColumn('quote_author');
            }
            if (Schema::hasColumn('overflowachievement_achievements', 'quote_text')) {
                $table->dropColumn('quote_text');
            }
            if (Schema::hasColumn('overflowachievement_achievements', 'quote_id')) {
                $table->dropIndex(['quote_id']);
                $table->dropColumn('quote_id');
            }
        });
    }
}
