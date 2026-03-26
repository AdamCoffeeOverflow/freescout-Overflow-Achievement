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
        $enabled = $this->moduleEnabled();
        $path = '/' . ltrim(request()->path() ?? '', '/');
        $isSettings = str_contains($path, '/settings/') && (str_contains($path, '/achievement') || (request()->get('section') === 'achievement'));
        $isModuleArea = str_contains($path, '/overflowachievement');

        return $enabled || $isSettings || $isModuleArea;
    }
}
