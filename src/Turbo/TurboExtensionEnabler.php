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

/**
 * Activates the phpstan_turbo extension's shadowing classes.
 *
 * The extension registers nothing under PHPStan's class names at module
 * startup. Once the Composer autoloader is registered, activateIfCompatible()
 * checks the loaded extension's version against EXPECTED_EXTENSION_VERSION
 * and, when it matches, asks Runtime::activateShadowing() to declare the
 * native classes under the PHP twins' real names — PHPStan\TrinaryLogic then
 * is the native class, linked like a PHP declaration (parents and interfaces
 * resolved through the autoloader). A mismatched or absent extension never
 * gets the call, and the PHP implementations load as usual.
 *
 * The version is the short SHA of the last commit touching turbo-ext/src/,
 * enforced by the phar.yml turbo-version job; the native classes must
 * behave exactly like the PHP implementations, hence the gate.
 */
#[ReferencedByTurboExtension(key: 'turboExtensionEnabler')]
final class TurboExtensionEnabler
{

	public const EXPECTED_EXTENSION_VERSION = '2d2c00c';

	private static bool $active = false;

	private static bool $trustingOwnTypes = false;

	public static function isLoaded(): bool
	{
		return extension_loaded('phpstan_turbo');
	}

	/**
	 * The version of the loaded extension when it does not pass the version
	 * gate. Null when the extension is not loaded or compatible.
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

	private static function isCompatible(): bool
	{
		return self::isLoaded() && phpversion('phpstan_turbo') === self::EXPECTED_EXTENSION_VERSION;
	}

	/**
	 * Whether activateIfCompatible() declared the native classes — the
	 * shadowed class names resolve to them only in that case.
	 */
	public static function isActive(): bool
	{
		return self::$active;
	}

	/**
	 * Read lazily by TypeCombinator, whose memoization lives in the native
	 * TypeCombinatorCache.
	 */
	public static function isTypeCombinatorCacheEnabled(): bool
	{
		return self::$active;
	}

	/**
	 * Must run after the Composer autoloader is registered (the native classes
	 * implement userland interfaces and may extend userland classes, resolved
	 * through it) and before anything could autoload one of the shadowed
	 * classes — a twin already declared cannot be shadowed.
	 */
	public static function activateIfCompatible(): void
	{
		if (!self::isCompatible()) {
			return;
		}

		// Both files are generated on composer dump-autoload by
		// build/generate-turbo-manifest.php from the attributes; missing when
		// the dump skipped scripts — run without the extension rather than
		// fatal.
		$root = dirname(__DIR__, 2);
		$manifestFile = $root . '/vendor/turbo-shadowed-classes.json';
		$classMapFile = $root . '/vendor/turbo-class-map.php';
		if (!is_file($manifestFile) || !is_file($classMapFile)) {
			return;
		}

		$manifestContents = file_get_contents($manifestFile);
		if ($manifestContents === false) {
			return;
		}

		// The manifest of shadowed pairs, from the ShadowedByTurboExtension
		// attributes: each native class is declared with its twin's source
		// file, so reflection keeps reading the PHP declaration.
		/** @var array<string, array{php: string, cpp: string, vendored?: bool}> $manifest */
		$manifest = json_decode($manifestContents, true);
		$twinFiles = [];
		foreach ($manifest as $className => $entry) {
			$twinFiles[$className] = $root . '/' . $entry['php'];
		}

		// Class names the native code references at run time, from the
		// ReferencedByTurboExtension attributes, so a renamed class updates
		// the map on the next autoloader dump.
		Runtime::configure(require $classMapFile);

		Runtime::activateShadowing($twinFiles);

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

		self::$active = true;
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
	 * Must run before the Composer autoloader and preload.php are compiled:
	 * the pass rewrites scripts as they are compiled, so whatever was
	 * compiled earlier keeps its checks. Independent of activateIfCompatible()
	 * — only the version gate matters here.
	 *
	 * @param list<string> $argv
	 */
	public static function trustOwnTypesIfSuitable(array $argv): void
	{
		if (!self::isCompatible()) {
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
