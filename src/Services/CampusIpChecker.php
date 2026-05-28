<?php

namespace CampusStatus\Services;

class CampusIpChecker
{
    public function isIpInRanges(string $ip): bool
    {
        $ranges = $this->parseCidrRanges();

        if (empty($ranges)) {
            return false;
        }

        foreach ($ranges as $cidr) {
            if ($this->ipInCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    public function parseCidrRanges(): array
    {
        $raw = option('campus_status_cidr_ranges', '');

        if (empty($raw)) {
            return [];
        }

        $lines = array_filter(
            array_map('trim', explode("\n", $raw)),
            fn ($line) => $line !== ''
        );

        $valid = [];
        foreach ($lines as $line) {
            if ($this->isValidCidr($line)) {
                $valid[] = $line;
            }
        }

        return $valid;
    }

    public function getInvalidCidrEntries(): array
    {
        $raw = option('campus_status_cidr_ranges', '');

        if (empty($raw)) {
            return [];
        }

        $lines = array_filter(
            array_map('trim', explode("\n", $raw)),
            fn ($line) => $line !== ''
        );

        $invalid = [];
        foreach ($lines as $line) {
            if (!$this->isValidCidr($line)) {
                $invalid[] = $line;
            }
        }

        return $invalid;
    }

    public function isValidCidr(string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        [$ip, $prefix] = explode('/', $cidr, 2);

        if (!is_numeric($prefix)) {
            return false;
        }

        $prefix = (int) $prefix;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $prefix >= 0 && $prefix <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $prefix >= 0 && $prefix <= 128;
        }

        return false;
    }

    public function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $prefix] = explode('/', $cidr, 2);
        $prefix = (int) $prefix;

        $ipPacked = inet_pton($ip);
        $subnetPacked = inet_pton($subnet);

        if ($ipPacked === false || $subnetPacked === false) {
            return false;
        }

        if (strlen($ipPacked) !== strlen($subnetPacked)) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipPacked, 0, $fullBytes) !== substr($subnetPacked, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits > 0 && isset($ipPacked[$fullBytes]) && isset($subnetPacked[$fullBytes])) {
            $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
            if ((ord($ipPacked[$fullBytes]) & $mask) !== (ord($subnetPacked[$fullBytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }
}
