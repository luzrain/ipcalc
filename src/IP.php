<?php

declare(strict_types=1);

namespace IPCalc;

/**
 * IPv4/IPv6 Network calculator for PHP
 */
final class IP implements \JsonSerializable
{
    private const PRIVATE_SUBNETS = [
        '127.0.0.0/8',    // RFC1700 (Loopback)
        '10.0.0.0/8',     // RFC1918
        '192.168.0.0/16', // RFC1918
        '172.16.0.0/12',  // RFC1918
        '169.254.0.0/16', // RFC3927
        '0.0.0.0/8',      // RFC5735
        '240.0.0.0/4',    // RFC1112
        '::1/128',        // Loopback
        'fc00::/7',       // Unique Local Address
        'fe80::/10',      // Link Local Address
        '::ffff:0:0/96',  // IPv4 translations
        '::/128',         // Unspecified address
    ];

    private string $ip;
    private int $cidr;
    private int $version;
    private bool $private;
    private string|int $ipLong;
    private string|int $netmaskLong;
    private string|array $ipLongBytesCache;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct(string $ip, int|null $cidr = null)
    {
        if ($cidr === null && \str_contains($ip, '/')) {
            [$ip, $cidr] = \explode('/', $ip, 2);
            if ($cidr === '') {
                throw new \InvalidArgumentException('The provided netmask is not valid');
            }
            $cidr = (int) $cidr;
        }

        if (\filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $cidr ??= 32;
            if ($cidr < 0 || $cidr > 32) {
                throw new \InvalidArgumentException('The provided netmask is not valid');
            }
            $this->version = 4;
            $this->ip = $ip;
            $this->ipLong = \ip2long($ip);
            $this->netmaskLong = $this->netmaskV4($cidr);
        } elseif (\filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $cidr ??= 128;
            if ($cidr < 0 || $cidr > 128) {
                throw new \InvalidArgumentException('The provided netmask is not valid');
            }
            $this->version = 6;
            $this->ip = \inet_ntop(\inet_pton($ip));
            $this->ipLong = \inet_pton($ip);
            $this->netmaskLong = $this->netmaskV6($cidr);
        } else {
            throw new \InvalidArgumentException('The provided ip address is not valid');
        }

        $this->cidr = $cidr;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getNetmask(): string
    {
        if ($this->version === 4) {
            return \long2ip($this->netmaskLong);
        }

        return \inet_ntop($this->netmaskLong);
    }

    public function getCidr(): int
    {
        return $this->cidr;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isPrivate(): bool
    {
        if (isset($this->private)) {
            return $this->private;
        }

        foreach (self::PRIVATE_SUBNETS as $subnet) {
            if ((new self($subnet))->contains($this->ip)) {
                $this->private = true;
                return true;
            }
        }

        $this->private = false;
        return false;
    }

    public function getNetwork(): string
    {
        if ($this->version === 4) {
            return \long2ip($this->ipLong & $this->netmaskLong);
        }

        return \inet_ntop($this->ipLong & $this->netmaskLong);
    }

    public function getBroadcast(): string
    {
        if ($this->version === 4) {
            return \long2ip($this->ipLong | ~$this->netmaskLong);
        }

        return \inet_ntop($this->ipLong | ~$this->netmaskLong);
    }

    public function getHostMin(): string
    {
        if ($this->version === 4) {
            $adj = $this->cidr === 32 || $this->cidr === 31 ? 0 : 1;
            return \long2ip(($this->ipLong & $this->netmaskLong) + $adj);
        }

        return \inet_ntop($this->ipLong & $this->netmaskLong);
    }

    public function getHostMax(): string
    {
        if ($this->version === 4) {
            $adj = $this->cidr === 32 || $this->cidr === 31 ? 0 : 1;
            return \long2ip(($this->ipLong | ~$this->netmaskLong) - $adj);
        }

        return \inet_ntop($this->ipLong | ~$this->netmaskLong);
    }

    public function contains(string $ip): bool
    {
        return $this->version === 4 ? $this->containsV4($ip) : $this->containsV6($ip);
    }

    public function jsonSerialize(): array
    {
        return [
            'ip' => $this->getIp(),
            'netmask' => $this->getNetmask(),
            'cidr' => $this->getCidr(),
            'version' => $this->getVersion(),
            'private' => $this->isPrivate(),
            'network' => $this->getNetwork(),
            'broadcast' => $this->getBroadcast(),
            'hostmin' => $this->getHostMin(),
            'hostmax' => $this->getHostMax(),
        ];
    }

    private function netmaskV4(int $cidr): int
    {
        return 0xFFFFFFFF << (32 - $cidr);
    }

    private function netmaskV6(int $cidr): string
    {
        $m = \str_repeat('1', $cidr) . \str_repeat('0', 128 - $cidr);
        $bin = '';
        foreach (\str_split($m, 8) as $byte) {
            $bin .= \pack('C', \bindec($byte));
        }

        return $bin;
    }

    private function containsV4(string $ip): bool
    {
        if (\str_contains($ip, ':') || $ip === '') {
            return false;
        }

        $ipBytes = $this->ipLongBytesCache ??= \str_pad(\decbin($this->ipLong), 32, '0', STR_PAD_LEFT);
        $inputIpBytes = \str_pad(\decbin((int) \ip2long($ip)), 32, '0', STR_PAD_LEFT);

        return \substr_compare($inputIpBytes, $ipBytes, 0, $this->cidr) === 0;
    }

    private function containsV6(string $ip): bool
    {
        if (!\str_contains($ip, ':') || $ip === '') {
            return false;
        }

        $bytesTest = \unpack('n*', (string) @\inet_pton($ip));

        if (!$bytesTest) {
            return false;
        }

        $bytesAddr = $this->ipLongBytesCache ??= \unpack('n*', (string) $this->ipLong);
        for ($i = 1, $ceil = \ceil($this->cidr / 16); $i <= $ceil; ++$i) {
            $left = $this->cidr - 16 * ($i - 1);
            $left = ($left <= 16) ? $left : 16;
            $mask = ~(0xFFFF >> $left) & 0xFFFF;
            if (($bytesAddr[$i] & $mask) !== ($bytesTest[$i] & $mask)) {
                return false;
            }
        }

        return true;
    }
}
