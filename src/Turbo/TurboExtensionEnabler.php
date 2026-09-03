<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Phar;
use PHPStanTurbo\Runtime;
use function class_exists;
use function dirname;
use function extension_loaded;
use function file_get_contents;
use function in_array;
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
	public const EXPECTED_EXTENSION_VERSION = 'd7ef536';

	private static bool $typeCombinatorCacheEnabled = false;

	private static bool $enabled = false;

	private static bool $trustingOwnTypes = false;

	public static function isLoaded(): bool
	{
		return extension_loaded('phpstan_turbo');
	}

	/**
	 * The version of the loaded extension when it does not pass the
	 * enableIfLoaded() version gate. Null when the extension is not loaded
	 * or compatible.
	 */
	public static function getIncompatibleLoadedVersion(): ?string
	{
		if (!self::isLoaded()) {
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

		// When running from a phar, arm the pthread_atfork hooks that keep
		// phar:// reads safe in pcntl_fork()ed workers — libphar serves them
		// through one shared archive fd whose seek cursor forked processes
		// would otherwise race on. Fork mode requires the guard, and
		// ForkParallelChecker only allows fork with the extension active.
		if (class_exists('Phar', false)) {
			$pharPath = Phar::running(false);
			if ($pharPath !== '') {
				Runtime::enablePharForkGuard($pharPath);
			}
		}

		self::$typeCombinatorCacheEnabled = true;
		self::$enabled = true;
	}

	/**
	 * Whether the extension drops the engine's argument and return type
	 * checks from PHPStan's own code in this process, see
	 * trustOwnTypesIfSuitable().
	 */
	public static function isTrustingOwnTypes(): bool
	{
		return self::$trustingOwnTypes;
	}

	/**
	 * PHPStan's code is verified by PHPStan itself at the strictest level, so
	 * the engine's run-time checks of its parameter and return types re-check
	 * what analysis already proved — at about 8% of the analysis CPU: a
	 * class-typed parameter costs a class lookup and an instanceof on every
	 * call, a typed return the same on the way out. With the extension active
	 * and PHPStan running from a phar, its optimizer pass (TrustedTypes.cpp)
	 * drops those checks from the code compiled out of the phar. Nothing else
	 * is touched: extensions, bootstrap files and the analysed project keep
	 * their checks, including on what they receive from PHPStan and return to
	 * it — a check sits in the callee.
	 *
	 * What is lost is the TypeError at the boundary when such code passes a
	 * wrong value into PHPStan: it surfaces later, deeper. That is why --debug
	 * keeps the checks — the "run with --debug" advice on internal errors then
	 * yields the original error. PHPUnit never gets here, so the test suites
	 * of PHPStan and of extensions always run fully checked.
	 *
	 * Must run right after enableIfLoaded(), before the Composer autoloader
	 * and preload.php are compiled: the pass rewrites scripts as they are
	 * compiled, so whatever was compiled earlier keeps its checks.
	 *
	 * @param list<string> $argv
	 */
	public static function trustOwnTypesIfSuitable(array $argv): void
	{
		if (!self::$enabled) {
			return;
		}
		if (in_array('--debug', $argv, true)) {
			return;
		}
		if (!class_exists('Phar', false)) {
			return;
		}
		$pharPath = Phar::running(false);
		if ($pharPath === '') {
			return;
		}

		self::$trustingOwnTypes = Runtime::trustTypesUnder('phar://' . $pharPath . '/');
	}

}
