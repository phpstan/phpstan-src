<?php declare(strict_types = 1);

use PHPStan\Build\TurboAttributeCollector;

/**
 * Generates two files from the ShadowedByTurboExtension and
 * ReferencedByTurboExtension attributes on every autoloader dump
 * (composer.json scripts.post-autoload-dump):
 *
 * - vendor/turbo-shadowed-classes.json: the manifest of shadowed pairs
 *   (each class's PHP source, the .cpp implementing it natively, and the
 *   final flag, parent and interfaces the native class must declare), read
 *   by TurboExtensionEnabler::activateIfCompatible() — the native classes
 *   are declared with their twins' source files — the compiler's preload
 *   builder, and the parity tooling.
 * - vendor/turbo-class-map.php: the class map TurboExtensionEnabler passes
 *   to PHPStanTurbo\Runtime::configure() — one entry per key of the native
 *   class-reference table (pt_class_refs in turbo-ext/src/support.cpp).
 *
 * The collection and rendering live in TurboAttributeCollector, shared with
 * turbo-ext/bin/side-by-side.php, which re-derives the two files and
 * byte-compares them against what this script wrote.
 */

error_reporting(E_ALL);

if (PHP_VERSION_ID < 80200) {
	// the CI downgrade legs dump the autoloader under PHP 7.4–8.1, where the
	// not-yet-downgraded sources cannot be class-loaded (and attribute
	// reflection needs 8.0+). The extension requires 8.3+ anyway, and
	// TurboExtensionEnabler treats a missing manifest as "stay inactive".
	echo "Skipping turbo manifest generation on PHP < 8.2\n";
	exit(0);
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
require_once __DIR__ . '/PHPStan/Build/TurboAttributeCollector.php';

$collector = new TurboAttributeCollector($root);
$collected = $collector->collect();

file_put_contents($root . '/vendor/turbo-shadowed-classes.json', $collector->renderManifestJson($collected['manifest']));
file_put_contents($root . '/vendor/turbo-class-map.php', $collector->renderClassMap($collected['classMap']));

echo sprintf("Generated turbo-shadowed-classes.json (%d classes) and turbo-class-map.php (%d entries)\n", count($collected['manifest']), count($collected['classMap']));
