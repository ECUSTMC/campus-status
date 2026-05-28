<?php

use App\Events\PluginWasDisabled;
use App\Events\PluginWasEnabled;
use App\Services\Facades\Option;

return [
    PluginWasEnabled::class => function () {
        $items = [
            'campus_status_cidr_ranges' => '',
            'campus_status_validity_days' => '365',
            'campus_status_benefits' => '',
        ];

        foreach ($items as $key => $value) {
            if (!Option::get($key)) {
                Option::set($key, $value);
            }
        }

        require_once __DIR__.'/src/Migrations/CreateCampusStatusTable.php';

        $migrate = new \CampusStatus\Migrations\CreateCampusStatusTable();
        $migrate->up();

        require_once __DIR__.'/src/Migrations/AddExpiresAtToCampusStatusTable.php';

        $migrate = new \CampusStatus\Migrations\AddExpiresAtToCampusStatusTable();
        $migrate->up();
    },

    PluginWasDisabled::class => function () {
    },
];
