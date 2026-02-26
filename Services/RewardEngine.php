<?php

namespace Modules\OverflowAchievement\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\OverflowAchievement\Entities\Achievement;
use Modules\OverflowAchievement\Entities\Event;
use Modules\OverflowAchievement\Entities\UnlockedAchievement;
use Modules\OverflowAchievement\Entities\UserStat;

class RewardEngine
{
    /** @var array<string, mixed> */
    protected static array $optCache = [];

    /**
     * Keep this class compatible with PHP 7.4 (FreeScout commonly supports 7.4+).
     * Avoid PHP 8 constructor property promotion.
     */
    protected LevelService $levelService;

    protected QuoteService $quoteService;

    protected function installed(): bool
    {
        // Cache within request to avoid repeated schema queries.
        static $ok = null;
        if ($ok !== null) {
            return (bool)$ok;
        }

        try {
            $ok = Schema::hasTable('overflowachievement_events')
                && Schema::hasTable('overflowachievement_user_stats')
                && Schema::hasTable('overflowachievement_unlocked');
        } catch (\Throwable $e) {
            $ok = false;
        }
        return (bool)$ok;
    }

    protected function opt(string $key, $default = null)
    {
        $full = 'overflowachievement.'.$key;
        if (array_key_exists($full, self::$optCache)) {
            return self::$optCache[$full];
        }
        try {
            $val = \Option::get($full, $default);
        } catch (\Throwable $e) {
            $val = $default;
        }
        self::$optCache[$full] = $val;
        return $val;
    }

    /**
     * Public, cheap capability checks used by hooks to skip expensive pre-queries
     * (for example, "is this the first reply?") when the corresponding XP/SLA is disabled.
     */
    public function enabled(): bool
    {
        return (bool)$this->opt('enabled', (bool)config('overflowachievement.enabled'));
    }

    public function wantsFirstReplyCheck(): bool
    {
        if (!$this->enabled()) {
            return false;
        }

        // First reply XP.
        $xp = (int)$this->opt('xp.first_reply', (int)config('overflowachievement.xp.first_reply', 15));
        if ($xp > 0) {
            return true;
        }

        // SLA (first response) may also depend on the "first reply" determination.
        return (bool)$this->opt('sla.first_response.enabled', (bool)config('overflowachievement.sla.first_response.enabled', false));
    }

    public function wantsAttachmentAward(): bool
    {
        if (!$this->enabled()) {
            return false;
        }
        $xp = (int)$this->opt('xp.attachment_added', (int)config('overflowachievement.xp.attachment_added', 5));
        return $xp > 0;
    }

    public function __construct(LevelService $levelService, QuoteService $quoteService)
    {
        $this->levelService = $levelService;
        $this->quoteService = $quoteService;
    }

    // -----------------------------
    // Primary actions (existing)
    // -----------------------------

