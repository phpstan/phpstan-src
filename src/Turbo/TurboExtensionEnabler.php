<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PHPStanTurbo\Runtime;
use function dirname;
use function extension_loaded;
use function file_get_contents;
use function getenv;
use function is_file;
use function json_decode;
use function phpversion;

final class TurboExtensionEnabler
{

	/**
	 * The native classes must match the PHP implementations exactly, so the
	 * extension is only enabled when its version is the expected one. The
	 * version is the short SHA of the last commit touching turbo-ext/src/,
	 * enforced by the phar.yml turbo-version job.
	 */
	public const EXPECTED_EXTENSION_VERSION = '4cc5e5e';

	private static bool $typeCombinatorCacheEnabled = false;

	private static bool $enabled = false;

	public static function isLoaded(): bool
	{
		return extension_loaded('phpstan_turbo');
	}

	/**
	 * The version of the loaded extension when it does not pass the
	 * enableIfLoaded() version gate. Null when the extension is not loaded,
	 * deliberately disabled, or compatible.
	 */
	public static function getIncompatibleLoadedVersion(): ?string
	{
		if (!self::isLoaded()) {
			return null;
		}

		if (getenv('PHPSTAN_TURBO') === '0') {
			return null;
		}

		$version = phpversion('phpstan_turbo');
		if ($version === self::EXPECTED_EXTENSION_VERSION) {
			return null;
		}

		return $version === false ? 'unknown' : $version;
	}

	/**
	 * Whether enableIfLoaded() actually activated the extension — the stubs
	 * shadow the PHP implementations only in that case.
	 */
	public static function isActive(): bool
	{
		return self::$enabled;
	}

	/**
	 * The real source files of the shadowed classes. With the extension
	 * active, the class names are declared by the stub shells, so reflection
	 * needs these files fed to it explicitly — resolving the class names
	 * through the autoloader would reflect the stubs. The manifest is
	 * generated next to the stubs by build/generate-turbo-stubs.php.
	 *
	 * @return list<string>
	 */
	public static function getShadowedClassSourceFiles(): array
	{
		$root = dirname(__DIR__, 2);
		$manifestPath = $root . '/vendor/turbo-shadowed-classes.json';
		if (!is_file($manifestPath)) {
			return [];
		}

		$manifestContents = file_get_contents($manifestPath);
		if ($manifestContents === false) {
			return [];
		}

		/** @var array<string, array{php: string, cpp: string, vendored?: bool}> $manifest */
		$manifest = json_decode($manifestContents, true);
		$files = [];
		foreach ($manifest as $entry) {
			$file = $root . '/' . $entry['php'];
			if (!is_file($file)) {
				continue;
			}
			$files[] = $file;
		}

		return $files;
	}

	/**
	 * Read lazily by TypeCombinator: enableIfLoaded() runs before the Composer
	 * autoloader, so it cannot touch autoloadable classes itself.
	 */
	public static function isTypeCombinatorCacheEnabled(): bool
	{
		return self::$typeCombinatorCacheEnabled;
	}

	public static function enableIfLoaded(): void
	{
		if (!self::isLoaded()) {
			return;
		}

		if (getenv('PHPSTAN_TURBO') === '0') {
			return;
		}

		if (phpversion('phpstan_turbo') !== self::EXPECTED_EXTENSION_VERSION) {
			return;
		}

		// Generated on composer dump-autoload by build/generate-turbo-stubs.php
		// from the ShadowedByTurboExtension attributes (plus the hardcoded
		// vendored PhpParser\NodeTraverser). Missing when the dump skipped
		// scripts — run without the extension rather than fatal.
		$stubsFile = dirname(__DIR__, 2) . '/vendor/turbo-stubs.php';
		if (!is_file($stubsFile)) {
			return;
		}

		// Class names the extension needs at runtime, generated from the
		// ReferencedByTurboExtension attributes so a renamed class updates the
		// map on the next autoloader dump. Entries mapping to shadowed classes
		// name what the extension instantiates — the stub subclasses loaded
		// below, so that every created object satisfies the original type
		// hints.
		$classMapFile = dirname(__DIR__, 2) . '/vendor/turbo-class-map.php';
		if (!is_file($classMapFile)) {
			return;
		}

		Runtime::configure(require $classMapFile);

		// Shadow the PHP implementations with the generated stubs extending the
		// extension's native classes. The stubs are declared before the Composer
		// autoloader registers, so later references to the original names
		// resolve to them.
		require_once $stubsFile;

		self::$typeCombinatorCacheEnabled = true;
		self::$enabled = true;
	}

}
