<?php

use App\Events\PluginWasDisabled;
use App\Events\PluginWasEnabled;
use App\Services\Facades\Option;

return [
    PluginWasEnabled::class => function () {
        $items = [
            'campus_status_cidr_ranges' => '',
            'campus_status_validity_days' => '365',
        ];

        foreach ($items as $key => $value) {
            if (!Option::get($key)) {
                Option::set($key, $value);
            }
        }

        $migrate = new \CampusStatus\Migrations\CreateCampusStatusTable();
        $migrate->up();
    },

    PluginWasDisabled::class => function () {
    },
];
