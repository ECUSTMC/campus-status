<?php

namespace CampusStatus\Controllers;

use Auth;
use CampusStatus\Models\CampusStatusRecord;
use CampusStatus\Services\CampusIpChecker;
use CampusStatus\Services\EmailVerifier;
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
            $validUntil = $record->expires_at ?? $record->verified_at->addDays($validityDays);
            $status = $isValid ? 'on_campus' : 'off_campus';
        }

        $emailVerifier = new EmailVerifier();
        $emailResult = $emailVerifier->verify($user->email);

        return view('CampusStatus::index', [
            'is_on_campus_ip' => $isOnCampus,
            'status' => $status,
            'verified_at' => $verifiedAt,
            'valid_until' => $validUntil,
            'current_ip' => $ip,
            'validity_days' => $validityDays,
            'user_email' => $user->email,
            'email_verify_result' => $emailResult,
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

        $validityDays = (int) option('campus_status_validity_days', 365);

        $record = CampusStatusRecord::where('uid', $user->uid)->first();
        if (!$record) {
            $record = new CampusStatusRecord();
            $record->uid = $user->uid;
        }

        $record->ip = $ip;
        $record->verified_at = now();
        $record->expires_at = now()->addDays($validityDays);
        $record->save();

        return json(trans('CampusStatus::campus-status.page.verify-success'), 0);
    }

    public function verifyByEmail(Request $request): JsonResponse
    {
        $user = Auth::user();

        $verifier = new EmailVerifier();
        $result = $verifier->verify($user->email);

        if (!$result['valid']) {
            return json($result['message'], 1);
        }

        $validityDays = (int) option('campus_status_validity_days', 365);

        $record = CampusStatusRecord::where('uid', $user->uid)->first();
        if (!$record) {
            $record = new CampusStatusRecord();
            $record->uid = $user->uid;
        }

        $record->ip = 'email';
        $record->verified_at = now();
        $record->expires_at = now()->addDays($validityDays);
        $record->save();

        return json($result['message'], 0);
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
