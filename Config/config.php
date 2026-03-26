<?php

return [
    // Master switch (actual runtime value comes from Option: overflowachievement.enabled)
    'enabled' => true,

    // XP rewards (defaults; runtime values are overrideable in Settings)
    'xp' => [
        'close_conversation' => 25,
        'first_reply'        => 15,

        // “Work feels rewarding” events
        'note_added'         => 8,
        'assigned'           => 6,
        'merged'             => 20,
        'moved'              => 5,
        'forwarded'          => 12,
        'attachment_added'   => 5,
        'customer_created'   => 10,
        'customer_updated'   => 4,
        // Proactive / quality-of-life
        'conversation_created' => 10,
        'subject_changed'      => 2,

        // More functional triggers
        'reply_sent'           => 3,
        'customer_replied'     => 1,
        'set_pending'          => 2,
        'marked_spam'          => 5,
        'deleted_conversation' => 5,
        'customer_merged'      => 12,

        // Focus time (XP per minute)
        'focus_time'           => 1,

        // SLA / quality bonuses
        'sla_first_response_ultra' => 12,
        'sla_first_response_fast'  => 8,
        'sla_fast_reply_ultra'     => 6,
        'sla_fast_reply'           => 4,
        'sla_resolve_4h'           => 12,
        'sla_resolve_24h'          => 8,
    ],

    // Anti-grind
    'caps' => [
        'daily_xp' => 800,
    ],

    // Cooldowns / uniqueness
    'limits' => [
        'close_once_per_conversation'       => true,
        'first_reply_once_per_conversation' => true,

        // Notes and attachments can be spammy; cap per conversation per day.
        'note_max_per_conversation_per_day'       => 3,
        'attachment_max_per_conversation_per_day' => 3,

        // Customer profile edits can be spammy; cap per day.
        'customer_updates_max_per_day' => 25,

        // Replies can be spammy; cap per conversation per day.
        'reply_max_per_conversation_per_day' => 6,

        // Incoming customer replies can be noisy; cap per conversation per day.
        'customer_reply_max_per_conversation_per_day' => 6,

        // Focus time is time-based; cap events (derived from minutes caps).
        'focus_max_minutes_per_event' => 10,
        'focus_max_minutes_per_conversation_per_day' => 30,
    ],

    // SLA thresholds (minutes/hours)
    'sla' => [
        'first_response_ultra_minutes' => 5,
        'first_response_fast_minutes'  => 30,
        'fast_reply_ultra_minutes'     => 5,
        'fast_reply_minutes'           => 30,
        'resolve_4h_hours'             => 4,
        'resolve_24h_hours'            => 24,
    ],

    // Dynamic leveling scaling (keeps progression sane as trophies/triggers increase)
    'levels_dynamic' => [
        'enabled' => false,
        'baseline_achievements' => 60,
        // +0.5% XP curve per extra active trophy above baseline
        'step' => 0.005,
        // Clamp factor
        'min' => 0.90,
        'max' => 1.60,
    ],


    // Trigger labels, hints, and scopes are resolved at runtime in Support\TriggerCatalog.

    // UI toggles
    'show_leaderboard' => true,
    'ui' => [
        'confetti' => true,
        // Celebration effect used on big moments: confetti | fireworks | off
        'effect' => 'confetti',
        // Toast look: neon | dark | classic
        'toast_theme' => 'neon',
        // Optional sound feedback for popups (browser autoplay rules apply).
        'sound_enabled' => false,
        // Minimum time between sounds (prevents burst spam).
        'sound_cooldown_ms' => 1200,
        // Show the small level + XP widget in the top-right user menu.
        'show_user_meta' => true,
        // Toast queue mode. Default is single-toast (safer for busy helpdesks).
        // When enabled, show up to toast_stack_max achievement toasts at once.
        'toast_stack_enabled' => false,
        'toast_stack_max' => 2,

        // Leaderboard UI
        // How many items to show in the "Recent Trophies" column.
        'recent_trophies_limit' => 10,
    ],

    // Quote display
    'quotes' => [
        'max_length' => 140,
    ],
];
