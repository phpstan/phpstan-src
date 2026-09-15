<?php declare(strict_types = 1);

/**
 * Generates turbo-ext/src/generated/<Stem>.h — the class declaration, the
 * property slots and the property declarations of every shadowing class,
 * derived from its PHP twin (PHPStan\Build\TurboDeclarationGenerator). Run
 * it after changing a shadowed class's declaration; side-by-side.php fails
 * while a generated header is stale.
 *
 * Usage: php turbo-ext/bin/generate-declarations.php
 *
 * Requires vendor/ (run composer install first).
 */

use PHPStan\Build\TurboAttributeCollector;
use PHPStan\Build\TurboDeclarationGenerator;

error_reporting(E_ALL);

$root = dirname(__DIR__, 2);
chdir($root);

require 'vendor/autoload.php';
require_once 'build/PHPStan/Build/TurboAttributeCollector.php';
require_once 'build/PHPStan/Build/TurboDeclarationGenerator.php';

$collected = (new TurboAttributeCollector($root))->collect();
$files = (new TurboDeclarationGenerator($collected['manifest']))->render();

$dir = 'turbo-ext/src/generated';
if (!is_dir($dir) && !mkdir($dir)) {
	fwrite(STDERR, "cannot create $dir\n");
	exit(1);
}

$written = 0;
foreach ($files as $path => $content) {
	if (is_file($path) && file_get_contents($path) === $content) {
		continue;
	}
	file_put_contents($path, $content);
	$written++;
}
$removed = 0;
foreach (glob($dir . '/*.h') ?: [] as $existing) {
	if (!isset($files[$existing])) {
		unlink($existing);
		$removed++;
	}
}
$withoutProperties = 0;
foreach ($files as $content) {
	if (str_contains($content, '/* no declareProperties():')) {
		$withoutProperties++;
	}
}

printf("%d headers (%d written, %d removed); %d without declareProperties()\n", count($files), $written, $removed, $withoutProperties);