    public function awardCloseConversation(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.close_conversation', (int)config('overflowachievement.xp.close_conversation', 25));
        if ($xp <= 0) {
            return;
        }

        if ((bool)$this->opt('limits.close_once_per_conversation', (bool)config('overflowachievement.limits.close_once_per_conversation', true))) {
            if ($this->eventExists($user_id, 'close_conversation', $conversation_id)) {
                return;
            }
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'close_conversation', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => (bool)$this->opt('limits.close_once_per_conversation', (bool)config('overflowachievement.limits.close_once_per_conversation', true)) ? 'once_per_conversation' : '',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'close_conversation', (int)($stat->closes_count ?? 0));
        }
    }

    public function awardFirstReply(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.first_reply', (int)config('overflowachievement.xp.first_reply', 15));
        if ($xp <= 0) {
            return;
        }

        if ((bool)$this->opt('limits.first_reply_once_per_conversation', (bool)config('overflowachievement.limits.first_reply_once_per_conversation', true))) {
            if ($this->eventExists($user_id, 'first_reply', $conversation_id)) {
                return;
            }
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'first_reply', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => (bool)$this->opt('limits.first_reply_once_per_conversation', (bool)config('overflowachievement.limits.first_reply_once_per_conversation', true)) ? 'once_per_conversation' : '',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'first_reply', (int)($stat->first_replies_count ?? 0));
        }
    }

    // -----------------------------
    // Work actions (new)
    // -----------------------------

    public function awardConversationCreated(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.conversation_created', (int)config('overflowachievement.xp.conversation_created', 10));
        if ($xp <= 0) {
            return;
        }

        // Only once per conversation.
        if ($this->eventExists($user_id, 'conversation_created', $conversation_id)) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'conversation_created', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'conversation_created', (int)($stat->conversations_created_count ?? 0));
        }
    }

    public function awardSubjectChanged(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.subject_changed', (int)config('overflowachievement.xp.subject_changed', 2));
        if ($xp <= 0) {
            return;
        }

        // Cap once per conversation per day (subject editing can get spammy).
        if ($this->eventsToday($user_id, 'subject_changed', $conversation_id) >= 1) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'subject_changed', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation_per_day',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'subject_changed', (int)($stat->subjects_changed_count ?? 0));
        }
    }

    /**
     * Any agent reply (not only the first reply). Capped per conversation per day.
     */
    public function awardReplySent(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.reply_sent', (int)config('overflowachievement.xp.reply_sent', 3));
        if ($xp <= 0) {
            return;
        }

        $max = (int)$this->opt('limits.reply_max_per_conversation_per_day', (int)config('overflowachievement.limits.reply_max_per_conversation_per_day', 6));
        if ($max > 0 && $this->eventsToday($user_id, 'reply_sent', $conversation_id) >= $max) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'reply_sent', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'max_per_conversation_per_day',
            'max' => $max,
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'reply_sent', (int)($stat->replies_sent_count ?? 0));
        }
    }

    /**
     * Customer replied in a conversation assigned to the user.
     * This is a "workload" signal; keep XP low and cap per conversation per day.
     */
    
    // -----------------------------
    // SLA / quality triggers
    // -----------------------------

    /**
     * SLA: Fast follow-up after a customer reply.
     * Awards when an agent replies within configured minutes after the latest customer reply
     * on the same conversation assigned to the agent.
     */
    public function awardSlaFastReply(int $user_id, int $conversation_id, $reply_at = null): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $replyAt = $reply_at ? Carbon::parse($reply_at) : Carbon::now();

        $lastCustomerReply = Event::query()
            ->where('user_id', $user_id)
            ->where('event_type', 'customer_replied')
            ->where('subject_type', 'conversation')
            ->where('subject_id', $conversation_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastCustomerReply || empty($lastCustomerReply->created_at)) {
            return;
        }

        $crAt = Carbon::parse($lastCustomerReply->created_at);
        $mins = (int)max(0, $crAt->diffInMinutes($replyAt, false));
        if ($mins < 0) {
            // reply before customer reply (should not happen)
            return;
        }

        $awardedUltra = false;

        $ultra = (int)$this->opt('sla.fast_reply_ultra_minutes', (int)config('overflowachievement.sla.fast_reply_ultra_minutes', 5));
        $fast  = (int)$this->opt('sla.fast_reply_minutes', (int)config('overflowachievement.sla.fast_reply_minutes', 30));

        // Dedupe per customer reply timestamp (so one customer message can't be farmed).
        $dedupeKeyBase = $crAt->format('Y-m-d H:i:s');

        if ($ultra > 0 && $mins <= $ultra) {
            $xp = (int)$this->opt('xp.sla_fast_reply_ultra', (int)config('overflowachievement.xp.sla_fast_reply_ultra', 6));
            if ($xp > 0) {
                // once per conversation per day is additional safety.
                $stat = $this->awardXpAndUpdateStats($user_id, 'sla_fast_reply_ultra', $xp, $conversation_id, [
                    'conversation_id' => $conversation_id,
                    'minutes' => $mins,
                    'customer_reply_at' => $dedupeKeyBase,
                ], [
                    'subject_type' => 'conversation',
                    'subject_id' => $conversation_id,
                ], [
                    'mode' => 'once_per_conversation_per_day',
                ]);
                if ($stat) {
                    $this->evaluateTriggeredAchievements($user_id, 'sla_fast_reply_ultra', (int)($stat->sla_fast_reply_ultra_count ?? 0));
                }
            }
                $awardedUltra = true;
        }

        

        if ($awardedUltra) {
            return;
                $awardedUltraFirst = true;
        }

        

        if ($awardedUltraFirst) {
            return;
        }

        if ($fast > 0 && $mins <= $fast) {
            $xp = (int)$this->opt('xp.sla_fast_reply', (int)config('overflowachievement.xp.sla_fast_reply', 4));
            if ($xp > 0) {
                $stat = $this->awardXpAndUpdateStats($user_id, 'sla_fast_reply', $xp, $conversation_id, [
                    'conversation_id' => $conversation_id,
                    'minutes' => $mins,
                    'customer_reply_at' => $dedupeKeyBase,
                ], [
                    'subject_type' => 'conversation',
                    'subject_id' => $conversation_id,
                ], [
                    'mode' => 'once_per_conversation_per_day',
                ]);
                if ($stat) {
                    $this->evaluateTriggeredAchievements($user_id, 'sla_fast_reply', (int)($stat->sla_fast_reply_count ?? 0));
                }
            }
        }
    }

    /**
     * SLA: Fast first response on a ticket (first agent reply).
     * Awards when the first agent reply happens within configured minutes from ticket creation.
     */
    public function awardSlaFirstResponse(int $user_id, int $conversation_id, $conversation_created_at, $reply_at): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        if (!$conversation_created_at || !$reply_at) {
            return;
        }

        $createdAt = Carbon::parse($conversation_created_at);
        $replyAt = Carbon::parse($reply_at);
        $mins = (int)max(0, $createdAt->diffInMinutes($replyAt, false));
        if ($mins < 0) {
            return;
        }

        $awardedUltraFirst = false;

        $ultra = (int)$this->opt('sla.first_response_ultra_minutes', (int)config('overflowachievement.sla.first_response_ultra_minutes', 5));
        $fast  = (int)$this->opt('sla.first_response_fast_minutes', (int)config('overflowachievement.sla.first_response_fast_minutes', 30));

        // Dedupe: only once per conversation.
        if ($ultra > 0 && $mins <= $ultra) {
            $xp = (int)$this->opt('xp.sla_first_response_ultra', (int)config('overflowachievement.xp.sla_first_response_ultra', 12));
            if ($xp > 0) {
                $stat = $this->awardXpAndUpdateStats($user_id, 'sla_first_response_ultra', $xp, $conversation_id, [
                    'conversation_id' => $conversation_id,
                    'minutes' => $mins,
                ], [
                    'subject_type' => 'conversation',
                    'subject_id' => $conversation_id,
                ], [
                    'mode' => 'once_per_conversation',
                ]);
                if ($stat) {
                    $this->evaluateTriggeredAchievements($user_id, 'sla_first_response_ultra', (int)($stat->sla_first_response_ultra_count ?? 0));
                }
            }
                $awardedUltra = true;
        }

        

        if ($awardedUltra) {
            return;
                $awardedUltraFirst = true;
        }

        

        if ($awardedUltraFirst) {
            return;
        }

        if ($fast > 0 && $mins <= $fast) {
            $xp = (int)$this->opt('xp.sla_first_response_fast', (int)config('overflowachievement.xp.sla_first_response_fast', 8));
            if ($xp > 0) {
                $stat = $this->awardXpAndUpdateStats($user_id, 'sla_first_response_fast', $xp, $conversation_id, [
                    'conversation_id' => $conversation_id,
                    'minutes' => $mins,
                ], [
                    'subject_type' => 'conversation',
                    'subject_id' => $conversation_id,
                ], [
                    'mode' => 'once_per_conversation',
                ]);
                if ($stat) {
                    $this->evaluateTriggeredAchievements($user_id, 'sla_first_response_fast', (int)($stat->sla_first_response_fast_count ?? 0));
                }
            }
        }
    }

    /**
     * SLA: Fast resolution (close) relative to ticket creation.
     */
    public function awardSlaResolve(int $user_id, int $conversation_id, $conversation_created_at, $closed_at = null): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }
        if (!$conversation_created_at) {
            return;
        }

        $createdAt = Carbon::parse($conversation_created_at);
        $closedAt = $closed_at ? Carbon::parse($closed_at) : Carbon::now();
        $hours = (int)max(0, $createdAt->diffInHours($closedAt, false));
        if ($hours < 0) {
            return;
        }

        $awardedRapid = false;

        $h4  = (int)$this->opt('sla.resolve_4h_hours', (int)config('overflowachievement.sla.resolve_4h_hours', 4));
        $h24 = (int)$this->opt('sla.resolve_24h_hours', (int)config('overflowachievement.sla.resolve_24h_hours', 24));

        if ($h4 > 0 && $hours <= $h4) {
            $xp = (int)$this->opt('xp.sla_resolve_4h', (int)config('overflowachievement.xp.sla_resolve_4h', 12));
            if ($xp > 0) {
                $stat = $this->awardXpAndUpdateStats($user_id, 'sla_resolve_4h', $xp, $conversation_id, [
                    'conversation_id' => $conversation_id,
                    'hours' => $hours,
                ], [
                    'subject_type' => 'conversation',
                    'subject_id' => $conversation_id,
                ], [
                    'mode' => 'once_per_conversation',
                ]);
                if ($stat) {
                    $this->evaluateTriggeredAchievements($user_id, 'sla_resolve_4h', (int)($stat->sla_resolve_4h_count ?? 0));
                }
            }
                $awardedRapid = true;
        }

        

        if ($awardedRapid) {
            return;
        }

        if ($h24 > 0 && $hours <= $h24) {
            $xp = (int)$this->opt('xp.sla_resolve_24h', (int)config('overflowachievement.xp.sla_resolve_24h', 8));
            if ($xp > 0) {
                $stat = $this->awardXpAndUpdateStats($user_id, 'sla_resolve_24h', $xp, $conversation_id, [
                    'conversation_id' => $conversation_id,
                    'hours' => $hours,
                ], [
                    'subject_type' => 'conversation',
                    'subject_id' => $conversation_id,
                ], [
                    'mode' => 'once_per_conversation',
                ]);
                if ($stat) {
                    $this->evaluateTriggeredAchievements($user_id, 'sla_resolve_24h', (int)($stat->sla_resolve_24h_count ?? 0));
                }
            }
        }
    }
