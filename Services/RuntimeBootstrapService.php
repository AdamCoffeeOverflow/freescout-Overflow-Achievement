<?php

namespace Modules\OverflowAchievement\Services;

use Modules\OverflowAchievement\Support\TriggerCatalog;

class RuntimeBootstrapService
{
    public function uiConfig(): array
    {
        return [
            'show_user_meta' => (bool)\Option::get('overflowachievement.ui.show_user_meta', 1),
            'confetti' => (bool)\Option::get('overflowachievement.ui.confetti', 1),
            'effect' => (string)\Option::get('overflowachievement.ui.effect', 'confetti'),
            'sound_enabled' => (bool)\Option::get('overflowachievement.ui.sound_enabled', 1),
            'sound_cooldown_ms' => (int)\Option::get('overflowachievement.ui.sound_cooldown_ms', 1200),
            'toast_theme' => (string)\Option::get('overflowachievement.ui.toast_theme', 'neon'),
            'toast_sticky' => (bool)\Option::get('overflowachievement.ui.toast_sticky', 0),
            'toast_duration_ms' => (int)\Option::get('overflowachievement.ui.toast_duration_ms', 10000),
            'toast_stack_enabled' => (bool)\Option::get('overflowachievement.ui.toast_stack_enabled', 0),
            'toast_stack_max' => (int)\Option::get('overflowachievement.ui.toast_stack_max', 2),
        ];
    }

    public function i18n(): array
    {
        return [
            'achievement' => __('Achievement'),
            'level_up' => __('Level Up!'),
            'trophy_unlocked' => __('Trophy Unlocked'),
            'achievements_unlocked' => __('Achievements Unlocked'),
            'achievements_count' => __('+:count achievements', ['count' => ':count']),
            'queued' => __('queued'),
            'unlocked_list' => __('Unlocked'),
            'unlocked' => __('Unlocked'),
            'locked' => __('Locked'),
            'preview_title' => __('Achievement Preview'),
            'dismiss' => __('Dismiss'),
            'view_trophies' => __('View trophies'),
            'more' => __('more'),
            'lv_short' => __('Lv'),
            'xp_short' => __('XP'),
            'to_next' => __('To next'),
            'progress' => __('Progress'),
            'scope_lifetime' => __('Lifetime'),
            'scope_daily' => __('Daily'),
            'scope_per_conversation' => __('Per conversation'),
            'rarity_common' => __('Common'),
            'rarity_rare' => __('Rare'),
            'rarity_epic' => __('Epic'),
            'rarity_legendary' => __('Legendary'),
            'preview_quote_auto' => __('Auto: a unique quote will be assigned.'),
            'checking' => __('Checking…'),
            'health_ok' => __('OK ✓ (user #:id)', ['id' => ':id']),
            'health_not_ok' => __('Not OK ✕ (:reason)', ['reason' => ':reason']),
            'health_error' => __('Error ✕ (HTTP :status)', ['status' => ':status']),
            'health_reason_disabled' => __('Disabled'),
            'health_reason_missing_tables' => __('Database tables are missing'),
            'health_reason_unreachable' => __('Unreachable'),
            'confirm_are_you_sure' => __('Are you sure?'),
            'preview_quote' => __('Preview quote'),
            'preview_author' => __('Overflow Achievement'),
        ];
    }

    public function payload(): array
    {
        return [
            'enabled' => (bool)\Option::get('overflowachievement.enabled', config('overflowachievement.enabled') ? 1 : 0),
            'urls' => [
                'unseen' => route('overflowachievement.unseen'),
                'mark_seen' => route('overflowachievement.mark_seen'),
                'bootstrap' => route('overflowachievement.bootstrap'),
                'achievements' => route('overflowachievement.achievements'),
                'health' => route('overflowachievement.health'),
            ],
            'ui' => $this->uiConfig(),
            'i18n' => $this->i18n(),
            'triggers' => [
                'labels' => TriggerCatalog::labels(),
                'hints' => TriggerCatalog::hints(),
                'scopes' => TriggerCatalog::scopes(),
                'scope_labels' => [
                    'lifetime' => __('Lifetime'),
                    'daily' => __('Daily'),
                    'per_conversation' => __('Per conversation'),
                ],
            ],
        ];
    }
}
