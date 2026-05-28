<?php

namespace CampusStatus\Migrations;

use CampusStatus\Models\CampusStatusRecord;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpiresAtToCampusStatusTable
{
    public function up(): void
    {
        if (Schema::hasTable('campus_status_records') && !Schema::hasColumn('campus_status_records', 'expires_at')) {
            Schema::table('campus_status_records', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable();
            });

            $validityDays = (int) option('campus_status_validity_days', 365);

            CampusStatusRecord::whereNotNull('verified_at')
                ->whereNull('expires_at')
                ->chunk(100, function ($records) use ($validityDays) {
                    foreach ($records as $record) {
                        $record->expires_at = $record->verified_at->addDays($validityDays);
                        $record->save();
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('campus_status_records') && Schema::hasColumn('campus_status_records', 'expires_at')) {
            Schema::table('campus_status_records', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
}
