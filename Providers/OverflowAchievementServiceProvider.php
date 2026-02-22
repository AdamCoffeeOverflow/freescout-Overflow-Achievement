<?php

namespace Modules\OverflowAchievement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OverflowAchievement\Services\LevelService;
use Modules\OverflowAchievement\Services\QuoteService;
use Modules\OverflowAchievement\Services\RewardEngine;

class OverflowAchievementServiceProvider extends ServiceProvider
{
    public const MODULE_ALIAS = 'overflowachievement';

    public function register(): void
    {
        // Define a stable module alias constant (FreeScout guideline).
        // Keep it global and unique, and always ending with _MODULE.
        if (!defined('OVERFLOWACHIEVEMENT_MODULE')) {
            define('OVERFLOWACHIEVEMENT_MODULE', self::MODULE_ALIAS);
        }

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'overflowachievement');
        $this->mergeConfigFrom(__DIR__.'/../Config/quotes.php', 'overflowachievement.quotes');
        $this->mergeConfigFrom(__DIR__.'/../Config/levels.php', 'overflowachievement.levels');

        $this->app->singleton('overflowachievement.levels', function () {
            return new LevelService();
        });

        $this->app->singleton('overflowachievement.quotes', function () {
            return new QuoteService();
        });

        $this->app->singleton('overflowachievement.rewards', function ($app) {
            return new RewardEngine(
                $app->make('overflowachievement.levels'),
                $app->make('overflowachievement.quotes')
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'overflowachievement');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Settings must always be available while the module is active,
        // even if the module functionality is disabled via option.
        $this->registerSettings();

        // Lightweight UI elements.
        $this->registerMenu();
        $this->registerAssets();

        // Heavy hooks should only be registered when the module is enabled.
        if ($this->moduleEnabled()) {
            $this->registerHooks();
        }
    }

    protected function moduleEnabled(): bool
    {
        try {
            return (bool)\Option::get('overflowachievement.enabled', config('overflowachievement.enabled') ? 1 : 0);
        } catch (\Throwable $e) {
            return (bool)config('overflowachievement.enabled');
        }
    }

    protected function registerMenu(): void
    {
        $enabled = $this->moduleEnabled();

        \Eventy::addAction('menu.append', function () use ($enabled) {
            if (!$enabled) {
                return;
            }
            echo view('overflowachievement::partials.menu')->render();
        });

        // Add level + XP bar under user name in the top-right account dropdown trigger.
        \Eventy::addAction('menu.user.name_append', function ($user) use ($enabled) {
            try {
                if (!$enabled) {
                    return;
                }
                // Optional UI widget: keep tracking active even if the navbar widget is hidden.
                if (!\Option::get('overflowachievement.ui.show_user_meta', 1)) {
                    return;
                }

                if (!$user || empty($user->id)) {
                    return;
                }

                // Do not touch DB if module migrations have not been executed yet.
                if (!\Illuminate\Support\Facades\Schema::hasTable('overflowachievement_user_stats')) {
                    return;
                }

                // Avoid repeated DB work within the same request.
                static $cached = [];
                if (!isset($cached[$user->id])) {
                    $cached[$user->id] = \Modules\OverflowAchievement\Entities\UserStat::query()
                        ->firstOrCreate(['user_id' => $user->id], ['xp_total' => 0, 'level' => 1]);
                }
                $stat = $cached[$user->id];

                $levels = app('overflowachievement.levels');
                $curMin = $levels->levelMinXp((int)$stat->level);
                $nextMin = $levels->nextLevelMinXp((int)$stat->level);
                $progress = 0;
                $den = max(1, $nextMin - $curMin);
                $progress = (int)round((($stat->xp_total - $curMin) / $den) * 100);
                $progress = max(0, min(100, $progress));

                echo view('overflowachievement::partials.user_meta', [
                    'stat' => $stat,
                    'nextMin' => $nextMin,
                    'curMin' => $curMin,
                    'progress' => $progress,
                ])->render();
            } catch (\Throwable $e) {
                // Never break navbar.
            }
        }, 10, 1);

        \Eventy::addFilter('menu.selected', function ($menu) {
            if (!$this->moduleEnabled()) {
                return $menu;
            }
            // Add our menu group so active highlighting works.
            $menu['overflowachievement'] = [
                'overflowachievement.my',
                'overflowachievement.achievements',
                'overflowachievement.leaderboard',
            ];
            return $menu;
        });
    }

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

    protected function registerAssets(): void
    {
        // IMPORTANT: Do not add query strings to asset paths.
        // FreeScout uses Devfactory Minify which treats entries as filesystem paths.
        // Query strings (e.g. ?v=...) can break minification and thus core UI scripts.

        \Eventy::addFilter('javascripts', function ($javascripts) {
            $enabled = $this->moduleEnabled();

            // Keep settings page functional even when module is disabled.
            $path = '/' . ltrim(request()->path() ?? '', '/');
            $isSettings = str_contains($path, '/settings/') && (str_contains($path, '/achievement') || (request()->get('section') === 'achievement'));
            $isModuleArea = str_contains($path, '/overflowachievement');

            if (!$enabled && !$isSettings && !$isModuleArea) {
                return $javascripts;
            }

            // vars.js is generated by: php artisan freescout:module-build
            // It carries module settings/urls in a CSP-safe way (no inline scripts).
            // IMPORTANT: Only include it if it exists, otherwise Devfactory Minify will throw
            // and FreeScout will skip ALL scripts for the request.
            $varsPath = public_path('modules/'.OVERFLOWACHIEVEMENT_MODULE.'/js/vars.js');
            if (is_file($varsPath)) {
                $javascripts[] = \Module::getPublicPath(OVERFLOWACHIEVEMENT_MODULE).'/js/vars.js';
            }
            $javascripts[] = \Module::getPublicPath(OVERFLOWACHIEVEMENT_MODULE).'/js/module.js';
            return $javascripts;
        });

        \Eventy::addFilter('stylesheets', function ($stylesheets) {
            $stylesheets[] = \Module::getPublicPath(OVERFLOWACHIEVEMENT_MODULE).'/css/module.css';
            return $stylesheets;
        });
    }

    protected function registerHooks(): void
    {
        // Award XP on close
        \Eventy::addAction('conversation.status_changed', function ($conversation, $user, $changed_on_reply, $prev_status) {
            try {
                if (!$user || empty($user->id)) {
                    return;
                }
                if ((int)$conversation->status === (int)\App\Conversation::STATUS_CLOSED
                    && (int)$prev_status !== (int)\App\Conversation::STATUS_CLOSED
                ) {
                    app('overflowachievement.rewards')->awardCloseConversation((int)$user->id, (int)$conversation->id);

                    // SLA: fast resolution relative to ticket creation
                    try {
                        app('overflowachievement.rewards')->awardSlaResolve((int)$user->id, (int)$conversation->id, $conversation->created_at ?? null, $conversation->updated_at ?? null);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                // Set pending (triage)
                if ((int)$conversation->status === (int)\App\Conversation::STATUS_PENDING
                    && (int)$prev_status !== (int)\App\Conversation::STATUS_PENDING
                ) {
                    app('overflowachievement.rewards')->awardSetPending((int)$user->id, (int)$conversation->id);
                }

                // Mark spam
                if ((int)$conversation->status === (int)\App\Conversation::STATUS_SPAM
                    && (int)$prev_status !== (int)\App\Conversation::STATUS_SPAM
                ) {
                    app('overflowachievement.rewards')->awardMarkedSpam((int)$user->id, (int)$conversation->id);
                }
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: status_changed hook failed: '.$e->getMessage());
            }
        }, 10, 4);

        // Award XP on first reply (final, after undo timeout)
        \Eventy::addAction('conversation.user_replied', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id) {
                    return;
                }

                $rewards = app('overflowachievement.rewards');

                // If first-reply XP + SLA-first-response are both disabled, skip the expensive pre-query.
                $checkFirstReply = method_exists($rewards, 'wantsFirstReplyCheck') ? $rewards->wantsFirstReplyCheck() : true;

                // If there are no other user message threads, this is the first reply.
                // Use EXISTS instead of COUNT() for performance on large threads tables.
                if ($checkFirstReply) {
                    $has_other_user_message = \App\Thread::query()
                        ->where('conversation_id', $conversation->id)
                        ->where('type', \App\Thread::TYPE_MESSAGE)
                        ->whereNotNull('created_by_user_id')
                        ->where('id', '<>', (int)($thread->id ?? 0))
                        ->exists();

                    if (!$has_other_user_message) {
                        $rewards->awardFirstReply($user_id, (int)$conversation->id);

                        // SLA: fast first response (relative to ticket creation)
                        try {
                            $rewards->awardSlaFirstResponse($user_id, (int)$conversation->id, $conversation->created_at ?? null, $thread->created_at ?? null);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }

                // Any reply (capped per conversation per day).
                $rewards->awardReplySent($user_id, (int)$conversation->id);

                // SLA: fast follow-up after a customer reply (uses our customer_replied event timestamp)
                try {
                    $rewards->awardSlaFastReply($user_id, (int)$conversation->id, $thread->created_at ?? null);
                } catch (\Throwable $e) {
                    // ignore
                }
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: user_replied hook failed: '.$e->getMessage());
            }
        }, 10, 2);

        // Customer replied (incoming email). Credit the currently assigned agent (if any).
        \Eventy::addAction('conversation.customer_replied', function ($conversation, $thread, $customer) {
            try {
                if (!$conversation) {
                    return;
                }
                $user_id = (int)($conversation->user_id ?? 0);
                if (!$user_id) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerReplied($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer_replied hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for creating a new conversation (outbound/proactive).
        // FreeScout fires this after undo window is done.
        \Eventy::addAction('conversation.created_by_user_can_undo', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardConversationCreated($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: created_by_user_can_undo hook failed: '.$e->getMessage());
            }
        }, 10, 2);


        // Award XP for internal note (after final save)
        \Eventy::addAction('conversation.note_added', function ($conversation, $thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardNoteAdded($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: note_added hook failed: '.$e->getMessage());
            }
        }, 10, 2);

        // Award XP for assignment changes (taking ownership / reassigning)
        \Eventy::addAction('conversation.user_changed', function ($conversation, $user, $prev_user_id) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                // FreeScout passes $user as the actor who changed the assignee.
                // Semantics: we treat this trigger as "took ownership" (self-assign), not "assigned someone".
                $new_user_id = (int)($conversation->user_id ?? 0);
                if ((int)$prev_user_id === $new_user_id) {
                    return;
                }
                if ($new_user_id !== (int)$user->id) {
                    return;
                }

                app('overflowachievement.rewards')->awardAssigned((int)$user->id, (int)$conversation->id, (int)$prev_user_id, $new_user_id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: user_changed hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for moving conversations between mailboxes
        \Eventy::addAction('conversation.moved', function ($conversation, $user, $prev_mailbox) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardMoved((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: moved hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for deleting conversations (state changed to deleted).
        \Eventy::addAction('conversation.state_changed', function ($conversation, $user, $prev_state) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                if ((int)($conversation->state ?? 0) === (int)\App\Conversation::STATE_DELETED
                    && (int)$prev_state !== (int)\App\Conversation::STATE_DELETED
                ) {
                    app('overflowachievement.rewards')->awardDeletedConversation((int)$user->id, (int)$conversation->id);
                }
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: state_changed hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for subject edits (capped once per conversation per day).
        \Eventy::addAction('conversation.subject_changed', function ($conversation, $user, $prev_subject) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardSubjectChanged((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: subject_changed hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for merges
        \Eventy::addAction('conversation.merged', function ($conversation, $second_conversation, $user) {
            try {
                if (!$user || empty($user->id) || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardMerged((int)$user->id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: merged hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for forwarding
        \Eventy::addAction('conversation.user_forwarded', function ($conversation, $thread, $forwarded_conversation, $forwarded_thread) {
            try {
                $user_id = (int)($thread->created_by_user_id ?? 0);
                if (!$user_id || !$conversation) {
                    return;
                }
                app('overflowachievement.rewards')->awardForwarded($user_id, (int)$conversation->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: user_forwarded hook failed: '.$e->getMessage());
            }
        }, 10, 4);

        // Award XP for attachments
        \Eventy::addAction('attachment.created', function ($attachment) {
            try {
                // Attachment model has: created_by_user_id, thread_id; infer conversation if possible
                $user_id = (int)($attachment->created_by_user_id ?? 0);
                if (!$user_id) {
                    return;
                }

                $rewards = app('overflowachievement.rewards');
                if (method_exists($rewards, 'wantsAttachmentAward') && !$rewards->wantsAttachmentAward()) {
                    return;
                }

                $conversation_id = 0;
                if (!empty($attachment->thread_id)) {
                    $thread = \App\Thread::query()->select(['id', 'conversation_id'])->find((int)$attachment->thread_id);
                    if ($thread) {
                        $conversation_id = (int)$thread->conversation_id;
                    }
                }
                $rewards->awardAttachmentAdded($user_id, $conversation_id ?: null);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: attachment.created hook failed: '.$e->getMessage());
            }
        }, 10, 1);

        // Award XP for creating customers (typically done while working)
        \Eventy::addAction('customer.created', function ($customer) {
            try {
                $user_id = (int)($customer->created_by_user_id ?? 0);
                if (!$user_id) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerCreated($user_id, (int)$customer->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer.created hook failed: '.$e->getMessage());
            }
        }, 10, 1);

        // Award XP for updating customers (capped per day)
        \Eventy::addAction('customer.updated', function ($customer) {
            try {
                $user_id = (int)($customer->updated_by_user_id ?? 0);
                if (!$user_id) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerUpdated($user_id, (int)$customer->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer.updated hook failed: '.$e->getMessage());
            }
        }, 10, 1);

        // Award XP for merging customers.
        \Eventy::addAction('customer.merged', function ($customer, $customer2, $user) {
            try {
                if (!$user || empty($user->id) || !$customer) {
                    return;
                }
                app('overflowachievement.rewards')->awardCustomerMerged((int)$user->id, (int)$customer->id);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: customer.merged hook failed: '.$e->getMessage());
            }
        }, 10, 3);

        // Award XP for focus time (viewer tracker).
        \Eventy::addAction('conversation.view.finish', function ($conversation_id, $user_id, $seconds) {
            try {
                $uid = (int)$user_id;
                $cid = (int)$conversation_id;
                $sec = (int)$seconds;
                if (!$uid || !$cid || $sec <= 0) {
                    return;
                }
                app('overflowachievement.rewards')->awardFocusTime($uid, $cid, $sec);
            } catch (\Throwable $e) {
                \Log::error('OverflowAchievement: view.finish hook failed: '.$e->getMessage());
            }
        }, 10, 3);
    }
}
