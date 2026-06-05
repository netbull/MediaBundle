<?php

declare(strict_types=1);

namespace NetBull\MediaBundle\Http;

/**
 * Guards server-side ("egress") HTTP requests against SSRF.
 *
 * A URL is allowed only when it is http/https and its host resolves entirely to public IP
 * addresses — blocking loopback, private and reserved ranges (e.g. 127.0.0.1, 10.0.0.0/8,
 * 192.168.0.0/16 and the 169.254.169.254 cloud-metadata endpoint).
 *
 * This is a best-effort check: it does not defend against DNS rebinding (the host could resolve
 * differently between this check and the actual request), so callers should also disable redirects
 * (max_redirects: 0) on the request itself.
 */
class UrlSecurityChecker
{
    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);
        if (!\is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (!\in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = $parts['host'];
        // parse_url keeps IPv6 literal hosts wrapped in brackets.
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        $ips = $this->resolveHost($host);
        if ([] === $ips) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!$this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function isPublicIp(string $ip): bool
    {
        return false !== filter_var(
            $ip,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE,
        );
    }

    /**
     * @return string[] the resolved IP addresses (or the host itself when it is an IP literal)
     */
    protected function resolveHost(string $host): array
    {
        if (false !== filter_var($host, \FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        $v4 = gethostbynamel($host);
        if (\is_array($v4)) {
            $ips = $v4;
        }

        $records = @dns_get_record($host, \DNS_AAAA);
        if (\is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return $ips;
    }
}
