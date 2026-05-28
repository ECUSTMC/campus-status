<?php

namespace CampusStatus\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateCampusStatusTable
{
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('campus_status_records')) {
            Capsule::schema()->create('campus_status_records', function (Blueprint $table) {
                $table->unsignedInteger('uid')->primary();
                $table->string('ip', 45)->nullable();
                $table->timestamp('verified_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('campus_status_records');
    }
}
