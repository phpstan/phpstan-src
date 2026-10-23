<?php declare(strict_types = 1);

/**
 * Prints the phpstan_turbo version this checkout expects (see
 * PHPStan\Build\TurboExtensionVersion) — what the workflows compare a built
 * binary's phpversion('phpstan_turbo') against. Needs no Composer
 * dependencies, so it runs in the compile jobs too. Fails without a usable
 * git checkout.
 */

use PHPStan\Build\TurboExtensionVersion;

require_once __DIR__ . '/PHPStan/Build/TurboExtensionVersion.php';

$version = TurboExtensionVersion::resolve(dirname(__DIR__));
if ($version === null) {
	fwrite(STDERR, "Could not read the last commit touching turbo-ext/src from git\n");
	exit(1);
}

echo $version;
