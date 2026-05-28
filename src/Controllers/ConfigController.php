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

            $form->textarea('campus_status_benefits', trans('CampusStatus::campus-status.config.general.benefits.title'))
                ->description(trans('CampusStatus::campus-status.config.general.benefits.description'))
                ->rows(10);
        });

        $form->after(function () use ($form) {
            $checker = new CampusIpChecker();
            $invalid = $checker->getInvalidCidrEntries();
            if (!empty($invalid)) {
                $form->addAlert(
                    trans('CampusStatus::campus-status.config.invalid-cidr', ['list' => implode(', ', $invalid)]),
                    'warning'
                );
            }
        });

        $form->handle();

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

        $search = request()->query('search', '');
        $statusFilter = request()->query('status', '');

        $query = User::orderBy('uid', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('uid', $search)
                  ->orWhere('nickname', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($statusFilter === 'on_campus') {
            $query->whereIn('users.uid', function ($q) {
                $q->select('uid')->from('campus_status_records')
                  ->where('expires_at', '>', now());
            });
        } elseif ($statusFilter === 'off_campus') {
            $query->whereIn('users.uid', function ($q) {
                $q->select('uid')->from('campus_status_records')
                  ->whereNotNull('expires_at')
                  ->where('expires_at', '<=', now());
            });
        } elseif ($statusFilter === 'unverified') {
            $query->whereNotIn('users.uid', function ($q) {
                $q->select('uid')->from('campus_status_records');
            });
        }

        $users = $query->paginate(20)->appends(['search' => $search, 'status' => $statusFilter]);

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
            'search' => $search,
            'status_filter' => $statusFilter,
        ]);
    }
}
