<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixAchievementIconsFa4 extends Migration
{
    public function up()
    {
        if (!DB::getSchemaBuilder()->hasTable('overflowachievement_achievements')) {
            return;
        }

        // 1) Normalize a legacy key that shipped in earlier builds.
        // Some installs may have `first_merge` even though it represents the first close.
        $hasFirstMerge = DB::table('overflowachievement_achievements')->where('key', 'first_merge')->exists();
        $hasFirstClose = DB::table('overflowachievement_achievements')->where('key', 'first_close')->exists();
        if ($hasFirstMerge && !$hasFirstClose) {
            DB::table('overflowachievement_achievements')
                ->where('key', 'first_merge')
                ->update([
                    'key' => 'first_close',
                    'title' => 'First Close',
                    'description' => 'Close your first conversation.',
                ]);
        }

        // 2) FreeScout ships FontAwesome 4.x. Map any FA5/FA6 names to FA4 equivalents.
        $map = [
            'fa-folder-check' => 'fa-check-circle',
            'fa-sticky-note' => 'fa-comment',
            'fa-sticky-note-o' => 'fa-comment',
            'fa-address-card' => 'fa-user',
            'fa-address-card-o' => 'fa-user',
            'fa-share-square' => 'fa-share',
            'fa-share-square-o' => 'fa-share',
            'fa-share-alt' => 'fa-share',
        ];

        foreach ($map as $from => $to) {
            DB::table('overflowachievement_achievements')
                ->where('icon_type', 'fa')
                ->where('icon_value', $from)
                ->update(['icon_value' => $to]);
        }

        // Ensure every achievement has *some* icon.
        DB::table('overflowachievement_achievements')
            ->where(function ($q) {
                $q->whereNull('icon_value')->orWhere('icon_value', '');
            })
            ->update(['icon_type' => 'fa', 'icon_value' => 'fa-trophy']);
    }

    public function down()
    {
        // Non-destructive.
    }
}
