<?php

namespace CampusStatus\Services;

use App\Models\User;
use CampusStatus\Models\CampusStatusRecord;
use Illuminate\Support\Facades\DB;

class AutoVerifier
{
    public static function verify(int $uid, string $email): bool
    {
        return (new self())->verifyUserByEmail($uid, $email);
    }

    public function verifyUserByEmail(int $uid, string $email): bool
    {
        $verifier = new EmailVerifier();
        $result = $verifier->verify($email);

        if (!$result['valid']) {
            $record = CampusStatusRecord::where('uid', $uid)->first();
            if (!$record) {
                $record = new CampusStatusRecord();
                $record->uid = $uid;
                $record->ip = 'email_failed';
                $record->verified_at = null;
                $record->expires_at = null;
                $record->save();
            }

            return false;
        }

        $validityDays = (int) option('campus_status_validity_days', 365);

        $expiresAt = now()->addDays($validityDays);

        if ($result['graduation_date'] && $result['graduation_date']->lt($expiresAt)) {
            $expiresAt = $result['graduation_date'];
        }

        $record = CampusStatusRecord::where('uid', $uid)->first();
        if (!$record) {
            $record = new CampusStatusRecord();
            $record->uid = $uid;
        }

        $record->ip = 'email';
        $record->verified_at = now();
        $record->expires_at = $expiresAt;
        $record->save();

        return true;
    }

    public function batchVerify(): int
    {
        $count = 0;

        User::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('campus_status_records')
                  ->whereRaw('campus_status_records.uid = users.uid');
        })->chunk(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                if ($this->verifyUserByEmail($user->uid, $user->email)) {
                    $count++;
                }
            }
        });

        return $count;
    }
}
