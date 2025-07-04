# 🌐 IPv4/IPv6 Network calculator for PHP

![PHP >=8.0](https://img.shields.io/badge/PHP->=8.0-777bb3.svg?style=flat)
![Tests Status](https://img.shields.io/github/actions/workflow/status/luzrain/ipcalc/tests.yaml?branch=main&label=Tests)
![Downloads](https://img.shields.io/packagist/dt/luzrain/ipcalc?label=Downloads&color=f28d1a)

This IP Network Calculator library supports both IPv4 and IPv6, offering functionality to compute usable host ranges, retrieve subnet masks, and determine whether a given IP address belongs to a specific network.

## Installation

Install with composer:
```bash
$ composer require luzrain/ipcalc
```

## Usage

Create IPCalc\IP instance
```php
$net = new IPCalc\IP('192.168.1.1/24');
// or
$net = new IPCalc\IP('192.168.1.1', 24);
```

You can then retrieve various properties of the network:
```php
$net->getIp();                  // 192.168.1.1    // The original IP address
$net->getNetmask();             // 255.255.255.0  // Subnet mask
$net->getCidr();                // 24             // CIDR prefix length
$net->getVersion();             // 4              // IP version (4 or 6)
$net->isPrivate();              // true           // Returns true if the IP address is in a private range
$net->getNetwork();             // 192.168.1.0    // Network address of the subnet (IPv4 only)
$net->getBroadcast();           // 192.168.1.255  // Broadcast address of the network (IPv4 only)
$net->getHostMin();             // 192.168.1.1    // First usable IP address in the subnet
$net->getHostMax();             // 192.168.1.254  // Last usable IP address in the subnet
$net->contains('192.168.1.10'); // true           // Returns true if the given IP address is within the network
```

#### Notes:

Although IPv6 does not use the concept of networks and broadcasts, the ranges are still needed to do inclusive searches. Also, IPv6 has a subnet segment, but can still be supernetted/subnetted, which this takes into consideration.
