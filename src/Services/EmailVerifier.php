<?php

namespace CampusStatus\Services;

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
            'message' => trans('CampusStatus::campus-status.page.email-verify-not-ecust'),
        ];
    }

    private function verifyStudentEmail(string $localPart): array
    {
        if (preg_match('/^[Yy](\d{8})$/', $localPart, $matches)) {
            $studentNumber = $matches[1];
            $enrollYear = (int) substr($studentNumber, 0, 2);
            $currentYear = (int) date('y');

            if ($currentYear - $enrollYear < 2) {
                return [
                    'valid' => true,
                    'type' => 'graduate',
                    'message' => trans('CampusStatus::campus-status.page.email-verify-success-graduate'),
                ];
            }

            return [
                'valid' => false,
                'type' => 'graduate',
                'message' => trans('CampusStatus::campus-status.page.email-verify-fail-graduate'),
            ];
        }

        if (preg_match('/^(\d{8})$/', $localPart, $matches)) {
            $enrollYear = (int) substr($matches[1], 0, 2);
            $currentYear = (int) date('y');

            if ($currentYear - $enrollYear < 4) {
                return [
                    'valid' => true,
                    'type' => 'undergraduate',
                    'message' => trans('CampusStatus::campus-status.page.email-verify-success-undergraduate'),
                ];
            }

            return [
                'valid' => false,
                'type' => 'undergraduate',
                'message' => trans('CampusStatus::campus-status.page.email-verify-fail-undergraduate'),
            ];
        }

        return [
            'valid' => false,
            'type' => null,
            'message' => trans('CampusStatus::campus-status.page.email-verify-invalid-student'),
        ];
    }

    private function verifyStaffEmail(string $localPart): array
    {
        if (preg_match('/^\d{5}$/', $localPart)) {
            return [
                'valid' => true,
                'type' => 'staff',
                'message' => trans('CampusStatus::campus-status.page.email-verify-success-staff'),
            ];
        }

        return [
            'valid' => false,
            'type' => null,
            'message' => trans('CampusStatus::campus-status.page.email-verify-invalid-staff'),
        ];
    }
}
