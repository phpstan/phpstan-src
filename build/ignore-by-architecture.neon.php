<?php declare(strict_types = 1);

if (PHP_INT_SIZE === 4) {
	$config = [];
	$config['includes'] = [
		__DIR__ . '/baseline-32bit.neon',
	];

	return $config;
}

return [];
