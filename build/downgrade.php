<?php declare(strict_types = 1);

return [
	'paths' => [
		__DIR__ . '/../src',
		__DIR__ . '/../tests/PHPStan',
		__DIR__ . '/../tests/e2e',
	],
	'excludePaths' => [
		'tests/*/data/*',
		'tests/*/Fixture/*',
		'tests/PHPStan/Analyser/traits/*',
		'tests/PHPStan/Generics/functions.php',
		'tests/e2e/resultCache_1.php',
		'tests/e2e/resultCache_2.php',
		'tests/e2e/resultCache_3.php',
	],
];
