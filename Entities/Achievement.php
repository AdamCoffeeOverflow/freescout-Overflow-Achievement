<?php

namespace Modules\OverflowAchievement\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\OverflowAchievement\Support\AchievementCatalog;

class Achievement extends Model
{
    protected $table = 'overflowachievement_achievements';

    protected $fillable = [
        'key','title','description','trigger','threshold','xp_reward','rarity',
        'icon_type','icon_value','is_active','created_by',
        'mailbox_id',
        'quote_id','quote_text','quote_author','quote_tone'
    ];

    protected $casts = [
        'mailbox_id' => 'int',
        'threshold' => 'int',
        'xp_reward' => 'int',
        'is_active' => 'bool',
    ];

    public function getDisplayTitleAttribute(): string
    {
        return static::translateText($this->title, (string)$this->key, 'title', (string)$this->trigger, (int)$this->threshold);
    }

    public function getDisplayDescriptionAttribute(): string
    {
        return static::translateText($this->description, (string)$this->key, 'description', (string)$this->trigger, (int)$this->threshold);
    }

    public static function translateText($value, string $achievementKey = '', string $field = '', string $trigger = '', int $threshold = 0): string
    {
        return AchievementCatalog::translateField($value, $achievementKey, $field, $trigger, $threshold);
    }
}
