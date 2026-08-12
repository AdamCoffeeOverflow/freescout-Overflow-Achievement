<?php

namespace Modules\OverflowAchievement\Providers\Concerns;

trait RegistersOverflowAchievementSettings
{
    protected function registerSettings(): void
    {
        // Add a new section under Manage > Settings.
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['achievement'] = [
                'title' => __('Achievement'),
                // Settings sidebar uses Bootstrap glyphicons (not FontAwesome).
                'icon'  => 'certificate',
                'order' => 650,
            ];
            return $sections;
        });

        // Tell FreeScout which view to render for our settings section.
        \Eventy::addFilter('settings.view', function ($view, $section) {
            if ($section === 'achievement') {
                return 'overflowachievement::settings/index';
            }
            return $view;
        }, 20, 2);

        // Validate every persisted setting before FreeScout writes options.
        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section !== 'achievement') {
                return $params;
            }

            $rules = [
                'settings.overflowachievement\.enabled' => 'required|in:0,1',
                'settings.overflowachievement\.show_leaderboard' => 'required|in:0,1',
                'settings.overflowachievement\.ui\.show_user_meta' => 'required|in:0,1',
                'settings.overflowachievement\.ui\.confetti' => 'required|in:0,1',
                'settings.overflowachievement\.ui\.sound_enabled' => 'required|in:0,1',
                'settings.overflowachievement\.ui\.toast_sticky' => 'required|in:0,1',
                'settings.overflowachievement\.ui\.toast_stack_enabled' => 'required|in:0,1',
                'settings.overflowachievement\.ui\.effect' => 'required|in:confetti,fireworks,off',
                'settings.overflowachievement\.ui\.toast_theme' => 'required|in:neon,dark,classic',
                'settings.overflowachievement\.ui\.sound_cooldown_ms' => 'required|integer|min:200|max:5000',
                'settings.overflowachievement\.ui\.toast_duration_ms' => 'required|integer|min:1000|max:120000',
                'settings.overflowachievement\.ui\.toast_stack_max' => 'required|integer|min:1|max:5',
                'settings.overflowachievement\.quotes\.mailbox_rules' => 'nullable|json|max:65535',
            ];

            $integerKeys = [
                'caps.daily_xp',
                'xp.close_conversation', 'xp.first_reply', 'xp.note_added', 'xp.assigned',
                'xp.merged', 'xp.moved', 'xp.forwarded', 'xp.attachment_added',
                'xp.customer_created', 'xp.customer_updated', 'xp.conversation_created',
                'xp.subject_changed', 'xp.reply_sent', 'xp.customer_replied', 'xp.set_pending',
                'xp.marked_spam', 'xp.deleted_conversation', 'xp.customer_merged', 'xp.focus_time',
                'xp.sla_first_response_ultra', 'xp.sla_first_response_fast',
                'xp.sla_fast_reply_ultra', 'xp.sla_fast_reply',
                'xp.sla_resolve_4h', 'xp.sla_resolve_24h',
                'sla.first_response_ultra_minutes', 'sla.first_response_fast_minutes',
                'sla.fast_reply_ultra_minutes', 'sla.fast_reply_minutes',
                'sla.resolve_4h_hours', 'sla.resolve_24h_hours',
                'limits.note_max_per_conversation_per_day',
                'limits.attachment_max_per_conversation_per_day',
                'limits.customer_updates_max_per_day',
                'limits.reply_max_per_conversation_per_day',
                'limits.customer_reply_max_per_conversation_per_day',
                'limits.focus_max_minutes_per_event',
                'limits.focus_max_minutes_per_conversation_per_day',
            ];

            foreach ($integerKeys as $key) {
                $rules['settings.overflowachievement\.'.str_replace('.', '\.', $key)] = 'required|integer|min:0|max:1000000';
            }

            $params['validator_rules'] = array_merge($params['validator_rules'] ?? [], $rules);
            return $params;
        }, 20, 2);

        // Provide module option list for SettingsController save().
        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section !== 'achievement') {
                return $settings;
            }

            return [
                'overflowachievement.enabled' => [
                    'value' => \Option::get('overflowachievement.enabled', config('overflowachievement.enabled') ? 1 : 0),
                ],
                'overflowachievement.caps.daily_xp' => [
                    'value' => \Option::get('overflowachievement.caps.daily_xp', (int)config('overflowachievement.caps.daily_xp', 800)),
                ],

                // XP values
                'overflowachievement.xp.close_conversation' => [
                    'value' => \Option::get('overflowachievement.xp.close_conversation', (int)config('overflowachievement.xp.close_conversation', 25)),
                ],
                'overflowachievement.xp.first_reply' => [
                    'value' => \Option::get('overflowachievement.xp.first_reply', (int)config('overflowachievement.xp.first_reply', 15)),
                ],
                'overflowachievement.xp.note_added' => [
                    'value' => \Option::get('overflowachievement.xp.note_added', (int)config('overflowachievement.xp.note_added', 8)),
                ],
                'overflowachievement.xp.assigned' => [
                    'value' => \Option::get('overflowachievement.xp.assigned', (int)config('overflowachievement.xp.assigned', 6)),
                ],
                'overflowachievement.xp.merged' => [
                    'value' => \Option::get('overflowachievement.xp.merged', (int)config('overflowachievement.xp.merged', 20)),
                ],
                'overflowachievement.xp.moved' => [
                    'value' => \Option::get('overflowachievement.xp.moved', (int)config('overflowachievement.xp.moved', 5)),
                ],
                'overflowachievement.xp.forwarded' => [
                    'value' => \Option::get('overflowachievement.xp.forwarded', (int)config('overflowachievement.xp.forwarded', 12)),
                ],
                'overflowachievement.xp.attachment_added' => [
                    'value' => \Option::get('overflowachievement.xp.attachment_added', (int)config('overflowachievement.xp.attachment_added', 5)),
                ],
                'overflowachievement.xp.customer_created' => [
                    'value' => \Option::get('overflowachievement.xp.customer_created', (int)config('overflowachievement.xp.customer_created', 10)),
                ],
                'overflowachievement.xp.customer_updated' => [
                    'value' => \Option::get('overflowachievement.xp.customer_updated', (int)config('overflowachievement.xp.customer_updated', 4)),
                ],

                'overflowachievement.xp.conversation_created' => [
                    'value' => \Option::get('overflowachievement.xp.conversation_created', (int)config('overflowachievement.xp.conversation_created', 10)),
                ],
                'overflowachievement.xp.subject_changed' => [
                    'value' => \Option::get('overflowachievement.xp.subject_changed', (int)config('overflowachievement.xp.subject_changed', 2)),
                ],

                'overflowachievement.xp.reply_sent' => [
                    'value' => \Option::get('overflowachievement.xp.reply_sent', (int)config('overflowachievement.xp.reply_sent', 3)),
                ],
                'overflowachievement.xp.customer_replied' => [
                    'value' => \Option::get('overflowachievement.xp.customer_replied', (int)config('overflowachievement.xp.customer_replied', 1)),
                ],
                'overflowachievement.xp.set_pending' => [
                    'value' => \Option::get('overflowachievement.xp.set_pending', (int)config('overflowachievement.xp.set_pending', 2)),
                ],
                'overflowachievement.xp.marked_spam' => [
                    'value' => \Option::get('overflowachievement.xp.marked_spam', (int)config('overflowachievement.xp.marked_spam', 5)),
                ],
                'overflowachievement.xp.deleted_conversation' => [
                    'value' => \Option::get('overflowachievement.xp.deleted_conversation', (int)config('overflowachievement.xp.deleted_conversation', 5)),
                ],
                'overflowachievement.xp.customer_merged' => [
                    'value' => \Option::get('overflowachievement.xp.customer_merged', (int)config('overflowachievement.xp.customer_merged', 12)),
                ],
                'overflowachievement.xp.focus_time' => [
                    'value' => \Option::get('overflowachievement.xp.focus_time', (int)config('overflowachievement.xp.focus_time', 1)),
                ],

                // SLA bonuses
                'overflowachievement.xp.sla_first_response_ultra' => [
                    'value' => \Option::get('overflowachievement.xp.sla_first_response_ultra', (int)config('overflowachievement.xp.sla_first_response_ultra', 12)),
                ],
                'overflowachievement.xp.sla_first_response_fast' => [
                    'value' => \Option::get('overflowachievement.xp.sla_first_response_fast', (int)config('overflowachievement.xp.sla_first_response_fast', 8)),
                ],
                'overflowachievement.xp.sla_fast_reply_ultra' => [
                    'value' => \Option::get('overflowachievement.xp.sla_fast_reply_ultra', (int)config('overflowachievement.xp.sla_fast_reply_ultra', 6)),
                ],
                'overflowachievement.xp.sla_fast_reply' => [
                    'value' => \Option::get('overflowachievement.xp.sla_fast_reply', (int)config('overflowachievement.xp.sla_fast_reply', 4)),
                ],
                'overflowachievement.xp.sla_resolve_4h' => [
                    'value' => \Option::get('overflowachievement.xp.sla_resolve_4h', (int)config('overflowachievement.xp.sla_resolve_4h', 12)),
                ],
                'overflowachievement.xp.sla_resolve_24h' => [
                    'value' => \Option::get('overflowachievement.xp.sla_resolve_24h', (int)config('overflowachievement.xp.sla_resolve_24h', 8)),
                ],

                // Quote mailbox rules (JSON).
                'overflowachievement.quotes.mailbox_rules' => [
                    'value' => \Option::get('overflowachievement.quotes.mailbox_rules', ''),
                ],

                // SLA thresholds
                'overflowachievement.sla.first_response_ultra_minutes' => [
                    'value' => \Option::get('overflowachievement.sla.first_response_ultra_minutes', (int)config('overflowachievement.sla.first_response_ultra_minutes', 5)),
                ],
                'overflowachievement.sla.first_response_fast_minutes' => [
                    'value' => \Option::get('overflowachievement.sla.first_response_fast_minutes', (int)config('overflowachievement.sla.first_response_fast_minutes', 30)),
                ],
                'overflowachievement.sla.fast_reply_ultra_minutes' => [
                    'value' => \Option::get('overflowachievement.sla.fast_reply_ultra_minutes', (int)config('overflowachievement.sla.fast_reply_ultra_minutes', 5)),
                ],
                'overflowachievement.sla.fast_reply_minutes' => [
                    'value' => \Option::get('overflowachievement.sla.fast_reply_minutes', (int)config('overflowachievement.sla.fast_reply_minutes', 30)),
                ],
                'overflowachievement.sla.resolve_4h_hours' => [
                    'value' => \Option::get('overflowachievement.sla.resolve_4h_hours', (int)config('overflowachievement.sla.resolve_4h_hours', 4)),
                ],
                'overflowachievement.sla.resolve_24h_hours' => [
                    'value' => \Option::get('overflowachievement.sla.resolve_24h_hours', (int)config('overflowachievement.sla.resolve_24h_hours', 24)),
                ],

                // Limits
                'overflowachievement.limits.note_max_per_conversation_per_day' => [
                    'value' => \Option::get('overflowachievement.limits.note_max_per_conversation_per_day', (int)config('overflowachievement.limits.note_max_per_conversation_per_day', 3)),
                ],
                'overflowachievement.limits.attachment_max_per_conversation_per_day' => [
                    'value' => \Option::get('overflowachievement.limits.attachment_max_per_conversation_per_day', (int)config('overflowachievement.limits.attachment_max_per_conversation_per_day', 3)),
                ],
                'overflowachievement.limits.customer_updates_max_per_day' => [
                    'value' => \Option::get('overflowachievement.limits.customer_updates_max_per_day', (int)config('overflowachievement.limits.customer_updates_max_per_day', 25)),
                ],

                'overflowachievement.limits.reply_max_per_conversation_per_day' => [
                    'value' => \Option::get('overflowachievement.limits.reply_max_per_conversation_per_day', (int)config('overflowachievement.limits.reply_max_per_conversation_per_day', 6)),
                ],
                'overflowachievement.limits.customer_reply_max_per_conversation_per_day' => [
                    'value' => \Option::get('overflowachievement.limits.customer_reply_max_per_conversation_per_day', (int)config('overflowachievement.limits.customer_reply_max_per_conversation_per_day', 6)),
                ],
                'overflowachievement.limits.focus_max_minutes_per_event' => [
                    'value' => \Option::get('overflowachievement.limits.focus_max_minutes_per_event', (int)config('overflowachievement.limits.focus_max_minutes_per_event', 10)),
                ],
                'overflowachievement.limits.focus_max_minutes_per_conversation_per_day' => [
                    'value' => \Option::get('overflowachievement.limits.focus_max_minutes_per_conversation_per_day', (int)config('overflowachievement.limits.focus_max_minutes_per_conversation_per_day', 30)),
                ],

                // UI
                'overflowachievement.show_leaderboard' => [
                    'value' => \Option::get('overflowachievement.show_leaderboard', config('overflowachievement.show_leaderboard') ? 1 : 0),

                ],

                'overflowachievement.ui.show_user_meta' => [
                    'value' => \Option::get('overflowachievement.ui.show_user_meta', (int)config('overflowachievement.ui.show_user_meta', 1)),
                ],
                'overflowachievement.ui.confetti' => [
                    'value' => \Option::get('overflowachievement.ui.confetti', config('overflowachievement.ui.confetti') ? 1 : 0),
                ],
                'overflowachievement.ui.effect' => [
                    'value' => \Option::get('overflowachievement.ui.effect', (string)config('overflowachievement.ui.effect', 'confetti')),
                ],
                'overflowachievement.ui.sound_enabled' => [
                    'value' => \Option::get('overflowachievement.ui.sound_enabled', config('overflowachievement.ui.sound_enabled') ? 1 : 0),
                ],
                'overflowachievement.ui.sound_cooldown_ms' => [
                    'value' => \Option::get('overflowachievement.ui.sound_cooldown_ms', (int)config('overflowachievement.ui.sound_cooldown_ms', 1200)),
                ],
                'overflowachievement.ui.toast_theme' => [
                    'value' => \Option::get('overflowachievement.ui.toast_theme', (string)config('overflowachievement.ui.toast_theme', 'neon')),
                ],
                'overflowachievement.ui.toast_sticky' => [
                    'value' => \Option::get('overflowachievement.ui.toast_sticky', 0),
                ],
                'overflowachievement.ui.toast_duration_ms' => [
                    'value' => \Option::get('overflowachievement.ui.toast_duration_ms', 10000),
                ],
                'overflowachievement.ui.toast_stack_enabled' => [
                    'value' => \Option::get('overflowachievement.ui.toast_stack_enabled', config('overflowachievement.ui.toast_stack_enabled') ? 1 : 0),
                ],
                'overflowachievement.ui.toast_stack_max' => [
                    'value' => \Option::get('overflowachievement.ui.toast_stack_max', (int)config('overflowachievement.ui.toast_stack_max', 2)),
                ],
            ];
        }, 20, 2);

        // Normalize complex settings before FreeScout persists them.
        // FreeScout stores posted option values as-is, so arrays must be converted to JSON strings.
        \Eventy::addFilter('settings.before_save', function ($request, $section, $settings) {
            if ($section !== 'achievement') {
                return $request;
            }

            try {
                $settings_values = (array)($request->settings ?? []);
                $key = 'overflowachievement.quotes.mailbox_rules';

                if (array_key_exists($key, $settings_values)) {
                    $settings_values[$key] = $this->normalizeMailboxRulesOptionValue($settings_values[$key]);
                    $request->merge(['settings' => $settings_values]);
                }
            } catch (\Throwable $e) {
                // Keep settings save resilient; the view/runtime also tolerate legacy values.
            }

            return $request;
        }, 20, 3);

        // After saving settings, clear module runtime caches.
        // (FreeScout clears some caches globally, but module-level caches/vars may remain.)
        \Eventy::addFilter('settings.after_save', function ($response, $request, $section, $saved_settings) {
            try {
                if ($section !== 'achievement') {
                    return $response;
                }

                // Clear common module cache keys (defensive; keys may not exist).
                \Cache::forget('_overflowachievement.vars');
                \Cache::forget('_overflowachievement.settings');
            } catch (\Throwable $e) {
                // ignore
            }
            return $response;
        }, 20, 4);
    }

    protected function normalizeMailboxRulesOptionValue($value): string
    {
        $rules = $this->decodeMailboxRulesOptionValue($value);

        if (empty($rules)) {
            return '';
        }

        $encoded = json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '';
    }

    protected function decodeMailboxRulesOptionValue($value): array
    {
        if (is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $allowed_tones = ['funny', 'epic', 'philosophical'];
        $rules = [];

        foreach ($value as $mailbox_id => $rule) {
            $mailbox_id = trim((string)$mailbox_id);
            if ($mailbox_id === '' || !ctype_digit($mailbox_id)) {
                continue;
            }

            $rule = is_array($rule) ? $rule : [];
            $tones = [];
            foreach ((array)($rule['tones'] ?? []) as $tone) {
                $tone = trim((string)$tone);
                if ($tone !== '' && in_array($tone, $allowed_tones, true)) {
                    $tones[$tone] = true;
                }
            }

            $ids = [];
            foreach ((array)($rule['ids'] ?? []) as $id) {
                $id = trim((string)$id);
                if ($id !== '') {
                    $ids[$id] = true;
                }
            }

            $limit = isset($rule['limit']) ? (int)$rule['limit'] : 0;
            $limit = max(0, $limit);

            if (!empty($tones) || !empty($ids) || $limit > 0) {
                $rules[$mailbox_id] = [
                    'tones' => array_keys($tones),
                    'limit' => $limit,
                ];

                if (!empty($ids)) {
                    $rules[$mailbox_id]['ids'] = array_keys($ids);
                }
            }
        }

        return $rules;
    }
}
