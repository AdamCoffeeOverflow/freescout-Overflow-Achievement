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

    /**
     * Resolve legacy FontAwesome definitions to a deterministic bundled image.
     * FreeScout's normal application UI ships Glyphicons, not FontAwesome.
     */
    public static function resolveIcon($iconType, $iconValue, $achievementKey = ''): array
    {
        $type = strtolower(trim((string)$iconType));
        $value = trim((string)$iconValue);

        if ($type === 'img' && static::isLocalIconValue($value)) {
            return ['type' => 'img', 'value' => $value];
        }

        $seed = (string)$achievementKey;
        if ($seed === '') {
            $seed = $value !== '' ? $value : 'overflowachievement';
        }
        $hash = (int)sprintf('%u', crc32($seed));
        $index = ($hash % 100) + 1;

        return ['type' => 'img', 'value' => sprintf('icon_%03d.png', $index)];
    }

    public static function isLocalIconValue($iconValue): bool
    {
        $value = trim((string)$iconValue);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^icon_[0-9]{3}\.png$/', $value)) {
            return true;
        }

        if (preg_match('#(?:^|/)modules/overflowachievement/icons/pack/icon_[0-9]{3}\.png$#i', $value)) {
            return true;
        }

        if (preg_match('#(?:^|/)modules/overflowachievement/icons/custom/[A-Za-z0-9._-]+\.(?:png|jpe?g|gif|webp)$#i', $value)) {
            return true;
        }

        return (bool)preg_match('#(?:^|/)storage/overflowachievement/icons/[A-Za-z0-9._-]+\.(?:png|jpe?g|gif|webp)$#i', $value);
    }

    public static function iconUrl($iconValue): string
    {
        $value = trim((string)$iconValue);
        if (!static::isLocalIconValue($value)) {
            $value = 'icon_001.png';
        }

        $modulesPos = strpos($value, '/modules/');
        if ($modulesPos !== false && $modulesPos > 0) {
            $value = substr($value, $modulesPos);
        }
        $storagePos = strpos($value, '/storage/');
        if ($storagePos !== false && $storagePos > 0) {
            $value = substr($value, $storagePos);
        }

        $base = \Helper::getSubdirectory();
        if (strpos($value, '/modules/') === 0 || strpos($value, '/storage/') === 0) {
            return $base.$value;
        }
        if (strpos($value, 'modules/') === 0 || strpos($value, 'storage/') === 0) {
            return $base.'/'.$value;
        }
        if (strpos($value, '/') === false) {
            return $base.'/modules/overflowachievement/icons/pack/'.$value;
        }
        if ($value[0] !== '/') {
            return $base.'/'.$value;
        }

        return $base.$value;
    }

    public static function translateText($value, string $achievementKey = '', string $field = '', string $trigger = '', int $threshold = 0): string
    {
        return AchievementCatalog::translateField($value, $achievementKey, $field, $trigger, $threshold);
    }
}
