<?php

namespace CampusStatus\Controllers;

use App\Models\User;
use App\Services\Facades\Option;
use App\Services\OptionForm;
use CampusStatus\Models\CampusStatusRecord;
use CampusStatus\Services\CampusIpChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class ConfigController extends Controller
{
    public function render(): View
    {
        $form = Option::form('general', trans('CampusStatus::campus-status.config.general.title'), function (OptionForm $form) {
            $form->textarea('campus_status_cidr_ranges', trans('CampusStatus::campus-status.config.general.cidr-ranges.title'))
                ->description(trans('CampusStatus::campus-status.config.general.cidr-ranges.description'))
                ->rows(10);

            $form->text('campus_status_validity_days', trans('CampusStatus::campus-status.config.general.validity-period.title'))
                ->description(trans('CampusStatus::campus-status.config.general.validity-period.description'));
        })->after(function () {
            $checker = new CampusIpChecker();
            $invalid = $checker->getInvalidCidrEntries();
            if (!empty($invalid)) {
                $form->addAlert(
                    trans('CampusStatus::campus-status.config.invalid-cidr', ['list' => implode(', ', $invalid)]),
                    'warning'
                );
            }
        })->handle();

        $totalUsers = User::count();
        $verifiedCount = CampusStatusRecord::count();
        $onCampusCount = 0;
        $offCampusCount = 0;

        $records = CampusStatusRecord::all();
        foreach ($records as $record) {
            if ($record->isOnCampus()) {
                $onCampusCount++;
            } else {
                $offCampusCount++;
            }
        }

        $unverifiedCount = $totalUsers - $verifiedCount;

        $form->addMessage(trans('CampusStatus::campus-status.config.stats.on-campus', ['count' => $onCampusCount]), 'success');
        $form->addMessage(trans('CampusStatus::campus-status.config.stats.off-campus', ['count' => $offCampusCount]), 'warning');
        $form->addMessage(trans('CampusStatus::campus-status.config.stats.unverified', ['count' => $unverifiedCount]), 'info');
        $form->addMessage(trans('CampusStatus::campus-status.config.stats.total', ['count' => $totalUsers]), 'info');

        $users = User::orderBy('uid', 'desc')->paginate(20);
        $userStatuses = [];

        foreach ($users as $user) {
            $record = CampusStatusRecord::where('uid', $user->uid)->first();
            $userStatuses[] = [
                'uid' => $user->uid,
                'nickname' => $user->nickname,
                'email' => $user->email,
                'record' => $record,
                'on_campus' => $record ? $record->isOnCampus() : false,
                'has_record' => $record !== null,
            ];
        }

        return view('CampusStatus::config', [
            'form' => $form,
            'users' => $users,
            'userStatuses' => $userStatuses,
        ]);
    }
}
