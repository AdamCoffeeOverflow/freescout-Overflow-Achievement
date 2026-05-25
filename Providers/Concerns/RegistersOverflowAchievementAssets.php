<?php

namespace Modules\OverflowAchievement\Providers\Concerns;

trait RegistersOverflowAchievementAssets
{
    protected function registerAssets(): void
    {
        \Eventy::addFilter('javascripts', function ($javascripts) {
            if (!$this->shouldLoadFrontendAssets()) {
                return $javascripts;
            }

            $javascripts[] = \Module::getPublicPath(OVERFLOWACHIEVEMENT_MODULE).'/js/module.js';
            return $javascripts;
        });

        \Eventy::addFilter('stylesheets', function ($stylesheets) {
            if (!$this->shouldLoadFrontendAssets()) {
                return $stylesheets;
            }

            $stylesheets[] = \Module::getPublicPath(OVERFLOWACHIEVEMENT_MODULE).'/css/module.css';
            return $stylesheets;
        });
    }

    protected function shouldLoadFrontendAssets(): bool
    {
        // The login/password pages also pass through the global FreeScout asset filters.
        // Do not load this module's runtime there: module.js calls authenticated JSON
        // endpoints such as /modules/overflowachievement/bootstrap and would produce a
        // harmless but noisy 401 in the browser console for guests.
        if (!$this->requestHasAuthenticatedUser()) {
            return false;
        }

        $enabled = $this->moduleEnabled();
        $path = '/' . ltrim(request()->path() ?? '', '/');
        $isSettings = strpos($path, '/settings/') !== false
            && (strpos($path, '/achievement') !== false || (request()->get('section') === 'achievement'));
        $isModuleArea = strpos($path, '/overflowachievement') !== false;

        return $enabled || $isSettings || $isModuleArea;
    }

    protected function requestHasAuthenticatedUser(): bool
    {
        try {
            return \Auth::check();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
