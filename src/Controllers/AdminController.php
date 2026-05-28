<?php

namespace CampusStatus\Controllers;

use App\Models\User;
use CampusStatus\Models\CampusStatusRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AdminController extends Controller
{
    public function manualVerify(Request $request, int $uid): JsonResponse
    {
        $user = User::find($uid);

        if (!$user) {
            return json(trans('CampusStatus::campus-status.config.user-list.manual-verify-fail'), 1);
        }

        $validityDays = (int) option('campus_status_validity_days', 365);

        $record = CampusStatusRecord::where('uid', $uid)->first();
        if (!$record) {
            $record = new CampusStatusRecord();
            $record->uid = $uid;
        }

        $record->ip = 'manual';
        $record->verified_at = now();
        $record->expires_at = now()->addDays($validityDays);
        $record->save();

        return json(trans('CampusStatus::campus-status.config.user-list.manual-verify-success'), 0);
    }

    public function revoke(Request $request, int $uid): JsonResponse
    {
        $record = CampusStatusRecord::where('uid', $uid)->first();

        if ($record) {
            $record->delete();
        }

        return json(trans('CampusStatus::campus-status.config.user-list.revoke-success'), 0);
    }
}
