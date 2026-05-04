<?php

declare(strict_types=1);

namespace Homedns;

class DnsResolver
{
    /**
     * Résout un hôte DNS et retourne l'adresse IP
     */
    public static function resolve(string $host): array
    {
        $dnsHost = preg_replace('~:\d+$~', '', $host) ?? $host;
        $result = [
            'up' => false,
            'address' => null,
        ];

        $records = @dns_get_record($dnsHost, DNS_A | DNS_AAAA);
        if (is_array($records) && $records !== []) {
            $result['up'] = true;
            foreach ($records as $record) {
                if (!empty($record['ip'])) {
                    $result['address'] = $record['ip'];
                    break;
                }
                if (!empty($record['ipv6'])) {
                    $result['address'] = $record['ipv6'];
                    break;
                }
            }

            return $result;
        }

        $resolved = @gethostbyname($dnsHost);
        if ($resolved !== $dnsHost) {
            $result['up'] = true;
            $result['address'] = $resolved;
        }

        return $result;
    }
}
