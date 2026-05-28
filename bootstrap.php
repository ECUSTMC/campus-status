<?php

use App\Services\Hook;

return function () {
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
            });
    });

    Hook::addMenuItem('user', 10, [
        'title' => 'CampusStatus::campus-status.menu',
        'link'  => 'user/campus-status',
        'icon'  => 'fa-university',
    ]);
};
