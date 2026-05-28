<?php

namespace CampusStatus\Models;

use Illuminate\Database\Eloquent\Model;

class CampusStatusRecord extends Model
{
    protected $table = 'campus_status_records';

    protected $primaryKey = 'uid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'ip',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function isOnCampus(): bool
    {
        if (!$this->verified_at) {
            return false;
        }

        $validityDays = (int) option('campus_status_validity_days', 365);

        return $this->verified_at->addDays($validityDays)->isFuture();
    }
}
