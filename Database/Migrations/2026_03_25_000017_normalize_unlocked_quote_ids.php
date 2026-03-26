<?php

use Illuminate\Database\Migrations\Migration;
use Modules\OverflowAchievement\Entities\UnlockedAchievement;
use Modules\OverflowAchievement\Support\QuoteCatalog;

class NormalizeUnlockedQuoteIds extends Migration
{
    public function up()
    {
        if (!class_exists(UnlockedAchievement::class)) {
            return;
        }

        UnlockedAchievement::query()
            ->where(function ($query) {
                $query->whereNull('quote_id')->orWhere('quote_id', '');
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $text = trim((string) ($row->quote_text ?? ''));
                    if ($text === '') {
                        continue;
                    }

                    $quoteId = QuoteCatalog::matchIdByText($text);
                    if ($quoteId === null) {
                        continue;
                    }

                    $row->quote_id = $quoteId;
                    $row->save();
                }
            });
    }

    public function down()
    {
        // Intentionally left blank. This migration only fills missing quote IDs.
    }
}
