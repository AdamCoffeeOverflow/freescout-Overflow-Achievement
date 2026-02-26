<?php

namespace Modules\OverflowAchievement\Entities;

use Illuminate\Database\Eloquent\Model;

class UserStat extends Model
{
    protected $table = 'overflowachievement_user_stats';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'xp_total',
        'daily_xp',
        'daily_xp_date',
        'level',
        'streak_current',
        'streak_best',
        'last_activity_at',
        'last_activity_date',

        // Counters
        'closes_count',
        'first_replies_count',
        'notes_count',
        'assigned_count',
        'merged_count',
        'moved_count',
        'forwarded_count',
        'attachments_count',
        'customers_created_count',
        'customer_updates_count',
        'conversations_created_count',
        'subjects_changed_count',
        'replies_sent_count',
        'customer_replies_count',
        'pending_set_count',
        'spam_marked_count',
        'deleted_count',
        'customers_merged_count',
        'focus_minutes',
        'sla_first_response_ultra_count',
        'sla_first_response_fast_count',
        'sla_fast_reply_ultra_count',
        'sla_fast_reply_count',
        'sla_resolve_4h_count',
        'sla_resolve_24h_count',
        'actions_count',
    ];

    protected $casts = [
        'xp_total' => 'int',
        'daily_xp' => 'int',
        'daily_xp_date' => 'date',
        'level' => 'int',
        'streak_current' => 'int',
        'streak_best' => 'int',
        'last_activity_at' => 'datetime',
        'last_activity_date' => 'date',

        // Counters
        'closes_count' => 'int',
        'first_replies_count' => 'int',
        'notes_count' => 'int',
        'assigned_count' => 'int',
        'merged_count' => 'int',
        'moved_count' => 'int',
        'forwarded_count' => 'int',
        'attachments_count' => 'int',
        'customers_created_count' => 'int',
        'customer_updates_count' => 'int',
        'conversations_created_count' => 'int',
        'subjects_changed_count' => 'int',
        'replies_sent_count' => 'int',
        'customer_replies_count' => 'int',
        'pending_set_count' => 'int',
        'spam_marked_count' => 'int',
        'deleted_count' => 'int',
        'customers_merged_count' => 'int',
        'focus_minutes' => 'int',
        'sla_first_response_ultra_count' => 'int',
        'sla_first_response_fast_count' => 'int',
        'sla_fast_reply_ultra_count' => 'int',
        'sla_fast_reply_count' => 'int',
        'sla_resolve_4h_count' => 'int',
        'sla_resolve_24h_count' => 'int',
        'actions_count' => 'int',
    ];
}
