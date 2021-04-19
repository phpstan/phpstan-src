<?php declare(strict_types = 1);

use PHPStan\DependencyInjection\NeonAdapter;

$adapter = new NeonAdapter();

$config = [];
if (PHP_VERSION_ID < 70300) {
	$config = array_merge_recursive($config, $adapter->load(__DIR__ . '/baseline-lt-7.3.neon'));
}
if (PHP_VERSION_ID >= 80000) {
	$config = array_merge_recursive($config, $adapter->load(__DIR__ . '/baseline-8.0.neon'));
}

if (PHP_VERSION_ID >= 70400) {
	$config = array_merge_recursive($config, $adapter->load(__DIR__ . '/ignore-gte-php7.4-errors.neon'));
}

return $config;
