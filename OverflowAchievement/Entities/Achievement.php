<?php

namespace Modules\OverflowAchievement\Entities;

use Illuminate\Database\Eloquent\Model;

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
}
