<?php

declare(strict_types=1);

namespace IPCalc\Test;

use IPCalc\IP;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IPTest extends TestCase
{
    #[DataProvider('ipCalculatorData')]
    public function testIpCalculator(string $ipAddr, array $details): void
    {
        $ip = new IP($ipAddr);

        $this->assertSame($details['ip'], $ip->getIp(), \sprintf('Failed asserting that ip is "%s"', $details['ip']));
        $this->assertSame($details['cidr'], $ip->getCidr(), \sprintf('Failed asserting that cidr is "%s"', $details['cidr']));
        $this->assertSame($details['netmask'], $ip->getNetmask(), \sprintf('Failed asserting that netmask is "%s"', $details['netmask']));
        $this->assertSame($details['version'], $ip->getVersion(), \sprintf('Failed asserting that "%s" %s version', $ipAddr, $details['version']));
        $this->assertSame($details['private'], $ip->isPrivate(), \sprintf('Failed asserting that "%s" is %s', $ipAddr, $details['private'] ? 'private' : 'public'));
        $this->assertSame($details['network'], $ip->getNetwork(), \sprintf('Failed asserting that network is "%s"', $details['network']));
        $this->assertSame($details['broadcast'], $ip->getBroadcast(), \sprintf('Failed asserting that broadcast is "%s"', $details['broadcast']));
        $this->assertSame($details['hostmin'], $ip->getHostMin(), \sprintf('Failed asserting that hostmin is "%s"', $details['hostmin']));
        $this->assertSame($details['hostmax'], $ip->getHostMax(), \sprintf('Failed asserting that hostmax is "%s"', $details['hostmax']));
    }

    #[DataProvider('invalidIpAddressData')]
    public function testInvalidIpAddress(string $ipAddr): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $ip = new IP($ipAddr);
    }

    #[DataProvider('subnetContainsData')]
    public function testSubnetContains(string $ipAddr, array $contains, array $notContains): void
    {
        $ip = new IP($ipAddr);

        foreach ($contains as $containsIp) {
            $this->assertTrue($ip->contains($containsIp), \sprintf('Failed asserting that "%s" contains "%s"', $ipAddr, $containsIp));
        }

        foreach ($notContains as $notContainsIp) {
            $this->assertFalse($ip->contains($notContainsIp), \sprintf('Failed asserting that "%s" not contains "%s"', $ipAddr, $notContainsIp));
        }
    }

    public static function ipCalculatorData(): iterable
    {
        yield ['127.0.0.1', [
            'ip' => '127.0.0.1',
            'cidr' => 32,
            'netmask' => '255.255.255.255',
            'version' => 4,
            'private' => true,
            'network' => '127.0.0.1',
            'broadcast' => '127.0.0.1',
            'hostmin' => '127.0.0.1',
            'hostmax' => '127.0.0.1',
        ]];

        yield ['172.16.1.5/31', [
            'ip' => '172.16.1.5',
            'cidr' => 31,
            'netmask' => '255.255.255.254',
            'version' => 4,
            'private' => true,
            'network' => '172.16.1.4',
            'broadcast' => '172.16.1.5',
            'hostmin' => '172.16.1.4',
            'hostmax' => '172.16.1.5',
        ]];

        yield ['192.168.1.10/30', [
            'ip' => '192.168.1.10',
            'cidr' => 30,
            'netmask' => '255.255.255.252',
            'version' => 4,
            'private' => true,
            'network' => '192.168.1.8',
            'broadcast' => '192.168.1.11',
            'hostmin' => '192.168.1.9',
            'hostmax' => '192.168.1.10',
        ]];

        yield ['192.168.1.100/24', [
            'ip' => '192.168.1.100',
            'cidr' => 24,
            'netmask' => '255.255.255.0',
            'version' => 4,
            'private' => true,
            'network' => '192.168.1.0',
            'broadcast' => '192.168.1.255',
            'hostmin' => '192.168.1.1',
            'hostmax' => '192.168.1.254',
        ]];

        yield ['104.16.0.0/13', [
            'ip' => '104.16.0.0',
            'cidr' => 13,
            'netmask' => '255.248.0.0',
            'version' => 4,
            'private' => false,
            'network' => '104.16.0.0',
            'broadcast' => '104.23.255.255',
            'hostmin' => '104.16.0.1',
            'hostmax' => '104.23.255.254',
        ]];

        yield ['fe80:dead::3/93', [
            'ip' => 'fe80:dead::3',
            'cidr' => 93,
            'netmask' => 'ffff:ffff:ffff:ffff:ffff:fff8::',
            'version' => 6,
            'private' => true,
            'network' => 'fe80:dead::',
            'broadcast' => 'fe80:dead::7:ffff:ffff',
            'hostmin' => 'fe80:dead::',
            'hostmax' => 'fe80:dead::7:ffff:ffff',
        ]];

        yield ['2606:4700::/32', [
            'ip' => '2606:4700::',
            'cidr' => 32,
            'netmask' => 'ffff:ffff::',
            'version' => 6,
            'private' => false,
            'network' => '2606:4700::',
            'broadcast' => '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
            'hostmin' => '2606:4700::',
            'hostmax' => '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
        ]];
    }

    public static function invalidIpAddressData(): iterable
    {
        yield ['127.0.0'];
        yield ['172.16.1.256'];
        yield ['abcde'];
        yield ['1'];
        yield ['172.16.1.1/64'];
        yield ['172.16.1.1/'];
        yield ['260v:4700::/32'];
        yield ['2606:4700::/129'];
        yield ['2606::4700::/32'];
    }

    public static function subnetContainsData(): iterable
    {
        yield ['172.16.1.1/24', [
            '172.16.1.0',
            '172.16.1.1',
            '172.16.1.254',
            '172.16.1.255',
        ], [
            '172.16.2.0',
            '172.16.2.1',
            '173.245.48.100',
            '192.168.1.1'
        ]];

        yield ['104.16.0.0/13', [
            '104.16.0.0',
            '104.16.0.1',
            '104.23.255.254',
            '104.23.255.255',
            '104.19.101.31',
        ], [
            '104.15.255.255',
            '104.24.0.1',
            '173.245.48.100',
            '88.151.194.24',
            '2606:46ff::1',
        ]];

        yield ['2606:4700::/32', [
            '2606:4700::',
            '2606:4700::1',
            '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff',
            '2606:4700:abcd:1:2::1',
        ], [
            '2606:46ff:ffff:ffff:ffff:ffff:ffff:ffff',
            '2606:4701:abcd:1:2::1',
            '2606:46ff::1',
            'fe80:dead::3',
            '172.16.1.0',
        ]];

        yield ['fe80:dead::3/98', [
            'fe80:dead::',
            'fe80:dead::1',
            'fe80:dead::3fff:ffff',
        ], [
            'fe80:deae:ffff:ffff:ffff:ffff:ffff:ffff',
            'fe80:dead::4000::',
            'fe80:dead::4000:1',
            'fe80:dead:1::3fff:ffff',
        ]];
    }
}
