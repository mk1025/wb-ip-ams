<?php

namespace App\Helpers;

class IpValidator
{
    // validate if string is a valid IP address (IPv4 or IPv6)
    public static function isValid(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    // Check if IP is IPv4
    public static function isIPv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    // Check if IP is IPv6
    public static function isIPv6(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    // Get IP version (4 or 6) or null if invalid
    public static function getVersion(string $ip): ?int
    {
        if (self::isIPv4($ip)) {
            return 4;
        }
        if (self::isIPv6($ip)) {
            return 6;
        }
        return null;
    }
}
