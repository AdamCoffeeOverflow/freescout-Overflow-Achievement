<?php

namespace Modules\OverflowAchievement\Support;

class AchievementCatalog
{
    public static function translationKey(string $achievementKey, string $field): string
    {
        return 'overflowachievement::achievements.' . $achievementKey . '.' . $field;
    }

    public static function translateField($value, string $achievementKey = '', string $field = '', $trigger = null, $threshold = null): string
    {
        $value = trim((string) $value);
        $trigger = TriggerCatalog::normalizeTrigger((string) $trigger);
        $threshold = (int) $threshold;

        $embedded = static::translationInfoFromValue($value);
        $targetKey = trim($achievementKey) !== '' ? trim($achievementKey) : (string) ($embedded['key'] ?? '');
        $targetField = trim($field) !== '' ? trim($field) : (string) ($embedded['field'] ?? '');

        if ($value !== '') {
            $translated = __($value);
            if ((string) $translated !== $value) {
                return (string) $translated;
            }

            if (!static::looksLikeTranslationKey($value)) {
                if ($targetKey !== '' && $targetField !== '' && static::matchesDefaultEnglish($value, $targetKey, $targetField, $trigger, $threshold)) {
                    $canonical = static::translatedCanonicalFor($targetKey, $targetField, $trigger, $threshold);
                    if ($canonical !== null) {
                        return $canonical;
                    }
                }

                return $value;
            }
        }

        if ($targetKey !== '' && $targetField !== '') {
            $canonical = static::translatedCanonicalFor($targetKey, $targetField, $trigger, $threshold);
            if ($canonical !== null) {
                return $canonical;
            }

            $generated = static::generatedTextFor($targetField, $targetKey, $trigger, $threshold);
            if ($generated !== null) {
                return $generated;
            }
        }

        if ($targetField !== '') {
            $generated = static::generatedTextFor($targetField, $targetKey, $trigger, $threshold);
            if ($generated !== null) {
                return $generated;
            }
        }

        return $value;
    }

    public static function translatedCanonical(string $achievementKey, string $field, $locale = null): ?string
    {
        $key = static::translationKey($achievementKey, $field);

        foreach (LocaleCatalog::translationCandidates($locale) as $candidateLocale) {
            $text = app('translator')->get($key, [], $candidateLocale);
            if ((string) $text !== $key) {
                return (string) $text;
            }
        }

        return null;
    }

