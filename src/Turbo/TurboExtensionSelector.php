<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Phar;
use function dirname;
use function file_get_contents;
use function is_dir;
use function is_file;
use function php_uname;
use function sprintf;
use function strpos;
use const PHP_DEBUG;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_OS_FAMILY;
use const PHP_VERSION_ID;
use const PHP_ZTS;

/**
 * Locates the distributed turbo extension binary matching the current runtime
 * so it can be loaded via `-d extension=` — into spawned worker processes
 * (ProcessHelper), or into the restarted main process (TurboProcessRestarter)
 * whose pcntl_fork()ed workers then inherit it.
 *
 * The binaries are committed to the phpstan/phpstan repository next to
 * phpstan.phar (turbo-ext/<platform>/phpstan_turbo-<minor>.so, .dll on
 * Windows) by the phar.yml commit job, so they only exist for phar-based
 * installations. Each is a thin per-PHP-version library loading the
 * platform's shared core from its own directory (phpstan_turbo_core.so;
 * phpstan_turbo_core.dll and phpstan_turbo_core-zts.dll on Windows, whose
 * thread-safe PHP is a different DLL to link against), so both must be
 * there —
 * a source checkout loads its locally built extension through php.ini
 * instead. Only non-debug builds for PHP >= MINIMUM_PHP_VERSION_ID are
 * shipped; a ZTS variant (-zts filename
 * suffix) exists for Windows, where XAMPP, WampServer and Scoop default
 * to thread-safe PHP, and for Linux, where the official Docker images are
 * thread-safe from 8.6 on (and the php:*-zts ones before) — a thread-safe
 * PHP on macOS finds no binary and runs without the extension. Workers run the regular
 * entrypoint, so TurboExtensionEnabler still gates activation on the
 * expected extension version.
 *
 * bin/phpstan loads this class before the Composer autoloader (for the
 * process restart), so it must only call functions native to PHP 7.4 - the
 * symfony polyfills are not registered yet. PreAutoloadFilesTest guards this.
 */
final class TurboExtensionSelector
{

	/**
	 * The oldest PHP the extension is built for — the phar.yml turbo-compile
	 * matrix. Older runtimes have no binary to find, so they skip the lookup
	 * (and with it the process restart) entirely.
	 */
	public const MINIMUM_PHP_VERSION_ID = 80300;

	public static function findExtensionForWorkers(?string $pharPath = null): ?string
	{
		if (TurboExtensionEnabler::isLoaded()) {
			$restartPath = TurboProcessRestarter::getRestartExtensionPath();
			if ($restartPath !== null) {
				// loaded through a -d flag — the restart's own, or the one
				// ProcessHelper gave this process when spawning it as a worker
				// (see TurboProcessRestarter): spawned workers do not inherit
				// command-line -d flags, so they need it passed explicitly
				return $restartPath;
			}

			// loaded through php.ini — workers inherit the ini file
			return null;
		}

		return self::findExtension($pharPath);
	}

	/**
	 * Whether PHPStan runs from a bare copy of phpstan.phar, without the
	 * turbo-ext/ directory the phpstan/phpstan Composer package ships next to
	 * it, on a runtime the extension is built for, and no php.ini loads the
	 * extension either.
	 */
	public static function isMissingNextToPhar(): bool
	{
		$platformDirectory = self::getPlatformDirectoryNextToPhar();
		if ($platformDirectory === null) {
			return false;
		}
		if (TurboExtensionEnabler::isLoaded()) {
			return false;
		}

		return !is_dir(dirname($platformDirectory));
	}

