<?php

namespace CampusStatus\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 注意：本类所有数据库写入使用 MySQL 方言（INSERT ... ON DUPLICATE KEY UPDATE），
 * 如需移植到 PostgreSQL / SQLite，需替换为 ON CONFLICT ... DO UPDATE / INSERT OR REPLACE。
 */
class AutoVerifier
{
    public static function verify(int $uid, string $email): bool
    {
        return (new self())->verifyUserByEmail($uid, $email);
    }

    public static function verifyByIp(int $uid, string $ip): bool
    {
        return (new self())->verifyUserByIp($uid, $ip);
    }

    public function verifyUserByIp(int $uid, string $ip): bool
    {
        $checker = new CampusIpChecker();
        if (!$checker->isIpInRanges($ip)) {
            // IF(verified_at IS NULL, ...) 保证已有成功记录不会被失败覆盖，
            // 仅失败记录会更新 ip 以追踪最近一次尝试
            DB::statement(
                'INSERT INTO campus_status_records (uid, ip, verified_at, expires_at) VALUES (?, ?, NULL, NULL) ON DUPLICATE KEY UPDATE ip = IF(verified_at IS NULL, VALUES(ip), ip)',
                [$uid, $ip]
            );

            return false;
        }

        $validityDays = (int) option('campus_status_validity_days', 365);
        $verifiedAt = now();
        $expiresAt = $verifiedAt->copy()->addDays($validityDays);

        DB::statement(
            'INSERT INTO campus_status_records (uid, ip, verified_at, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE ip = VALUES(ip), verified_at = VALUES(verified_at), expires_at = VALUES(expires_at)',
            [$uid, $ip, $verifiedAt, $expiresAt]
        );

        return true;
    }

    public function verifyUserByEmail(int $uid, string $email): bool
    {
        $verifier = new EmailVerifier();
        $result = $verifier->verify($email);

        if (!$result['valid']) {
            // IF(verified_at IS NULL, ...) 保证已有成功记录不会被失败覆盖
            DB::statement(
                'INSERT INTO campus_status_records (uid, ip, verified_at, expires_at) VALUES (?, ?, NULL, NULL) ON DUPLICATE KEY UPDATE ip = IF(verified_at IS NULL, VALUES(ip), ip)',
                [$uid, 'email_failed']
            );

            return false;
        }

        $validityDays = (int) option('campus_status_validity_days', 365);
        $verifiedAt = now();
        $expiresAt = $verifiedAt->copy()->addDays($validityDays);

        if ($result['graduation_date'] && $result['graduation_date']->lt($expiresAt)) {
            $expiresAt = $result['graduation_date'];
        }

        DB::statement(
            'INSERT INTO campus_status_records (uid, ip, verified_at, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE ip = VALUES(ip), verified_at = VALUES(verified_at), expires_at = VALUES(expires_at)',
            [$uid, 'email', $verifiedAt, $expiresAt]
        );

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
