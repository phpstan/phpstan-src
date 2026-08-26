<?php declare(strict_types = 1);

use PHPStan\Build\TurboAttributeCollector;

/**
 * Generates three files from the ShadowedByTurboExtension and
 * ReferencedByTurboExtension attributes on every autoloader dump
 * (composer.json scripts.post-autoload-dump):
 *
 * - vendor/turbo-stubs.php: one empty stub shell per shadowed class,
 *   extending the phpstan_turbo extension's native counterpart the attribute
 *   names and repeating the class's own implements clause.
 *   TurboExtensionEnabler requires the file before the Composer
 *   autoloader registers, so with the extension active every reference to
 *   the original class name transparently resolves to the native
 *   implementation.
 * - vendor/turbo-shadowed-classes.json: the manifest of shadowed pairs
 *   (each class's PHP source and the .cpp implementing it natively), read
 *   by TurboExtensionEnabler::getShadowedClassSourceFiles(), the compiler's
 *   preload builder, and turbo-ext/tests/signature-parity.php.
 * - vendor/turbo-class-map.php: the class map TurboExtensionEnabler passes
 *   to PHPStanTurbo\Runtime::configure() — one entry per key of the native
 *   class-reference table (pt_class_refs in turbo-ext/src/support.cpp).
 *
 * The collection and rendering live in TurboAttributeCollector, shared with
 * turbo-ext/bin/side-by-side.php, which re-derives the three files and
 * byte-compares them against what this script wrote.
 */

error_reporting(E_ALL);

if (PHP_VERSION_ID < 80200) {
	// the CI downgrade legs dump the autoloader under PHP 7.4–8.1, where the
	// not-yet-downgraded sources cannot be class-loaded (and attribute
	// reflection needs 8.0+). The extension requires 8.3+ anyway, and
	// TurboExtensionEnabler treats a missing stubs file as "stay inactive".
	echo "Skipping turbo-stubs.php generation on PHP < 8.2\n";
	exit(0);
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
require_once __DIR__ . '/PHPStan/Build/TurboAttributeCollector.php';

$collector = new TurboAttributeCollector($root);
$collected = $collector->collect();

file_put_contents($root . '/vendor/turbo-stubs.php', $collector->renderStubs($collected['pairs']));
file_put_contents($root . '/vendor/turbo-shadowed-classes.json', $collector->renderManifestJson($collected['manifest']));
file_put_contents($root . '/vendor/turbo-class-map.php', $collector->renderClassMap($collected['classMap']));

echo sprintf("Generated turbo-stubs.php, turbo-shadowed-classes.json (%d classes) and turbo-class-map.php (%d entries)\n", count($collected['pairs']), count($collected['classMap']));
