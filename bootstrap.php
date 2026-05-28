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
                Route::get('check-ip', 'CampusStatusController@checkIp');
            });
    });

    Hook::addMenuItem('user', 10, [
        'title' => 'CampusStatus::campus-status.menu',
        'link'  => 'user/campus-status',
        'icon'  => 'fa-university',
    ]);
};
