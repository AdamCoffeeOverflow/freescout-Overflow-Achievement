<?php

namespace Modules\OverflowAchievement\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\OverflowAchievement\Support\QuoteCatalog;

class UnlockedAchievement extends Model
{
    protected $table = 'overflowachievement_unlocked';

    protected $fillable = [
        'user_id',
        'achievement_key',
        'unlocked_at',
        'seen_at',
        'quote_id',
        'quote_text',
        'quote_author',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function getDisplayQuoteTextAttribute(): string
    {
        return QuoteCatalog::localizeText($this->quote_id, $this->quote_text);
    }

    public function getDisplayQuoteAuthorAttribute(): string
    {
        return QuoteCatalog::localizeAuthor($this->quote_id, $this->quote_author);
    }
}
