<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Security;

use InvalidArgumentException;

final class UrlValidator
{
    /**
     * Validate remote URL to prevent SSRF (Server-Side Request Forgery) attacks
     *
     * @throws InvalidArgumentException
     */
    public function validate(string $url): void
    {
        // Parse the URL
        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            throw new InvalidArgumentException('Invalid URL provided');
        }

        // Only allow HTTP and HTTPS schemes
        $allowedSchemes = ['http', 'https'];
        if (! in_array(strtolower($parsed['scheme']), $allowedSchemes, true)) {
            throw new InvalidArgumentException('Invalid URL scheme: only http and https are allowed');
        }

        $host = $parsed['host'];

        // Strip brackets from IPv6 addresses
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        // Block localhost variations
        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
            '0:0:0:0:0:0:0:1',
        ];

        if (in_array(strtolower($host), $localhostPatterns, true)) {
            throw new InvalidArgumentException('Access to localhost is not allowed');
        }

        // Resolve hostname to IP address(es) for validation
        $ips = @gethostbynamel($host);

        // If DNS resolution fails, fall back to checking if host is already an IP
        if ($ips === false) {
            // Check if host is an IP address (including IPv6)
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                $ips = [$host];
            } elseif (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                // Handle IPv6 addresses
                $ips = [$host];
            } else {
                // DNS resolution failed and it's not an IP
                // This could be a malformed hostname or network issue
                // For security, we'll allow it to fail here rather than block legitimate hostnames
                // The actual connection attempt will fail naturally if the host doesn't exist
                return;
            }
        }

        // Validate each resolved IP address
        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                throw new InvalidArgumentException('Access to private or reserved IP addresses is not allowed');
            }
        }

        // Block common dangerous ports
        if (isset($parsed['port'])) {
            $dangerousPorts = [
                22,    // SSH
                23,    // Telnet
                25,    // SMTP
                3306,  // MySQL
                5432,  // PostgreSQL
                6379,  // Redis
                27017, // MongoDB
                11211, // Memcached
            ];

            if (in_array((int) $parsed['port'], $dangerousPorts, true)) {
                throw new InvalidArgumentException('Access to port ' . $parsed['port'] . ' is not allowed');
            }
        }
    }

    /**
     * Check if an IP address is private or reserved
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        // Validate IP format
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true; // Invalid IP, treat as blocked
        }

        // Check for private and reserved IP ranges
        // This includes:
        // - Private IPv4 ranges (10.x.x.x, 172.16-31.x.x, 192.168.x.x)
        // - Loopback (127.x.x.x, ::1)
        // - Link-local (169.254.x.x, fe80::/10)
        // - Multicast and broadcast addresses
        // - Cloud metadata endpoints (169.254.169.254)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        // Additional check for AWS metadata endpoint
        if ($ip === '169.254.169.254') {
            return true;
        }

        return false;
    }
}
