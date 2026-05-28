<?php

namespace CampusStatus\Controllers;

use Auth;
use CampusStatus\Models\CampusStatusRecord;
use CampusStatus\Services\CampusIpChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CampusStatusController extends Controller
{
    public function page()
    {
        $user = Auth::user();
        $ip = request()->header('EO-Connecting-IP', request()->ip());

        $checker = new CampusIpChecker();
        $isOnCampus = $checker->isIpInRanges($ip);

        $record = CampusStatusRecord::where('uid', $user->uid)->first();
        $isValid = $record ? $record->isOnCampus() : false;

        $validityDays = (int) option('campus_status_validity_days', 365);

        $status = 'unverified';
        $verifiedAt = null;
        $validUntil = null;

        if ($record && $record->verified_at) {
            $verifiedAt = $record->verified_at;
            $validUntil = $record->verified_at->addDays($validityDays);
            $status = $isValid ? 'on_campus' : 'off_campus';
        }

        return view('CampusStatus::index', [
            'is_on_campus_ip' => $isOnCampus,
            'status' => $status,
            'verified_at' => $verifiedAt,
            'valid_until' => $validUntil,
            'current_ip' => $ip,
            'validity_days' => $validityDays,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $user = Auth::user();
        $ip = $request->header('EO-Connecting-IP', $request->ip());

        $checker = new CampusIpChecker();
        if (!$checker->isIpInRanges($ip)) {
            return json(trans('CampusStatus::campus-status.page.verify-fail'), 1);
        }

        $record = CampusStatusRecord::where('uid', $user->uid)->first();
        if (!$record) {
            $record = new CampusStatusRecord();
            $record->uid = $user->uid;
        }

        $record->ip = $ip;
        $record->verified_at = now();
        $record->save();

        return json(trans('CampusStatus::campus-status.page.verify-success'), 0);
    }

    public function checkIp(): JsonResponse
    {
        $ip = request()->header('EO-Connecting-IP', request()->ip());

        $checker = new CampusIpChecker();
        $isOnCampus = $checker->isIpInRanges($ip);

        return json([
            'ip' => $ip,
            'on_campus' => $isOnCampus,
        ], 0);
    }
}
