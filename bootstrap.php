<?php

use App\Services\Hook;
use Illuminate\Support\Facades\Cache;
use CampusStatus\Migrations\AddExpiresAtToCampusStatusTable;

return function () {
    if (!Cache::get('campus_status_expires_at_migrated')) {
        require_once __DIR__.'/src/Migrations/AddExpiresAtToCampusStatusTable.php';

        $migrate = new AddExpiresAtToCampusStatusTable();
        $migrate->up();

        Cache::forever('campus_status_expires_at_migrated', true);
    }

    Hook::addRoute(function () {
        Route::namespace('CampusStatus\Controllers')
            ->prefix('user/campus-status')
            ->middleware(['web', 'authorize', 'verified'])
            ->group(function () {
                Route::get('', 'CampusStatusController@page');
                Route::post('verify', 'CampusStatusController@verify');
                Route::post('verify-by-email', 'CampusStatusController@verifyByEmail');
                Route::get('check-ip', 'CampusStatusController@checkIp');
            });

        Route::namespace('CampusStatus\Controllers')
            ->prefix('admin/campus-status')
            ->middleware(['web', 'auth', 'role:admin'])
            ->group(function () {
                Route::post('manual-verify/{uid}', 'AdminController@manualVerify');
                Route::post('revoke/{uid}', 'AdminController@revoke');
                Route::post('batch-verify', 'AdminController@batchVerify');
            });
    });

    Hook::addMenuItem('user', 10, [
        'title' => 'CampusStatus::campus-status.menu',
        'link'  => 'user/campus-status',
        'icon'  => 'fa-university',
    ]);
};
