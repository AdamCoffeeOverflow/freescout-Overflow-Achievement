<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeBuiltinAchievementTranslationKeys extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('overflowachievement_achievements')) {
            return;
        }

        $catalogPath = __DIR__.'/../../Resources/lang/en/achievements.php';
        if (!file_exists($catalogPath)) {
            return;
        }

        $catalog = require $catalogPath;
        if (!is_array($catalog) || empty($catalog)) {
            return;
        }

        foreach ($catalog as $key => $fields) {
            $row = DB::table('overflowachievement_achievements')
                ->where('key', $key)
                ->first(['id', 'title', 'description']);

            if (!$row) {
                continue;
            }

            $updates = [];
            $canonicalTitle = 'overflowachievement::achievements.'.$key.'.title';
            $canonicalDescription = 'overflowachievement::achievements.'.$key.'.description';

            $storedTitle = trim((string)($row->title ?? ''));
            $storedDescription = trim((string)($row->description ?? ''));
            $defaultTitle = trim((string)($fields['title'] ?? ''));
            $defaultDescription = trim((string)($fields['description'] ?? ''));

            if ($storedTitle === '' || $storedTitle === $defaultTitle || $storedTitle === $canonicalTitle) {
                $updates['title'] = $canonicalTitle;
            }

            if ($storedDescription === '' || $storedDescription === $defaultDescription || $storedDescription === $canonicalDescription) {
                $updates['description'] = $canonicalDescription;
            }

            if (!empty($updates)) {
                $updates['updated_at'] = date('Y-m-d H:i:s');
                DB::table('overflowachievement_achievements')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down()
    {
        // Keep canonical translation keys in place on rollback.
    }
}
