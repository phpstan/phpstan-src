<?php declare(strict_types = 1);

$includes = [];
if (PHP_VERSION_ID >= 80000) {
	$includes[] = __DIR__ . '/baseline-8.0.neon';
}
if (PHP_VERSION_ID >= 80100) {
	$includes[] = __DIR__ . '/baseline-8.1.neon';
} else {
	$includes[] = __DIR__ . '/enums.neon';
	$includes[] = __DIR__ . '/readonly-property.neon';
}

if (PHP_VERSION_ID >= 70400) {
	$includes[] = __DIR__ . '/ignore-gte-php7.4-errors.neon';
}

if (PHP_VERSION_ID < 80000) {
	$includes[] = __DIR__ . '/more-enum-adapter-errors.neon';
}

if (PHP_VERSION_ID < 80000) {
	$includes[] = __DIR__ . '/spl-autoload-functions-pre-php-7.neon';
} else {
	$includes[] = __DIR__ . '/spl-autoload-functions-php-8.neon';
}

if (PHP_VERSION_ID >= 80300) {
	$includes[] = __DIR__ . '/datetime-php-83.neon';
}

if (PHP_VERSION_ID >= 80400) {
	$includes[] = __DIR__ . '/deprecated-8.4.neon';
}

if (PHP_VERSION_ID < 80200) {
	$includes[] = __DIR__ . '/old-phpunit.neon';
} else {
	$includes[] = __DIR__ . '/new-phpunit.neon';
}

if (PHP_VERSION_ID < 80300) {
	$includes[] = __DIR__ . '/str-increment.neon';
}

$config = [];
$config['includes'] = $includes;

// overrides config.platform.php in composer.json
$config['parameters']['phpVersion'] = PHP_VERSION_ID;

return $config;
