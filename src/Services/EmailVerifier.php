<?php

namespace CampusStatus\Services;

use Carbon\Carbon;

class EmailVerifier
{
    public function verify(string $email): array
    {
        $emailLower = strtolower($email);

        $mailSuffix = '@mail.ecust.edu.cn';
        if (substr($emailLower, -strlen($mailSuffix)) === $mailSuffix) {
            $localPart = substr($email, 0, -strlen($mailSuffix));
            return $this->verifyStudentEmail($localPart);
        }

        $staffSuffix = '@ecust.edu.cn';
        if (substr($emailLower, -strlen($staffSuffix)) === $staffSuffix) {
            $localPart = substr($email, 0, -strlen($staffSuffix));
            return $this->verifyStaffEmail($localPart);
        }

        return [
            'valid' => false,
            'type' => null,
            'graduation_date' => null,
            'message' => trans('CampusStatus::campus-status.page.email-verify-not-ecust'),
        ];
    }

    private function verifyStudentEmail(string $localPart): array
    {
        if (preg_match('/^[Yy](\d{8})$/', $localPart, $matches)) {
            $studentNumber = $matches[1];
            $enrollYear = (int) substr($studentNumber, 2, 2);

            $graduationDate = Carbon::create(2000 + $enrollYear + 2, 6, 30, 23, 59, 59);

            if ($graduationDate->isFuture()) {
                return [
                    'valid' => true,
                    'type' => 'graduate',
                    'graduation_date' => $graduationDate,
                    'message' => trans('CampusStatus::campus-status.page.email-verify-success-graduate'),
                ];
            }

            return [
                'valid' => false,
                'type' => 'graduate',
                'graduation_date' => null,
                'message' => trans('CampusStatus::campus-status.page.email-verify-fail-graduate'),
            ];
        }

        if (preg_match('/^(\d{8})$/', $localPart, $matches)) {
            $enrollYear = (int) substr($matches[1], 0, 2);

            $graduationDate = Carbon::create(2000 + $enrollYear + 4, 6, 30, 23, 59, 59);

            if ($graduationDate->isFuture()) {
                return [
                    'valid' => true,
                    'type' => 'undergraduate',
                    'graduation_date' => $graduationDate,
                    'message' => trans('CampusStatus::campus-status.page.email-verify-success-undergraduate'),
                ];
            }

            return [
                'valid' => false,
                'type' => 'undergraduate',
                'graduation_date' => null,
                'message' => trans('CampusStatus::campus-status.page.email-verify-fail-undergraduate'),
            ];
        }

        return [
            'valid' => false,
            'type' => null,
            'graduation_date' => null,
            'message' => trans('CampusStatus::campus-status.page.email-verify-invalid-student'),
        ];
    }

    private function verifyStaffEmail(string $localPart): array
    {
        if (preg_match('/^\d{5}$/', $localPart)) {
            return [
                'valid' => true,
                'type' => 'staff',
                'graduation_date' => null,
                'message' => trans('CampusStatus::campus-status.page.email-verify-success-staff'),
            ];
        }

        return [
            'valid' => false,
            'type' => null,
            'graduation_date' => null,
            'message' => trans('CampusStatus::campus-status.page.email-verify-invalid-staff'),
        ];
    }
}
