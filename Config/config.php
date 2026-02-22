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
        'enabled' => true,
        'baseline_achievements' => 60,
        // +0.5% XP curve per extra active trophy above baseline
        'step' => 0.005,
        // Clamp factor
        'min' => 0.90,
        'max' => 1.60,
    ],



    // Friendly trigger labels and hints (used in the UI).
    'triggers' => [
        'labels' => [
            'close_conversation' => __('Closed conversations'),
            'first_reply'        => __('First replies'),
            'note_added'         => __('Internal notes added'),
            'assigned'           => __('Conversations assigned'),
            'merged'             => __('Conversations merged'),
            'moved'              => __('Conversations moved'),
            'forwarded'          => __('Conversations forwarded'),
            'attachment_added'   => __('Attachments added'),
            'customer_created'   => __('Customers created'),
            'customer_updated'   => __('Customer updates'),
            'conversation_created' => __('Created conversations'),
            'subject_changed'      => __('Subject edits'),
            'reply_sent'           => __('Replies sent'),
            'customer_replied'     => __('Customer replies received'),
            'set_pending'          => __('Set to pending'),
            'marked_spam'          => __('Marked as spam'),
            'deleted_conversation' => __('Deleted conversations'),
            'customer_merged'      => __('Customers merged'),
            'focus_time'           => __('Focus minutes'),
            'sla_first_response_ultra' => __('Lightning First Response'),
            'sla_first_response_fast'  => __('Fast First Response'),
            'sla_fast_reply_ultra'     => __('Lightning Follow-up'),
            'sla_fast_reply'           => __('Fast Follow-up'),
            'sla_resolve_4h'           => __('Rapid Resolution'),
            'sla_resolve_24h'          => __('Same-day Resolution'),

            'streak_days'        => __('Active days streak'),
            'xp_total'           => __('Total XP'),
            'actions_total'      => __('Total actions'),
        ],
        'hints' => [
            'close_conversation' => __('Resolve a conversation and close it.'),
            'first_reply'        => __('Be the first to reply in a conversation.'),
            'note_added'         => __('Add an internal note to help your team.'),
            'assigned'           => __('Assign a conversation to yourself or a teammate.'),
            'merged'             => __('Merge duplicate conversations.'),
            'moved'              => __('Move a conversation to another mailbox.'),
            'forwarded'          => __('Forward a conversation when needed.'),
            'attachment_added'   => __('Attach a file to a reply or note.'),
            'customer_created'   => __('Create a new customer profile.'),
            'customer_updated'   => __('Update customer details.'),
            'conversation_created' => __('Start a new conversation (outbound/proactive message).'),
            'subject_changed'      => __('Edit the subject to make the ticket clearer.'),
            'reply_sent'           => __('Send a reply to a customer.'),
            'customer_replied'     => __('A customer replied in a conversation assigned to you.'),
            'set_pending'          => __('Set a conversation status to pending.'),
            'marked_spam'          => __('Mark a conversation as spam.'),
            'deleted_conversation' => __('Delete a conversation (use carefully).'),
            'customer_merged'      => __('Merge duplicate customer profiles.'),
            'focus_time'           => __('Spend focused time viewing conversations.'),
            'sla_first_response_ultra' => __('Send the first reply within :n minutes.', ['n' => 5]),
            'sla_first_response_fast'  => __('Send the first reply within :n minutes.', ['n' => 30]),
            'sla_fast_reply_ultra'     => __('Reply within :n minutes of a customer message.', ['n' => 5]),
            'sla_fast_reply'           => __('Reply within :n minutes of a customer message.', ['n' => 30]),
            'sla_resolve_4h'           => __('Close a ticket within :n hours of creation.', ['n' => 4]),
            'sla_resolve_24h'          => __('Close a ticket within :n hours of creation.', ['n' => 24]),

            'streak_days'        => __('Be active on consecutive days.'),
            'xp_total'           => __('Earn XP from any activity.'),
            'actions_total'      => __('Perform tracked actions across the helpdesk.'),
        ],

        // Scope badges shown in the trophy details modal.
        // Values: lifetime | daily | per_conversation
        'scopes' => [
            'close_conversation' => 'per_conversation',
            'first_reply'        => 'per_conversation',
            'note_added'         => 'lifetime',
            'assigned'           => 'daily',
            'merged'             => 'per_conversation',
            'moved'              => 'daily',
            'forwarded'          => 'per_conversation',
            'attachment_added'   => 'lifetime',
            'customer_created'   => 'lifetime',
            'customer_updated'   => 'lifetime',
            'conversation_created' => 'per_conversation',
            'subject_changed'      => 'daily',
            'reply_sent'           => 'daily',
            'customer_replied'     => 'daily',
            'set_pending'          => 'daily',
            'marked_spam'          => 'per_conversation',
            'deleted_conversation' => 'per_conversation',
            'customer_merged'      => 'lifetime',
            'focus_time'           => 'daily',
            'sla_first_response_ultra' => 'per_conversation',
            'sla_first_response_fast'  => 'per_conversation',
            'sla_fast_reply_ultra'     => 'daily',
            'sla_fast_reply'           => 'daily',
            'sla_resolve_4h'           => 'per_conversation',
            'sla_resolve_24h'          => 'per_conversation',

            'streak_days'        => 'lifetime',
            'xp_total'           => 'lifetime',
            'actions_total'      => 'lifetime',
        ],
    ],

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
    ],

    // Quote display
    'quotes' => [
        'max_length' => 140,
    ],
];
