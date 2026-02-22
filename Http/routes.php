<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['web', 'auth'],
    // IMPORTANT:
    // Module routes are registered from module.json "files" (start.php) and are not wrapped
    // by FreeScout's main RouteServiceProvider group. So we MUST add subdirectory prefix here
    // to keep FreeScout working when installed in a subdirectory.
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\\OverflowAchievement\\Http\\Controllers'
], function () {

    Route::get('/modules/overflowachievement/my', [
        'uses' => 'OverflowAchievementController@my',
        'as' => 'overflowachievement.my',
    ]);

    Route::get('/modules/overflowachievement/achievements', [
        'uses' => 'OverflowAchievementController@achievements',
        'as' => 'overflowachievement.achievements',
    ]);

    Route::get('/modules/overflowachievement/leaderboard', [
        'uses' => 'OverflowAchievementController@leaderboard',
        'as' => 'overflowachievement.leaderboard',
    ]);

    Route::post('/modules/overflowachievement/unseen', [
        'uses' => 'OverflowAchievementController@unseen',
        'as' => 'overflowachievement.unseen',
    ]);

    Route::post('/modules/overflowachievement/mark-seen', [
        'uses' => 'OverflowAchievementController@markSeen',
        'as' => 'overflowachievement.mark_seen',
    ]);

    // Lightweight health endpoint for the settings diagnostic.
    Route::get('/modules/overflowachievement/health', [
        'uses' => 'OverflowAchievementController@health',
        'as' => 'overflowachievement.health',
    ]);

    // Admin achievement management (used from Settings > Achievement)
    Route::post('/modules/overflowachievement/admin/achievements', [
        'uses' => 'AchievementAdminController@store',
        'as' => 'overflowachievement.admin.achievements.store',
    ]);

    // Re-assign all achievement icons from the bundled icon pack (admin maintenance)
    // NOTE: Must be declared BEFORE routes using {id} to avoid treating "reassign-icons" as an ID.
    Route::post('/modules/overflowachievement/admin/achievements/reassign-icons', [
        'uses' => 'AchievementAdminController@reassignIcons',
        'as' => 'overflowachievement.admin.achievements.reassign_icons',
    ]);

    Route::post('/modules/overflowachievement/admin/achievements/{id}', [
        'uses' => 'AchievementAdminController@update',
        'as' => 'overflowachievement.admin.achievements.update',
    ])->where('id', '[0-9]+');

    Route::post('/modules/overflowachievement/admin/achievements/{id}/delete', [
        'uses' => 'AchievementAdminController@destroy',
        'as' => 'overflowachievement.admin.achievements.destroy',
    ])->where('id', '[0-9]+');

    Route::post('/modules/overflowachievement/admin/reset', [
        'uses' => 'AchievementAdminController@reset',
        'as' => 'overflowachievement.admin.reset',
    ]);

    Route::post('/modules/overflowachievement/admin/test-toast', [
        'uses' => 'AchievementAdminController@testToast',
        'as' => 'overflowachievement.admin.test_toast',
    ]);

    Route::post('/modules/overflowachievement/admin/test-preview', [
        'uses' => 'AchievementAdminController@testPreview',
        'as' => 'overflowachievement.admin.test_preview',
    ]);
});
