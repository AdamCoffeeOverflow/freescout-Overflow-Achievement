<?php

namespace Modules\OverflowAchievement\Providers\Concerns;

use Modules\OverflowAchievement\Services\UserProgressService;

trait RegistersOverflowAchievementMenu
{
    protected function registerMenu(): void
    {
        $enabled = $this->moduleEnabled();

        \Eventy::addAction('menu.append', function () use ($enabled) {
            if (!$enabled) {
                return;
            }
            echo view('overflowachievement::partials.menu')->render();
        });

        \Eventy::addAction('menu.user.name_append', function ($user) use ($enabled) {
            try {
                if (!$enabled || !\Option::get('overflowachievement.ui.show_user_meta', 1)) {
                    return;
                }

                if (!$user || empty($user->id)) {
                    return;
                }

                if (!\Illuminate\Support\Facades\Schema::hasTable('overflowachievement_user_stats')) {
                    return;
                }

                static $cached = [];
                if (!isset($cached[$user->id])) {
                    $progress = app(UserProgressService::class);
                    $stat = $progress->statForUser((int) $user->id, false);
                    $stat = $progress->syncDisplayedLevel($stat, false);
                    $cached[$user->id] = [
                        'stat' => $stat,
                        'snapshot' => $progress->snapshot($stat),
                    ];
                }

                $data = $cached[$user->id];
                echo view('overflowachievement::partials.user_meta', [
                    'stat' => $data['stat'],
                    'nextMin' => $data['snapshot']['next_min'],
                    'curMin' => $data['snapshot']['cur_min'],
                    'progress' => $data['snapshot']['progress'],
                ])->render();
            } catch (\Throwable $e) {
                // Never break navbar.
            }
        }, 10, 1);

        \Eventy::addFilter('menu.selected', function ($menu) {
            if (!$this->moduleEnabled()) {
                return $menu;
            }
            $menu['overflowachievement'] = [
                'overflowachievement.my',
                'overflowachievement.achievements',
                'overflowachievement.leaderboard',
            ];
            return $menu;
        });
    }
}
