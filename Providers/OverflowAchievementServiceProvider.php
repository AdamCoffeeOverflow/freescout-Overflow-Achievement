<?php

namespace Modules\OverflowAchievement\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\OverflowAchievement\Providers\Concerns\RegistersOverflowAchievementAssets;
use Modules\OverflowAchievement\Providers\Concerns\RegistersOverflowAchievementHooks;
use Modules\OverflowAchievement\Providers\Concerns\RegistersOverflowAchievementMenu;
use Modules\OverflowAchievement\Providers\Concerns\RegistersOverflowAchievementSettings;
use Modules\OverflowAchievement\Services\LevelService;
use Modules\OverflowAchievement\Services\QuoteService;
use Modules\OverflowAchievement\Services\RewardEngine;
use Modules\OverflowAchievement\Services\RuntimeBootstrapService;
use Modules\OverflowAchievement\Services\UserProgressService;

class OverflowAchievementServiceProvider extends ServiceProvider
{
    use RegistersOverflowAchievementMenu;
    use RegistersOverflowAchievementSettings;
    use RegistersOverflowAchievementAssets;
    use RegistersOverflowAchievementHooks;

    public const MODULE_ALIAS = 'overflowachievement';

    public function register(): void
    {
        if (!defined('OVERFLOWACHIEVEMENT_MODULE')) {
            define('OVERFLOWACHIEVEMENT_MODULE', self::MODULE_ALIAS);
        }

        $this->mergeModuleConfig(__DIR__.'/../Config/config.php', 'overflowachievement', true);
        $this->mergeModuleConfig(__DIR__.'/../Config/quotes.php', 'overflowachievement.quotes', true);
        $this->mergeModuleConfig(__DIR__.'/../Config/levels.php', 'overflowachievement.levels', false);

        $this->app->singleton('overflowachievement.levels', function () {
            return new LevelService();
        });
        $this->app->singleton(LevelService::class, function ($app) {
            return $app->make('overflowachievement.levels');
        });

        $this->app->singleton('overflowachievement.quotes', function () {
            return new QuoteService();
        });
        $this->app->singleton(QuoteService::class, function ($app) {
            return $app->make('overflowachievement.quotes');
        });

        $this->app->singleton('overflowachievement.rewards', function ($app) {
            return new RewardEngine(
                $app->make(LevelService::class),
                $app->make(QuoteService::class)
            );
        });
        $this->app->singleton(RewardEngine::class, function ($app) {
            return $app->make('overflowachievement.rewards');
        });

        $this->app->singleton(UserProgressService::class, function ($app) {
            return new UserProgressService($app->make(LevelService::class));
        });

        $this->app->singleton(RuntimeBootstrapService::class, function () {
            return new RuntimeBootstrapService();
        });
    }

    protected function mergeModuleConfig(string $path, string $key, bool $recursive = true): void
    {
        $defaults = require $path;
        if (!is_array($defaults)) {
            $defaults = [];
        }

        $existing = $this->app['config']->get($key, []);
        if (!is_array($existing)) {
            $existing = [];
        }

        $merged = $recursive
            ? array_replace_recursive($defaults, $existing)
            : array_replace($defaults, $existing);

        $this->app['config']->set($key, $merged);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'overflowachievement');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'overflowachievement');
        $this->loadJsonTranslationsFrom(__DIR__.'/../Resources/lang');

        $this->registerSettings();
        $this->registerMenu();
        $this->registerAssets();

        if ($this->moduleEnabled()) {
            $this->registerHooks();
        }
    }

    protected function moduleEnabled(): bool
    {
        try {
            return (bool) \Option::get('overflowachievement.enabled', config('overflowachievement.enabled') ? 1 : 0);
        } catch (\Throwable $e) {
            return (bool) config('overflowachievement.enabled');
        }
    }
}
