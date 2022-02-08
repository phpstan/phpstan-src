<?php declare(strict_types = 1);

use PHPStan\DependencyInjection\NeonAdapter;

$adapter = new NeonAdapter();

if (PHP_INT_SIZE === 4) {
	return $adapter->load(__DIR__ . '/baseline-32bit.neon');
}

return [];
