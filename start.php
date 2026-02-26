<?php

// Module alias constant (recommended by FreeScout).
// Use this constant whenever referencing the module alias (paths, option keys, etc.).
if (!defined('OVERFLOWACHIEVEMENT_MODULE')) {
    define('OVERFLOWACHIEVEMENT_MODULE', 'overflowachievement');
}

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
*/

if (!app()->routesAreCached()) {
    // Some FreeScout deployments do not register module PSR-4 autoloaders
    // early enough for controller string resolution (especially after updates
    // with caches/opcache). Explicit requires keep routing stable.
    require_once __DIR__ . '/Http/Controllers/OverflowAchievementController.php';
    require_once __DIR__ . '/Http/Controllers/AchievementAdminController.php';

    require __DIR__ . '/Http/routes.php';
}