public function awardCustomerReplied(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.customer_replied', (int)config('overflowachievement.xp.customer_replied', 1));
        if ($xp <= 0) {
            return;
        }

        $max = (int)$this->opt('limits.customer_reply_max_per_conversation_per_day', (int)config('overflowachievement.limits.customer_reply_max_per_conversation_per_day', 6));
        if ($max > 0 && $this->eventsToday($user_id, 'customer_replied', $conversation_id) >= $max) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'customer_replied', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'max_per_conversation_per_day',
            'max' => $max,
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'customer_replied', (int)($stat->customer_replies_count ?? 0));
        }
    }

    /**
     * Set status to pending (once per conversation per day).
     */
    public function awardSetPending(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.set_pending', (int)config('overflowachievement.xp.set_pending', 2));
        if ($xp <= 0) {
            return;
        }

        if ($this->eventsToday($user_id, 'set_pending', $conversation_id) >= 1) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'set_pending', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation_per_day',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'set_pending', (int)($stat->pending_set_count ?? 0));
        }
    }

    /**
     * Mark as spam (once per conversation).
     */
    public function awardMarkedSpam(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.marked_spam', (int)config('overflowachievement.xp.marked_spam', 5));
        if ($xp <= 0) {
            return;
        }

        if ($this->eventExists($user_id, 'marked_spam', $conversation_id)) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'marked_spam', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'marked_spam', (int)($stat->spam_marked_count ?? 0));
        }
    }

    /**
     * Delete conversation (once per conversation).
     */
    public function awardDeletedConversation(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.deleted_conversation', (int)config('overflowachievement.xp.deleted_conversation', 5));
        if ($xp <= 0) {
            return;
        }

        if ($this->eventExists($user_id, 'deleted_conversation', $conversation_id)) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'deleted_conversation', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'deleted_conversation', (int)($stat->deleted_count ?? 0));
        }
    }

    /**
     * Merge customers (once per customer profile).
     */
    public function awardCustomerMerged(int $user_id, int $customer_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.customer_merged', (int)config('overflowachievement.xp.customer_merged', 12));
        if ($xp <= 0) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'customer_merged', $xp, null, [
            'customer_id' => $customer_id,
        ], [
            'subject_type' => 'customer',
            'subject_id' => $customer_id,
        ], [
            'mode' => 'once_per_subject',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'customer_merged', (int)($stat->customers_merged_count ?? 0));
        }
    }

    /**
     * Focus time when viewing a conversation. Awards XP per minute.
     */
    public function awardFocusTime(int $user_id, int $conversation_id, int $seconds): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $per_min = (int)$this->opt('xp.focus_time', (int)config('overflowachievement.xp.focus_time', 1));
        if ($per_min <= 0) {
            return;
        }

        $max_event = (int)$this->opt('limits.focus_max_minutes_per_event', (int)config('overflowachievement.limits.focus_max_minutes_per_event', 10));
        $max_event = max(1, $max_event);
        $seconds = max(0, $seconds);
        $minutes = (int)floor(min($seconds, $max_event * 60) / 60);
        if ($minutes <= 0) {
            return;
        }

        $max_conv_day = (int)$this->opt('limits.focus_max_minutes_per_conversation_per_day', (int)config('overflowachievement.limits.focus_max_minutes_per_conversation_per_day', 30));
        $max_conv_day = max($max_event, $max_conv_day);
        $max_events = (int)ceil($max_conv_day / $max_event);

        if ($max_events > 0 && $this->eventsToday($user_id, 'focus_time', $conversation_id) >= $max_events) {
            return;
        }

        $xp = $minutes * $per_min;

        $stat = $this->awardXpAndUpdateStats($user_id, 'focus_time', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
            'seconds' => $seconds,
            'minutes' => $minutes,
        ], [], [
            'mode' => 'max_per_conversation_per_day',
            'max' => $max_events,
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'focus_time', (int)($stat->focus_minutes ?? 0));
        }
    }

    public function awardNoteAdded(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.note_added', (int)config('overflowachievement.xp.note_added', 8));
        if ($xp <= 0) {
            return;
        }

        $max = (int)$this->opt('limits.note_max_per_conversation_per_day', (int)config('overflowachievement.limits.note_max_per_conversation_per_day', 3));
        if ($max > 0 && $this->eventsToday($user_id, 'note_added', $conversation_id) >= $max) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'note_added', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'max_per_conversation_per_day',
            'max' => (int)$this->opt('limits.note_max_per_conversation_per_day', (int)config('overflowachievement.limits.note_max_per_conversation_per_day', 3)),
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'note_added', (int)($stat->notes_count ?? 0));
        }
    }

    public function awardAssigned(int $user_id, int $conversation_id, int $prev_user_id = 0, int $new_user_id = 0): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        // Semantics: "assigned" means "took ownership" (self-assign).
        // In FreeScout, the hook parameter $user is the actor who changed the assignee.
        // Do not award XP when someone assigns a conversation to another user.
        if ($new_user_id && (int)$new_user_id !== (int)$user_id) {
            return;
        }
        if ($prev_user_id && (int)$prev_user_id === (int)$user_id) {
            // Already owned; nothing to count.
            return;
        }

        $xp = (int)$this->opt('xp.assigned', (int)config('overflowachievement.xp.assigned', 6));
        if ($xp <= 0) {
            return;
        }

        // Prevent spam by only counting once per conversation per day.
        if ($this->eventsToday($user_id, 'assigned', $conversation_id) >= 1) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'assigned', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
            'prev_user_id' => $prev_user_id,
            'new_user_id' => $new_user_id,
        ], [], [
            'mode' => 'once_per_conversation_per_day',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'assigned', (int)($stat->assigned_count ?? 0));
        }
    }

    public function awardMerged(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.merged', (int)config('overflowachievement.xp.merged', 20));
        if ($xp <= 0) {
            return;
        }

        // One per conversation forever.
        if ($this->eventExists($user_id, 'merged', $conversation_id)) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'merged', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'merged', (int)($stat->merged_count ?? 0));
        }
    }

    public function awardMoved(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.moved', (int)config('overflowachievement.xp.moved', 5));
        if ($xp <= 0) {
            return;
        }

        // Cap once per conversation per day.
        if ($this->eventsToday($user_id, 'moved', $conversation_id) >= 1) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'moved', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation_per_day',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'moved', (int)($stat->moved_count ?? 0));
        }
    }

    public function awardForwarded(int $user_id, int $conversation_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.forwarded', (int)config('overflowachievement.xp.forwarded', 12));
        if ($xp <= 0) {
            return;
        }

        // One per conversation forever.
        if ($this->eventExists($user_id, 'forwarded', $conversation_id)) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'forwarded', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'once_per_conversation',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'forwarded', (int)($stat->forwarded_count ?? 0));
        }
    }

    public function awardAttachmentAdded(int $user_id, ?int $conversation_id = null): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.attachment_added', (int)config('overflowachievement.xp.attachment_added', 5));
        if ($xp <= 0) {
            return;
        }

        $max = (int)$this->opt('limits.attachment_max_per_conversation_per_day', (int)config('overflowachievement.limits.attachment_max_per_conversation_per_day', 3));
        if ($conversation_id && $max > 0 && $this->eventsToday($user_id, 'attachment_added', $conversation_id) >= $max) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'attachment_added', $xp, $conversation_id, [
            'conversation_id' => $conversation_id,
        ], [], [
            'mode' => 'max_per_conversation_per_day',
            'max' => (int)$this->opt('limits.attachment_max_per_conversation_per_day', (int)config('overflowachievement.limits.attachment_max_per_conversation_per_day', 3)),
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'attachment_added', (int)($stat->attachments_count ?? 0));
        }
    }

    public function awardCustomerCreated(int $user_id, int $customer_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.customer_created', (int)config('overflowachievement.xp.customer_created', 10));
        if ($xp <= 0) {
            return;
        }

        // One per customer.
        // Newer versions store subject_type/subject_id; older versions used meta JSON.
        $exists = Event::query()
            ->where('user_id', $user_id)
            ->where('event_type', 'customer_created')
            ->where(function ($q) use ($customer_id) {
                $q->where(function ($q2) use ($customer_id) {
                    $q2->where('subject_type', 'customer')->where('subject_id', $customer_id);
                })->orWhere('meta', 'like', '%"customer_id":'.$customer_id.'%');
            })
            ->exists();
        if ($exists) {
            return;
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'customer_created', $xp, null, [
            'customer_id' => $customer_id,
        ], [
            'subject_type' => 'customer',
            'subject_id' => $customer_id,
        ], [
            'mode' => 'once_per_subject',
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'customer_created', (int)($stat->customers_created_count ?? 0));
        }
    }

    public function awardCustomerUpdated(int $user_id, int $customer_id): void
    {
        if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
            return;
        }
        if (!$this->installed()) {
            return;
        }

        $xp = (int)$this->opt('xp.customer_updated', (int)config('overflowachievement.xp.customer_updated', 4));
        if ($xp <= 0) {
            return;
        }

        $max = (int)$this->opt('limits.customer_updates_max_per_day', (int)config('overflowachievement.limits.customer_updates_max_per_day', 25));
        if ($max > 0) {
            $today_start = Carbon::now()->startOfDay();
            $count = (int)Event::query()
                ->where('user_id', $user_id)
                ->where('event_type', 'customer_updated')
                ->where('created_at', '>=', $today_start)
                ->count();
            if ($count >= $max) {
                return;
            }
        }

        $stat = $this->awardXpAndUpdateStats($user_id, 'customer_updated', $xp, null, [
            'customer_id' => $customer_id,
        ], [
            'subject_type' => 'customer',
            'subject_id' => $customer_id,
        ], [
            'mode' => 'max_per_day',
            'max' => (int)$this->opt('limits.customer_updates_max_per_day', (int)config('overflowachievement.limits.customer_updates_max_per_day', 25)),
        ]);

        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'customer_updated', (int)($stat->customer_updates_count ?? 0));
        }
    }

    // -----------------------------
    // Core mechanics
    // -----------------------------

    protected function eventExists(int $user_id, string $event_type, int $conversation_id): bool
    {
        static $cache = [];
        $k = $user_id.'|'.$event_type.'|'.$conversation_id;
        if (isset($cache[$k])) {
            return (bool)$cache[$k];
        }
        $exists = Event::query()
            ->where('user_id', $user_id)
            ->where('event_type', $event_type)
            ->where('conversation_id', $conversation_id)
            ->exists();
        $cache[$k] = $exists ? 1 : 0;
        return (bool)$exists;
    }

    protected function eventsToday(int $user_id, string $event_type, int $conversation_id): int
    {
        static $cache = [];
        $today = Carbon::now()->toDateString();
        $k = $today.'|'.$user_id.'|'.$event_type.'|'.$conversation_id;
        if (isset($cache[$k])) {
            return (int)$cache[$k];
        }

        $today_start = Carbon::now()->startOfDay();
        $count = (int)Event::query()
            ->where('user_id', $user_id)
            ->where('event_type', $event_type)
            ->where('conversation_id', $conversation_id)
            ->where('created_at', '>=', $today_start)
            ->count();
        $cache[$k] = $count;
        return $count;
    }


    /**
     * Check whether a query has at least $max rows without doing COUNT(*).
     * Uses a small OFFSET which is fine for small caps (like 5, 10, 25).
     */
    protected function hasAtLeast($query, int $max): bool
    {
        if ($max <= 0) {
            return false;
        }
        return (bool)$query->skip($max - 1)->take(1)->exists();
    }


    /**
     * Awards XP (respecting caps), updates stats (xp, level, counters, streak), and logs an event.
     */
    protected function awardXpAndUpdateStats(
        int $user_id,
        string $event_type,
        int $xp,
        ?int $conversation_id = null,
        array $meta = [],
        array $subject = [],
        array $dedupe = []
    ): ?UserStat
    {
        $xp = max(0, $xp);
        if ($xp === 0) {
            return null;
        }

        $cap = (int)$this->opt('caps.daily_xp', (int)config('overflowachievement.caps.daily_xp', 800));

        $effective_xp = 0;
        $today = Carbon::now()->toDateString();

        $stat_out = null;

        DB::transaction(function () use ($user_id, $event_type, $xp, $conversation_id, $meta, $subject, $cap, $today, &$effective_xp, &$stat_out) {
            // Lock row to avoid concurrent lost-updates (common on busy helpdesks).
            $stat = UserStat::query()->where('user_id', $user_id)->lockForUpdate()->first();
            if (!$stat) {
                UserStat::query()->create([
                    'user_id' => $user_id,
                    'xp_total' => 0,
                    'daily_xp' => 0,
                    'daily_xp_date' => $today,
                    'level' => 1,
                    'closes_count' => 0,
                    'first_replies_count' => 0,
                    'notes_count' => 0,
                    'assigned_count' => 0,
                    'merged_count' => 0,
                    'moved_count' => 0,
                    'forwarded_count' => 0,
                    'attachments_count' => 0,
                    'customers_created_count' => 0,
                    'customer_updates_count' => 0,
                    'actions_count' => 0,
                    'streak_current' => 0,
                    'streak_best' => 0,
                ]);
                $stat = UserStat::query()->where('user_id', $user_id)->lockForUpdate()->first();
            }



            // Concurrency-safe dedupe: re-check inside the user row lock.
            // This prevents double-counting when two requests race (both pass the pre-check).
            if (!empty($dedupe)) {
                $mode = (string)($dedupe['mode'] ?? '');

                if ($mode === 'once_per_conversation' && $conversation_id) {
                    $exists = Event::query()
                        ->where('user_id', $user_id)
                        ->where('event_type', $event_type)
                        ->where('conversation_id', $conversation_id)
                        ->exists();
                    if ($exists) {
                        $effective_xp = 0;
                        return;
                    }
                }

                if ($mode === 'once_per_conversation_per_day' && $conversation_id) {
                    $today_start = Carbon::now()->startOfDay();
                    $exists = Event::query()
                        ->where('user_id', $user_id)
                        ->where('event_type', $event_type)
                        ->where('conversation_id', $conversation_id)
                        ->where('created_at', '>=', $today_start)
                        ->exists();
                    if ($exists) {
                        $effective_xp = 0;
                        return;
                    }
                }

                if ($mode === 'max_per_conversation_per_day' && $conversation_id) {
                    $max = (int)($dedupe['max'] ?? 0);
                    if ($max > 0) {
                        $today_start = Carbon::now()->startOfDay();
                        $q = Event::query()
                            ->where('user_id', $user_id)
                            ->where('event_type', $event_type)
                            ->where('conversation_id', $conversation_id)
                            ->where('created_at', '>=', $today_start);
                        if ($this->hasAtLeast($q, $max)) {
                            $effective_xp = 0;
                            return;
                        }
                    }
                }

                if ($mode === 'max_per_day') {
                    $max = (int)($dedupe['max'] ?? 0);
                    if ($max > 0) {
                        $today_start = Carbon::now()->startOfDay();
                        $q = Event::query()
                            ->where('user_id', $user_id)
                            ->where('event_type', $event_type)
                            ->where('created_at', '>=', $today_start);
                        if ($this->hasAtLeast($q, $max)) {
                            $effective_xp = 0;
                            return;
                        }
                    }
                }

                if ($mode === 'once_per_subject') {
                    $stype = (string)($subject['subject_type'] ?? '');
                    $sid = (int)($subject['subject_id'] ?? 0);
                    if ($stype && $sid) {
                        $exists = Event::query()
                            ->where('user_id', $user_id)
                            ->where('event_type', $event_type)
                            ->where('subject_type', $stype)
                            ->where('subject_id', $sid)
                            ->exists();
                        if ($exists) {
                            $effective_xp = 0;
                            return;
                        }
                    }
                }
            }
            // Reset daily XP counter if it's a new day.
            $dailyDate = $stat->daily_xp_date ? (string)$stat->daily_xp_date : null;
            if ($dailyDate !== $today) {
                $stat->daily_xp = 0;
                $stat->daily_xp_date = $today;
            }

            $alreadyToday = (int)($stat->daily_xp ?? 0);
            if ($cap > 0 && $alreadyToday >= $cap) {
                $effective_xp = 0;
                return;
            }

            $effective_xp = $xp;
            if ($cap > 0 && ($alreadyToday + $xp) > $cap) {
                $effective_xp = max(0, $cap - $alreadyToday);
            }
            if ($effective_xp <= 0) {
                $effective_xp = 0;
                return;
            }

            Event::create([
                'user_id' => $user_id,
                'event_type' => $event_type,
                'conversation_id' => $conversation_id,
                'subject_type' => $subject['subject_type'] ?? null,
                'subject_id' => $subject['subject_id'] ?? null,
                'xp_delta' => $effective_xp,
                'meta' => !empty($meta) ? $meta : null,
            ]);

            // Counters
            switch ($event_type) {
                case 'close_conversation':
                    $stat->closes_count = (int)($stat->closes_count ?? 0) + 1;
                    break;
                case 'first_reply':
                    $stat->first_replies_count = (int)($stat->first_replies_count ?? 0) + 1;
                    break;
                case 'note_added':
                    $stat->notes_count = (int)($stat->notes_count ?? 0) + 1;
                    break;
                case 'assigned':
                    $stat->assigned_count = (int)($stat->assigned_count ?? 0) + 1;
                    break;
                case 'merged':
                    $stat->merged_count = (int)($stat->merged_count ?? 0) + 1;
                    break;
                case 'moved':
                    $stat->moved_count = (int)($stat->moved_count ?? 0) + 1;
                    break;
                case 'forwarded':
                    $stat->forwarded_count = (int)($stat->forwarded_count ?? 0) + 1;
                    break;
                case 'attachment_added':
                    $stat->attachments_count = (int)($stat->attachments_count ?? 0) + 1;
                    break;
                case 'customer_created':
                    $stat->customers_created_count = (int)($stat->customers_created_count ?? 0) + 1;
                    break;
                case 'customer_updated':
                    $stat->customer_updates_count = (int)($stat->customer_updates_count ?? 0) + 1;
                    break;
                case 'conversation_created':
                    $stat->conversations_created_count = (int)($stat->conversations_created_count ?? 0) + 1;
                    break;
                case 'subject_changed':
                    $stat->subjects_changed_count = (int)($stat->subjects_changed_count ?? 0) + 1;
                    break;
                case 'reply_sent':
                    $stat->replies_sent_count = (int)($stat->replies_sent_count ?? 0) + 1;
                    break;
                case 'customer_replied':
                    $stat->customer_replies_count = (int)($stat->customer_replies_count ?? 0) + 1;
                    break;
                case 'set_pending':
                    $stat->pending_set_count = (int)($stat->pending_set_count ?? 0) + 1;
                    break;
                case 'marked_spam':
                    $stat->spam_marked_count = (int)($stat->spam_marked_count ?? 0) + 1;
                    break;
                case 'deleted_conversation':
                    $stat->deleted_count = (int)($stat->deleted_count ?? 0) + 1;
                    break;
                case 'customer_merged':
                    $stat->customers_merged_count = (int)($stat->customers_merged_count ?? 0) + 1;
                    break;
                case 'focus_time':
                    $minutes = (int)($meta['minutes'] ?? 0);
                    if ($minutes > 0) {
                        $stat->focus_minutes = (int)($stat->focus_minutes ?? 0) + $minutes;
                    }
                    break;
            
                case 'sla_first_response_ultra':
                    $stat->sla_first_response_ultra_count = (int)($stat->sla_first_response_ultra_count ?? 0) + 1;
                    break;
                case 'sla_first_response_fast':
                    $stat->sla_first_response_fast_count = (int)($stat->sla_first_response_fast_count ?? 0) + 1;
                    break;
                case 'sla_fast_reply_ultra':
                    $stat->sla_fast_reply_ultra_count = (int)($stat->sla_fast_reply_ultra_count ?? 0) + 1;
                    break;
                case 'sla_fast_reply':
                    $stat->sla_fast_reply_count = (int)($stat->sla_fast_reply_count ?? 0) + 1;
                    break;
                case 'sla_resolve_4h':
                    $stat->sla_resolve_4h_count = (int)($stat->sla_resolve_4h_count ?? 0) + 1;
                    break;
                case 'sla_resolve_24h':
                    $stat->sla_resolve_24h_count = (int)($stat->sla_resolve_24h_count ?? 0) + 1;
                    break;
            }

            // Count any rewarding action (excluding XP-only bonus from trophies)
            if ($event_type !== 'achievement_unlock') {
                $stat->actions_count = (int)($stat->actions_count ?? 0) + 1;
            }

            // Streak (any XP-awarding action counts as activity)
            $yesterday = Carbon::now()->subDay()->toDateString();
            $last_date = $stat->last_activity_date ? (string)$stat->last_activity_date : null;

            if ($last_date === $today) {
                // already counted today
            } elseif ($last_date === $yesterday) {
                $stat->streak_current = (int)($stat->streak_current ?? 0) + 1;
            } else {
                $stat->streak_current = 1;
            }

            if ((int)$stat->streak_current > (int)($stat->streak_best ?? 0)) {
                $stat->streak_best = (int)$stat->streak_current;
            }

            $stat->last_activity_at = Carbon::now();
            $stat->last_activity_date = $today;

            // XP + level
            $stat->xp_total = (int)$stat->xp_total + $effective_xp;
            $stat->daily_xp = (int)($stat->daily_xp ?? 0) + $effective_xp;
            $stat->level = $this->levelService->levelForXp((int)$stat->xp_total);
            $stat->save();

            $stat_out = $stat;
        });

        if ($effective_xp <= 0) {
            return null;
        }

        // Evaluate cross-cutting achievements outside tx (lighter)
        $stat = $stat_out ?: UserStat::query()->where('user_id', $user_id)->first();
        if ($stat) {
            $this->evaluateTriggeredAchievements($user_id, 'streak_days', (int)($stat->streak_current ?? 0));
            $this->evaluateTriggeredAchievements($user_id, 'xp_total', (int)($stat->xp_total ?? 0));
            $this->evaluateTriggeredAchievements($user_id, 'actions_total', (int)($stat->actions_count ?? 0));
        }

        return $stat;
    }

    protected function evaluateTriggeredAchievements(int $user_id, string $trigger, int $value): void
    {
        try {
            if (!(bool)$this->opt('enabled', config('overflowachievement.enabled'))) {
                return;
            }
            if (!$this->installed()) {
                return;
            }

            if (!Schema::hasTable('overflowachievement_achievements')) {
                return;
            }

            // Cache active definitions per trigger within the request.
            static $trigger_cache = [];
            if (!array_key_exists($trigger, $trigger_cache)) {
                $trigger_cache[$trigger] = Achievement::query()
                    ->where('is_active', true)
                    ->where('trigger', $trigger)
                    ->orderBy('threshold')
                    ->get();
            }

            $achievements = $trigger_cache[$trigger]->filter(function ($a) use ($value) {
                return (int)($a->threshold ?? 0) <= $value;
            })->values();

            if ($achievements->isEmpty()) {
                return;
            }

            $keys = $achievements->pluck('key')->filter()->values()->all();
            if (empty($keys)) {
                return;
            }

            $already = UnlockedAchievement::query()
                ->where('user_id', $user_id)
                ->whereIn('achievement_key', $keys)
                ->pluck('achievement_key')
                ->all();

            $alreadyMap = array_fill_keys($already, true);

            foreach ($achievements as $achievement) {
                if (!empty($alreadyMap[$achievement->key])) {
                    continue;
                }
                $this->unlockAchievement($user_id, $achievement);
            }
        } catch (\Throwable $e) {
            \Log::error('OverflowAchievement: evaluateTriggeredAchievements failed: '.$e->getMessage());
        }
    }

    protected function unlockAchievement(int $user_id, Achievement $achievement): void
    {
        if (empty($achievement->key)) {
            return;
        }

        // Quotes are unique per achievement. If the achievement does not have a quote assigned yet,
        // fall back to a deterministic pick from the library (stable across installs).
        $quote = $this->quoteService->forAchievement($achievement);

        try {
            UnlockedAchievement::create([
                'user_id' => $user_id,
                'achievement_key' => $achievement->key,
                'unlocked_at' => Carbon::now(),
                'seen_at' => null,
                'quote_id' => $quote['id'],
                'quote_text' => $quote['text'],
                'quote_author' => $quote['author'],
            ]);
        } catch (\Throwable $e) {
            // Race/duplicate: ignore.
            return;
        }

        $bonus = (int)($achievement->xp_reward ?? 0);
        if ($bonus > 0) {
            $this->awardXpAndUpdateStats($user_id, 'achievement_unlock', $bonus, null, [
                'achievement_key' => $achievement->key,
                'bonus' => true,
            ]);
        }
    }

    protected function inferThemeFromTrigger(string $trigger): string
    {
        switch ($trigger) {
            case 'conversation_created':
                return 'initiative';
            case 'subject_changed':
                return 'clarity';
            case 'first_reply':
                return 'speed';
            case 'close_conversation':
                return 'quality';
            case 'note_added':
                return 'focus';
            case 'assigned':
                return 'ownership';
            case 'merged':
                return 'order';
            case 'moved':
                return 'flow';
            case 'forwarded':
                return 'clarity';
            case 'attachment_added':
                return 'craft';
            case 'customer_created':
            case 'customer_updated':
                return 'care';
            case 'streak_days':
                return 'consistency';
            case 'actions_total':
                return 'momentum';
            case 'xp_total':
                return 'mastery';
            default:
                return 'generic';
        }
    }

    protected function recentQuoteIds(int $user_id, int $limit = 10): array
    {
        return UnlockedAchievement::query()
            ->where('user_id', $user_id)
            ->whereNotNull('quote_id')
            ->orderByDesc('unlocked_at')
            ->limit($limit)
            ->pluck('quote_id')
            ->toArray();
    }
}