	/**
	 * Locates the distributed extension binary for the current platform —
	 * present only next to a phar-based installation.
	 *
	 * Another program that loads PHPStan's phar, Rector for example, passes
	 * the path to that phar: Phar::running() only knows the phar that runs.
	 */
	public static function findExtension(?string $pharPath = null): ?string
	{
		$platformDirectory = self::getPlatformDirectoryNextToPhar($pharPath);
		if ($platformDirectory === null) {
			return null;
		}

		$file = sprintf(
			'%s/phpstan_turbo-%d.%d%s.%s',
			$platformDirectory,
			PHP_MAJOR_VERSION,
			PHP_MINOR_VERSION,
			(bool) PHP_ZTS ? '-zts' : '',
			PHP_OS_FAMILY === 'Windows' ? 'dll' : 'so',
		);
		if (!is_file($file)) {
			return null;
		}

		// without its core next to it, PHP would warn at startup that it
		// cannot load the extension
		if (!is_file(dirname($file) . '/' . self::resolveCoreFileName(PHP_OS_FAMILY, (bool) PHP_ZTS))) {
			return null;
		}

		return $file;
	}

	/**
	 * The directory of turbo-ext/ next to the running phar that holds the
	 * binaries for this platform, whether it exists or not. Null when no
	 * binary is built for this runtime: PHP older than the minimum, a debug
	 * build, a run from source, or a platform without binaries.
	 */
	private static function getPlatformDirectoryNextToPhar(?string $pharPath = null): ?string
	{
		if (PHP_VERSION_ID < self::MINIMUM_PHP_VERSION_ID) {
			return null;
		}
		if ((bool) PHP_DEBUG) {
			return null;
		}

		$pharPath ??= Phar::running(false);
		if ($pharPath === '') {
			return null;
		}

		$platform = self::resolvePlatformDirectory(PHP_OS_FAMILY, php_uname('m'), self::isMusl());
		if ($platform === null) {
			return null;
		}

		return sprintf('%s/turbo-ext/%s', dirname($pharPath), $platform);
	}

	/**
	 * The shared core the extension binary loads, in the same directory.
	 */
	public static function resolveCoreFileName(string $osFamily, bool $zts): string
	{
		if ($osFamily === 'Windows') {
			return $zts ? 'phpstan_turbo_core-zts.dll' : 'phpstan_turbo_core.dll';
		}

		// one core serves the thread-safe and the non-thread-safe extension:
		// it reaches the engine's globals through the pointers the extension
		// hands it, never through TSRM itself
		return 'phpstan_turbo_core.so';
	}

	public static function resolvePlatformDirectory(string $osFamily, string $machine, bool $isMusl): ?string
	{
		if ($osFamily === 'Darwin') {
			// arm64 (Apple Silicon) only - there is no Intel build. An x86_64
			// PHP under Rosetta reports x86_64 here as well, and cannot load
			// the arm64 binary either.
			return $machine === 'arm64' ? 'macos-arm64' : null;
		}
		if ($osFamily === 'Windows') {
			return $machine === 'AMD64' || $machine === 'x86_64' ? 'windows-x86_64' : null;
		}
		if ($osFamily !== 'Linux') {
			return null;
		}

		$architecture = $machine === 'aarch64' ? 'arm64' : $machine;
		if ($architecture !== 'x86_64' && $architecture !== 'arm64') {
			return null;
		}

		return sprintf('linux-%s-%s', $isMusl ? 'musl' : 'gnu', $architecture);
	}

	/**
	 * libc has no PHP constant; this checks what loader is actually
	 * mapped into the running process rather than whether a musl loader
	 * merely exists somewhere on disk — a glibc host with musl-tools
	 * installed has one too, and would otherwise be misdetected as musl.
	 */
	public static function isMusl(): bool
	{
		return self::resolveIsMusl(@file_get_contents('/proc/self/maps'), is_file('/etc/alpine-release'));
	}

	public static function resolveIsMusl(string|false $selfMaps, bool $hasAlpineRelease): bool
	{
		if ($selfMaps !== false) {
			// strpos() rather than str_contains(): this runs before the Composer
			// autoloader, so the symfony polyfill is not loaded yet and
			// str_contains() does not exist on PHP < 8.0
			return strpos($selfMaps, '/ld-musl-') !== false;
		}

		// no procfs to inspect (non-Linux, or a sandboxed container without
		// /proc) — Alpine's own musl PHP packages are still worth detecting
		return $hasAlpineRelease;
	}

}
