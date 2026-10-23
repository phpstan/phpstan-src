<?php declare(strict_types = 1);

use PHPStan\Build\TurboAttributeCollector;
use PHPStan\Build\TurboExtensionVersion;

/**
 * Generates three files on every autoloader dump
 * (composer.json scripts.post-autoload-dump), two of them from the
 * ShadowedByTurboExtension and ReferencedByTurboExtension attributes:
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
 * - vendor/turbo-extension-version.php: the extension version
 *   TurboExtensionEnabler activates, from git (TurboExtensionVersion). The
 *   phar compilation dumps the autoloader too, so the phar carries it baked
 *   in. `make build-turbo` reruns this script, so a binary rebuilt after a
 *   turbo-ext/src commit is expected without another dump.
 *
 * The collection and rendering live in TurboAttributeCollector and
 * TurboExtensionVersion, shared with turbo-ext/bin/side-by-side.php, which
 * re-derives the three files and byte-compares them against what this script
 * wrote.
 */

error_reporting(E_ALL);

if (PHP_VERSION_ID < 80200) {
	// the CI downgrade legs dump the autoloader under PHP 7.4–8.1, where the
	// not-yet-downgraded sources cannot be class-loaded (and attribute
	// reflection needs 8.0+). The extension requires 8.3+ anyway, and
	// TurboExtensionEnabler treats a missing manifest or version as "stay
	// inactive".
	echo "Skipping turbo manifest generation on PHP < 8.2\n";
	exit(0);
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
require_once __DIR__ . '/PHPStan/Build/TurboAttributeCollector.php';
require_once __DIR__ . '/PHPStan/Build/TurboExtensionVersion.php';

$collector = new TurboAttributeCollector($root);
$collected = $collector->collect();

file_put_contents($root . '/vendor/turbo-shadowed-classes.json', $collector->renderManifestJson($collected['manifest']));
file_put_contents($root . '/vendor/turbo-class-map.php', $collector->renderClassMap($collected['classMap']));
$version = TurboExtensionVersion::resolve($root);
file_put_contents($root . '/vendor/turbo-extension-version.php', TurboExtensionVersion::render($version));

echo sprintf("Generated turbo-shadowed-classes.json (%d classes), turbo-class-map.php (%d entries) and turbo-extension-version.php (%s)\n", count($collected['manifest']), count($collected['classMap']), $version ?? 'no git checkout, the extension stays inactive');
