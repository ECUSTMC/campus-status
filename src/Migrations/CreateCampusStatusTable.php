<?php

namespace CampusStatus\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampusStatusTable
{
    public function up(): void
    {
        if (!Schema::hasTable('campus_status_records')) {
            Schema::create('campus_status_records', function (Blueprint $table) {
                $table->unsignedInteger('uid')->primary();
                $table->string('ip', 45)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('expires_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_status_records');
    }
}
