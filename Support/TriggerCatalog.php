<?php

namespace Modules\OverflowAchievement\Support;

class TriggerCatalog
{
    public static function definitions(): array
    {
        return [
            'close_conversation' => [
                'label_key' => 'overflowachievement::catalog.triggers.close_conversation.label',
                'hint_key' => 'overflowachievement::catalog.triggers.close_conversation.hint',
                'scope' => 'per_conversation',
            ],
            'first_reply' => [
                'label_key' => 'overflowachievement::catalog.triggers.first_reply.label',
                'hint_key' => 'overflowachievement::catalog.triggers.first_reply.hint',
                'scope' => 'per_conversation',
            ],
            'note_added' => [
                'label_key' => 'overflowachievement::catalog.triggers.note_added.label',
                'hint_key' => 'overflowachievement::catalog.triggers.note_added.hint',
                'scope' => 'lifetime',
            ],
            'assigned' => [
                'label_key' => 'overflowachievement::catalog.triggers.assigned.label',
                'hint_key' => 'overflowachievement::catalog.triggers.assigned.hint',
                'scope' => 'daily',
            ],
            'merged' => [
                'label_key' => 'overflowachievement::catalog.triggers.merged.label',
                'hint_key' => 'overflowachievement::catalog.triggers.merged.hint',
                'scope' => 'per_conversation',
            ],
            'moved' => [
                'label_key' => 'overflowachievement::catalog.triggers.moved.label',
                'hint_key' => 'overflowachievement::catalog.triggers.moved.hint',
                'scope' => 'daily',
            ],
            'forwarded' => [
                'label_key' => 'overflowachievement::catalog.triggers.forwarded.label',
                'hint_key' => 'overflowachievement::catalog.triggers.forwarded.hint',
                'scope' => 'per_conversation',
            ],
            'attachment_added' => [
                'label_key' => 'overflowachievement::catalog.triggers.attachment_added.label',
                'hint_key' => 'overflowachievement::catalog.triggers.attachment_added.hint',
                'scope' => 'lifetime',
            ],
            'customer_created' => [
                'label_key' => 'overflowachievement::catalog.triggers.customer_created.label',
                'hint_key' => 'overflowachievement::catalog.triggers.customer_created.hint',
                'scope' => 'lifetime',
            ],
            'customer_updated' => [
                'label_key' => 'overflowachievement::catalog.triggers.customer_updated.label',
                'hint_key' => 'overflowachievement::catalog.triggers.customer_updated.hint',
                'scope' => 'lifetime',
            ],
            'conversation_created' => [
                'label_key' => 'overflowachievement::catalog.triggers.conversation_created.label',
                'hint_key' => 'overflowachievement::catalog.triggers.conversation_created.hint',
                'scope' => 'per_conversation',
            ],
            'subject_changed' => [
                'label_key' => 'overflowachievement::catalog.triggers.subject_changed.label',
                'hint_key' => 'overflowachievement::catalog.triggers.subject_changed.hint',
                'scope' => 'daily',
            ],
            'reply_sent' => [
                'label_key' => 'overflowachievement::catalog.triggers.reply_sent.label',
                'hint_key' => 'overflowachievement::catalog.triggers.reply_sent.hint',
                'scope' => 'daily',
            ],
            'customer_replied' => [
                'label_key' => 'overflowachievement::catalog.triggers.customer_replied.label',
                'hint_key' => 'overflowachievement::catalog.triggers.customer_replied.hint',
                'scope' => 'daily',
            ],
            'set_pending' => [
                'label_key' => 'overflowachievement::catalog.triggers.set_pending.label',
                'hint_key' => 'overflowachievement::catalog.triggers.set_pending.hint',
                'scope' => 'daily',
            ],
            'marked_spam' => [
                'label_key' => 'overflowachievement::catalog.triggers.marked_spam.label',
                'hint_key' => 'overflowachievement::catalog.triggers.marked_spam.hint',
                'scope' => 'per_conversation',
            ],
            'deleted_conversation' => [
                'label_key' => 'overflowachievement::catalog.triggers.deleted_conversation.label',
                'hint_key' => 'overflowachievement::catalog.triggers.deleted_conversation.hint',
                'scope' => 'per_conversation',
            ],
            'customer_merged' => [
                'label_key' => 'overflowachievement::catalog.triggers.customer_merged.label',
                'hint_key' => 'overflowachievement::catalog.triggers.customer_merged.hint',
                'scope' => 'lifetime',
            ],
            'focus_time' => [
                'label_key' => 'overflowachievement::catalog.triggers.focus_time.label',
                'hint_key' => 'overflowachievement::catalog.triggers.focus_time.hint',
                'scope' => 'daily',
            ],
            'sla_first_response_ultra' => [
                'label_key' => 'overflowachievement::catalog.triggers.sla_first_response_ultra.label',
                'hint_key' => 'overflowachievement::catalog.triggers.sla_first_response_ultra.hint',
                'scope' => 'per_conversation',
            ],
            'sla_first_response_fast' => [
                'label_key' => 'overflowachievement::catalog.triggers.sla_first_response_fast.label',
                'hint_key' => 'overflowachievement::catalog.triggers.sla_first_response_fast.hint',
                'scope' => 'per_conversation',
            ],
            'sla_fast_reply_ultra' => [
                'label_key' => 'overflowachievement::catalog.triggers.sla_fast_reply_ultra.label',
                'hint_key' => 'overflowachievement::catalog.triggers.sla_fast_reply_ultra.hint',
                'scope' => 'daily',
            ],
            'sla_fast_reply' => [
                'label_key' => 'overflowachievement::catalog.triggers.sla_fast_reply.label',
                'hint_key' => 'overflowachievement::catalog.triggers.sla_fast_reply.hint',
                'scope' => 'daily',
            ],
            'sla_resolve_4h' => [
                'label_key' => 'overflowachievement::catalog.triggers.sla_resolve_4h.label',
                'hint_key' => 'overflowachievement::catalog.triggers.sla_resolve_4h.hint',
                'scope' => 'per_conversation',
            ],
            'sla_resolve_24h' => [
                'label_key' => 'overflowachievement::catalog.triggers.sla_resolve_24h.label',
                'hint_key' => 'overflowachievement::catalog.triggers.sla_resolve_24h.hint',
                'scope' => 'per_conversation',
            ],
            'streak_days' => [
                'label_key' => 'overflowachievement::catalog.triggers.streak_days.label',
                'hint_key' => 'overflowachievement::catalog.triggers.streak_days.hint',
                'scope' => 'lifetime',
            ],
            'xp_total' => [
                'label_key' => 'overflowachievement::catalog.triggers.xp_total.label',
                'hint_key' => 'overflowachievement::catalog.triggers.xp_total.hint',
                'scope' => 'lifetime',
            ],
            'actions_total' => [
                'label_key' => 'overflowachievement::catalog.triggers.actions_total.label',
                'hint_key' => 'overflowachievement::catalog.triggers.actions_total.hint',
                'scope' => 'lifetime',
            ],
        ];
    }

