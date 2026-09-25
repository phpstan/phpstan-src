<?php declare(strict_types = 1);

$includes = [];
if (PHP_VERSION_ID >= 80000) {
	$includes[] = __DIR__ . '/baseline-8.0.neon';
} else {
	$includes[] = __DIR__ . '/baseline-pre-8.0.neon';
}
if (PHP_VERSION_ID < 80100) {
	$includes[] = __DIR__ . '/enums.neon';
	$includes[] = __DIR__ . '/readonly-property.neon';
	$includes[] = __DIR__ . '/turbo-declaration-generator-pre-8.1.neon';
}

// PHP 8.1's serialize() does not trim its buffer, so the rule's cache keys cost 4 KB each
if (PHP_VERSION_ID >= 80200) {
	$includes[] = __DIR__ . '/../vendor/shipmonk/dead-code-detector/rules.neon';
}

if (PHP_VERSION_ID >= 70400) {
	$includes[] = __DIR__ . '/ignore-gte-php7.4-errors.neon';
}

if (PHP_VERSION_ID < 80000) {
	$includes[] = __DIR__ . '/more-enum-adapter-errors.neon';
}

if (PHP_VERSION_ID < 80200) {
	$includes[] = __DIR__ . '/randomizer.neon';
}

if (PHP_VERSION_ID >= 80000) {
	$includes[] = __DIR__ . '/spl-autoload-functions-php-8.neon';
}

if (PHP_VERSION_ID >= 80400) {
	$includes[] = __DIR__ . '/deprecated-8.4.neon';
}

if (PHP_VERSION_ID < 80200) {
	$includes[] = __DIR__ . '/old-phpunit.neon';
} else {
	$includes[] = __DIR__ . '/new-phpunit.neon';
}

if (PHP_VERSION_ID < 80500) {
	$includes[] = __DIR__ . '/pre-php-85.neon';
} else {
	$includes[] = __DIR__ . '/php-85.neon';
}

$config = [];
$config['includes'] = $includes;

// overrides config.platform.php in composer.json
$config['parameters']['phpVersion'] = PHP_VERSION_ID;

return $config;