    public static function translatedCanonicalFor(string $achievementKey, string $field, string $trigger = '', int $threshold = 0, $locale = null): ?string
    {
        $trigger = TriggerCatalog::normalizeTrigger($trigger);
        $candidates = [];
        foreach ([
            trim($achievementKey),
            static::canonicalKey($achievementKey, $trigger, $threshold),
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && !in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            $text = static::translatedCanonical($candidate, $field, $locale);
            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }

    public static function canonicalKey(string $achievementKey = '', string $trigger = '', int $threshold = 0): string
    {
        $achievementKey = trim((string) $achievementKey);
        $trigger = TriggerCatalog::normalizeTrigger((string) $trigger);
        $threshold = max(0, (int) $threshold);

        $exact = [
            'notes_1' => 'first_note',
            'note_1' => 'first_note',
            'assigned_1' => 'first_assign',
            'assign_1' => 'first_assign',
            'closes_1' => 'first_close',
            'close_1' => 'first_close',
            'finish_1' => 'first_close',
            'finished_1' => 'first_close',
            'replies_1' => 'hello_world',
            'reply_1' => 'hello_world',
            'first_replies_1' => 'hello_world',
            'moves_1' => 'first_move',
            'move_1' => 'first_move',
            'forwards_1' => 'first_forward',
            'forward_1' => 'first_forward',
            'attachments_1' => 'first_attachment',
            'attachment_1' => 'first_attachment',
            'customers_1' => 'first_customer',
            'customer_1' => 'first_customer',
            'created_1' => 'first_created',
            'subjects_1' => 'first_subject_edit',
            'subject_1' => 'first_subject_edit',
            'reply_sent_1' => 'first_reply_sent',
            'customer_reply_1' => 'first_customer_reply',
            'pending_1' => 'first_pending',
            'spam_1' => 'first_spam',
            'delete_1' => 'first_delete',
            'cust_merge_1' => 'first_customer_merge',
            'focus_10' => 'first_focus_10',
        ];
        if ($achievementKey !== '' && isset($exact[$achievementKey])) {
            return $exact[$achievementKey];
        }

        $patternMap = [
            '/^overflowachievement::achievements\.([^.]+)\.(?:title|description)$/i' => null,
            '/^notes?_(\d+)$/i' => 'note_added',
            '/^internal_notes?_(\d+)$/i' => 'note_added',
            '/^assigned_(\d+)$/i' => 'assigned',
            '/^assigns?_(\d+)$/i' => 'assigned',
            '/^closes?_(\d+)$/i' => 'close_conversation',
            '/^finish(?:ed|es)?_(\d+)$/i' => 'close_conversation',
            '/^view_finish(?:ed|es)?_(\d+)$/i' => 'close_conversation',
            '/^(?:conversation_)?close(?:d|s)?_(\d+)$/i' => 'close_conversation',
            '/^(?:first_)?repl(?:y|ies)_(\d+)$/i' => 'first_reply',
            '/^moves?_(\d+)$/i' => 'moved',
            '/^(?:conversation_)?move(?:d|s)?_(\d+)$/i' => 'moved',
            '/^forwards?_(\d+)$/i' => 'forwarded',
            '/^(?:conversation_)?forward(?:ed|s)?_(\d+)$/i' => 'forwarded',
            '/^attachments?_(\d+)$/i' => 'attachment_added',
            '/^customers?_(\d+)$/i' => 'customer_created',
            '/^created_(\d+)$/i' => 'conversation_created',
            '/^subjects?_(\d+)$/i' => 'subject_changed',
            '/^reply_sent_(\d+)$/i' => 'reply_sent',
            '/^customer_reply_(\d+)$/i' => 'customer_replied',
            '/^pending_(\d+)$/i' => 'set_pending',
            '/^spam_(\d+)$/i' => 'marked_spam',
            '/^delete_(\d+)$/i' => 'deleted_conversation',
            '/^merge(?:d|s)?_(\d+)$/i' => 'merged',
            '/^(?:conversation_)?merge(?:d|s)?_(\d+)$/i' => 'merged',
            '/^cust_merge_(\d+)$/i' => 'customer_merged',
            '/^focus_(\d+)$/i' => 'focus_time',
        ];
        if ($achievementKey !== '') {
            foreach ($patternMap as $pattern => $mappedTrigger) {
                if (!preg_match($pattern, $achievementKey, $m)) {
                    continue;
                }

                if ($mappedTrigger === null) {
                    $nestedKey = trim((string) ($m[1] ?? ''));
                    if ($nestedKey !== '') {
                        return static::canonicalKey($nestedKey, $trigger, $threshold);
                    }
                    break;
                }

                $candidate = static::canonicalKeyForTrigger($mappedTrigger, (int) ($m[1] ?? 0));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        if ($trigger !== '') {
            $candidate = static::canonicalKeyForTrigger($trigger, $threshold);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $achievementKey;
    }

    public static function localizeText($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return (string) __($value);
    }

    protected static function canonicalKeyForTrigger(string $trigger, int $threshold): string
    {
        $trigger = TriggerCatalog::normalizeTrigger($trigger);
        $threshold = max(0, (int) $threshold);

        switch ($trigger) {
            case 'close_conversation':
                return $threshold <= 1 ? 'first_close' : 'closer_' . $threshold;
            case 'first_reply':
                return $threshold <= 1 ? 'hello_world' : 'responder_' . $threshold;
            case 'note_added':
                return $threshold <= 1 ? 'first_note' : 'notekeeper_' . $threshold;
            case 'assigned':
                return $threshold <= 1 ? 'first_assign' : 'owner_' . $threshold;
            case 'merged':
                return 'merger_' . $threshold;
            case 'moved':
                return $threshold <= 1 ? 'first_move' : 'mover_' . $threshold;
            case 'forwarded':
                return $threshold <= 1 ? 'first_forward' : 'forwarder_' . $threshold;
            case 'attachment_added':
                return $threshold <= 1 ? 'first_attachment' : 'attachments_' . $threshold;
            case 'customer_created':
                return $threshold <= 1 ? 'first_customer' : 'customers_' . $threshold;
            case 'customer_updated':
                return 'profile_polish_' . $threshold;
            case 'conversation_created':
                return $threshold <= 1 ? 'first_created' : 'creator_' . $threshold;
            case 'subject_changed':
                return $threshold <= 1 ? 'first_subject_edit' : 'subject_' . $threshold;
            case 'reply_sent':
                return $threshold <= 1 ? 'first_reply_sent' : 'reply_sent_' . $threshold;
            case 'customer_replied':
                return $threshold <= 1 ? 'first_customer_reply' : 'customer_reply_' . $threshold;
            case 'set_pending':
                return $threshold <= 1 ? 'first_pending' : 'pending_' . $threshold;
            case 'marked_spam':
                return $threshold <= 1 ? 'first_spam' : 'spam_' . $threshold;
            case 'deleted_conversation':
                return $threshold <= 1 ? 'first_delete' : 'delete_' . $threshold;
            case 'customer_merged':
                return $threshold <= 1 ? 'first_customer_merge' : 'cust_merge_' . $threshold;
            case 'focus_time':
                return $threshold <= 10 ? 'first_focus_10' : 'focus_' . $threshold;
            case 'streak_days':
                return 'streak_' . $threshold;
            case 'xp_total':
                return 'xp_' . $threshold;
            case 'actions_total':
                return 'actions_' . $threshold;
            case 'sla_first_response_ultra':
                return 'sla_first_response_ultra_' . $threshold;
            case 'sla_first_response_fast':
                return 'sla_first_response_fast_' . $threshold;
            case 'sla_fast_reply_ultra':
                return 'sla_fast_reply_ultra_' . $threshold;
            case 'sla_fast_reply':
                return 'sla_fast_reply_' . $threshold;
            case 'sla_resolve_4h':
                return 'sla_resolve_4h_' . $threshold;
            case 'sla_resolve_24h':
                return 'sla_resolve_24h_' . $threshold;
            default:
                return '';
        }
    }

    protected static function matchesDefaultEnglish(string $value, string $achievementKey, string $field, string $trigger = '', int $threshold = 0): bool
    {
        $english = static::translatedCanonicalFor($achievementKey, $field, $trigger, $threshold, 'en');
        if ($english === null) {
            return false;
        }

        return trim($english) === trim($value);
    }

    protected static function looksLikeTranslationKey(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9_\-]+(?:::[a-z0-9_\-]+)?(?:\.[a-z0-9_\-]+)+$/i', $value);
    }

    protected static function translationInfoFromValue(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if (preg_match('/^overflowachievement::achievements\.([^.]+)\.(title|description)$/i', $value, $m)) {
            return [
                'key' => trim((string) ($m[1] ?? '')),
                'field' => trim((string) ($m[2] ?? '')),
            ];
        }

        return [];
    }

    protected static function generatedTextFor(string $field, string $achievementKey, string $trigger, int $threshold, $locale = null): ?string
    {
        $field = trim((string) $field);
        if ($field === '') {
            return null;
        }

        $trigger = TriggerCatalog::normalizeTrigger($trigger);
        if ($trigger === '') {
            $trigger = static::inferTriggerFromKey($achievementKey);
        }

        if ($trigger === '') {
            return null;
        }

        $label = TriggerCatalog::labelFor($trigger, $locale);
        $label = trim((string) $label);
        if ($label === '') {
            return null;
        }

        if ($field === 'title') {
            if ($threshold > 1) {
                return $label . ' x' . $threshold;
            }

            return $label;
        }

        if ($field === 'description') {
            if ($threshold > 0) {
                return $label . ' ≥ ' . $threshold;
            }

            $hint = TriggerCatalog::hintFor($trigger, $locale);
            if ($hint !== '') {
                return $hint;
            }

            return $label;
        }

        return null;
    }

    protected static function inferTriggerFromKey(string $achievementKey): string
    {
        $achievementKey = trim((string) $achievementKey);
        if ($achievementKey === '') {
            return '';
        }

        $patterns = [
            '/^first_close$/i' => 'close_conversation',
            '/^closer_\d+$/i' => 'close_conversation',
            '/^hello_world$/i' => 'first_reply',
            '/^responder_\d+$/i' => 'first_reply',
            '/^first_note$/i' => 'note_added',
            '/^notekeeper_\d+$/i' => 'note_added',
            '/^first_assign$/i' => 'assigned',
            '/^owner_\d+$/i' => 'assigned',
            '/^merger_\d+$/i' => 'merged',
            '/^first_move$/i' => 'moved',
            '/^mover_\d+$/i' => 'moved',
            '/^first_forward$/i' => 'forwarded',
            '/^forwarder_\d+$/i' => 'forwarded',
            '/^first_attachment$/i' => 'attachment_added',
            '/^attachments_\d+$/i' => 'attachment_added',
            '/^first_customer$/i' => 'customer_created',
            '/^customers_\d+$/i' => 'customer_created',
            '/^profile_polish_\d+$/i' => 'customer_updated',
            '/^first_created$/i' => 'conversation_created',
            '/^creator_\d+$/i' => 'conversation_created',
            '/^first_subject_edit$/i' => 'subject_changed',
            '/^subject_\d+$/i' => 'subject_changed',
            '/^first_reply_sent$/i' => 'reply_sent',
            '/^reply_sent_\d+$/i' => 'reply_sent',
            '/^first_customer_reply$/i' => 'customer_replied',
            '/^customer_reply_\d+$/i' => 'customer_replied',
            '/^first_pending$/i' => 'set_pending',
            '/^pending_\d+$/i' => 'set_pending',
            '/^first_spam$/i' => 'marked_spam',
            '/^spam_\d+$/i' => 'marked_spam',
            '/^first_delete$/i' => 'deleted_conversation',
            '/^delete_\d+$/i' => 'deleted_conversation',
            '/^first_customer_merge$/i' => 'customer_merged',
            '/^cust_merge_\d+$/i' => 'customer_merged',
            '/^first_focus_10$/i' => 'focus_time',
            '/^focus_\d+$/i' => 'focus_time',
            '/^streak_\d+$/i' => 'streak_days',
            '/^xp_\d+$/i' => 'xp_total',
            '/^actions_\d+$/i' => 'actions_total',
            '/^sla_first_response_ultra_\d+$/i' => 'sla_first_response_ultra',
            '/^sla_first_response_fast_\d+$/i' => 'sla_first_response_fast',
            '/^sla_fast_reply_ultra_\d+$/i' => 'sla_fast_reply_ultra',
            '/^sla_fast_reply_\d+$/i' => 'sla_fast_reply',
            '/^sla_resolve_4h_\d+$/i' => 'sla_resolve_4h',
            '/^sla_resolve_24h_\d+$/i' => 'sla_resolve_24h',
        ];

        foreach ($patterns as $pattern => $trigger) {
            if (preg_match($pattern, $achievementKey)) {
                return $trigger;
            }
        }

        return '';
    }
}