    public static function aliases(): array
    {
        return [
            'close' => 'close_conversation',
            'closed' => 'close_conversation',
            'closes' => 'close_conversation',
            'finish' => 'close_conversation',
            'finished' => 'close_conversation',
            'view_finish' => 'close_conversation',
            'conversation_close' => 'close_conversation',
            'conversation_closed' => 'close_conversation',
            'conversation_finish' => 'close_conversation',
            'conversation_finished' => 'close_conversation',

            'reply' => 'first_reply',
            'replies' => 'first_reply',
            'first_replies' => 'first_reply',
            'conversation_reply' => 'first_reply',

            'note' => 'note_added',
            'notes' => 'note_added',
            'internal_note' => 'note_added',
            'internal_notes' => 'note_added',
            'conversation_note' => 'note_added',
            'conversation_notes' => 'note_added',
            'notes_added' => 'note_added',

            'assign' => 'assigned',
            'assignment' => 'assigned',
            'conversation_assigned' => 'assigned',

            'merge' => 'merged',
            'merges' => 'merged',
            'conversation_merge' => 'merged',
            'conversation_merged' => 'merged',

            'move' => 'moved',
            'moves' => 'moved',
            'conversation_move' => 'moved',
            'conversation_moved' => 'moved',

            'forward' => 'forwarded',
            'forwards' => 'forwarded',
            'conversation_forward' => 'forwarded',
            'conversation_forwarded' => 'forwarded',

            'attachment' => 'attachment_added',
            'attachments' => 'attachment_added',

            'customer' => 'customer_created',
            'customers' => 'customer_created',

            'created' => 'conversation_created',
            'conversation_created_count' => 'conversation_created',

            'subject' => 'subject_changed',
            'subjects' => 'subject_changed',

            'customer_reply' => 'customer_replied',
            'customer_replies' => 'customer_replied',

            'pending' => 'set_pending',
            'spam' => 'marked_spam',
            'delete' => 'deleted_conversation',
            'deleted' => 'deleted_conversation',

            'cust_merge' => 'customer_merged',
            'customer_merge' => 'customer_merged',

            'focus' => 'focus_time',
        ];
    }

    public static function normalizeTrigger(string $trigger = ''): string
    {
        $trigger = trim((string) $trigger);
        if ($trigger === '') {
            return '';
        }

        if (isset(static::definitions()[$trigger])) {
            return $trigger;
        }

        $aliases = static::aliases();
        $lower = strtolower($trigger);

        if (isset($aliases[$lower])) {
            return $aliases[$lower];
        }

        return $trigger;
    }

    public static function labelFor(string $trigger, $locale = null): string
    {
        $trigger = static::normalizeTrigger($trigger);
        $definitions = static::definitions();
        if (!isset($definitions[$trigger])) {
            return static::humanize($trigger);
        }

        $key = $definitions[$trigger]['label_key'];
        foreach (LocaleCatalog::translationCandidates($locale) as $candidateLocale) {
            $text = app('translator')->get($key, [], $candidateLocale);
            if ((string) $text !== $key) {
                return (string) $text;
            }
        }

        return static::humanize($trigger);
    }

    public static function hintFor(string $trigger, $locale = null): string
    {
        $trigger = static::normalizeTrigger($trigger);
        $definitions = static::definitions();
        if (!isset($definitions[$trigger])) {
            return '';
        }

        $key = $definitions[$trigger]['hint_key'];
        foreach (LocaleCatalog::translationCandidates($locale) as $candidateLocale) {
            $text = app('translator')->get($key, [], $candidateLocale);
            if ((string) $text !== $key) {
                return (string) $text;
            }
        }

        return '';
    }

    public static function scopeFor(string $trigger): string
    {
        $trigger = static::normalizeTrigger($trigger);
        $definitions = static::definitions();
        if (!isset($definitions[$trigger])) {
            return 'lifetime';
        }

        return (string) $definitions[$trigger]['scope'];
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (static::definitions() as $trigger => $meta) {
            $labels[$trigger] = (string) __($meta['label_key']);
        }
        foreach (static::aliases() as $alias => $trigger) {
            if (!isset($labels[$alias]) && isset($labels[$trigger])) {
                $labels[$alias] = $labels[$trigger];
            }
        }
        return $labels;
    }

    public static function hints(): array
    {
        $hints = [];
        foreach (static::definitions() as $trigger => $meta) {
            $hints[$trigger] = (string) __($meta['hint_key']);
        }
        foreach (static::aliases() as $alias => $trigger) {
            if (!isset($hints[$alias]) && isset($hints[$trigger])) {
                $hints[$alias] = $hints[$trigger];
            }
        }
        return $hints;
    }

    public static function scopes(): array
    {
        $scopes = [];
        foreach (static::definitions() as $trigger => $meta) {
            $scopes[$trigger] = (string) $meta['scope'];
        }
        foreach (static::aliases() as $alias => $trigger) {
            if (!isset($scopes[$alias]) && isset($scopes[$trigger])) {
                $scopes[$alias] = $scopes[$trigger];
            }
        }
        return $scopes;
    }

    protected static function humanize(string $trigger): string
    {
        $trigger = trim((string) $trigger);
        if ($trigger === '') {
            return '';
        }

        $trigger = str_replace(['_', '-'], ' ', $trigger);
        $trigger = preg_replace('/\s+/', ' ', $trigger);

        return ucwords($trigger);
    }
}
