<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Phar;
use function basename;
use function dirname;
use function explode;
use function file_get_contents;
use function function_exists;
use function get_cfg_var;
use function in_array;
use function is_dir;
use function is_file;
use function is_string;
use function php_uname;
use function preg_match;
use function readlink;
use function sprintf;
use function strpos;
use function version_compare;
use const PHP_DEBUG;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const PHP_OS_FAMILY;
use const PHP_VERSION_ID;
use const PHP_ZTS;

/**
 * Locates the distributed turbo extension binary matching the current runtime
 * so it can be loaded via `-d extension=` — into spawned worker processes
 * (ProcessHelper), or into the restarted main process (the turbo row of
 * PHPStan\Process\Restart\ExtensionRegistry) whose pcntl_fork()ed workers then
 * inherit it.
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
	public const MINIMUM_PHP_VERSION_ID = 80200;

	/** @api */
	public const EXTENSION_PATH_INI = 'phpstan.turboExtensionPath';

	/**
	 * The extension path this process was given through -d — by the restart,
	 * or by ProcessHelper when spawned as a worker. Null when the extension
	 * came from the php.ini or is not loaded at all.
	 */
	public static function getRestartExtensionPath(): ?string
	{
		$path = get_cfg_var(self::EXTENSION_PATH_INI);
		if (!is_string($path) || $path === '') {
			return null;
		}

		return $path;
	}

	/**
	 * The extension path a spawned worker needs as `-d extension=`, along with
	 * `-d` EXTENSION_PATH_INI with the same path. Null when the worker gets the
	 * extension from the php.ini it inherits, or when there is no extension.
	 *
	 * @api
	 */
	public static function findExtensionForWorkers(?string $pharPath = null): ?string
	{
		if (TurboExtensionEnabler::isLoaded()) {
			$restartPath = self::getRestartExtensionPath();
			if ($restartPath !== null) {
				// loaded through a -d flag — the restart's own, or the one
				// ProcessHelper gave this process when spawning it as a worker:
				// spawned workers do not inherit command-line -d flags, so they
				// need it passed explicitly
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
	 * build, a run from source, or a platform without binaries. Also null
	 * on Linux when shouldTryLinuxBinaries() finds a reason to skip them.
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

		$selfMaps = @file_get_contents('/proc/self/maps');
		$platform = self::resolvePlatformDirectory(PHP_OS_FAMILY, php_uname('m'), self::resolveIsMusl($selfMaps, is_file('/etc/alpine-release')));
		if ($platform === null) {
			return null;
		}
		if (PHP_OS_FAMILY === 'Linux') {
			$executable = function_exists('readlink')
				? @readlink('/proc/self/exe')
				: false;

			if (!self::shouldTryLinuxBinaries($selfMaps, $executable)) {
				return null;
			}
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

	/**
	 * Whether to try the bundled Linux binaries, judged from the file-backed
	 * mappings of the running process. True means no reason to skip them was
	 * found, not that they will load. Unreadable or empty maps keep the
	 * current behaviour. If the executable cannot be identified, the
	 * static-layout check is skipped; a recognizable old-glibc mapping can
	 * still be rejected.
	 *
	 * The binaries are skipped when
	 * - a mapped libc has a versioned name older than 2.34 (libc-2.31.so),
	 *   which glibc used for its own files before 2.34, the version the
	 *   binaries are built against
	 * - the PHP binary is mapped and no other executable file is, matching
	 *   the layouts of the fully static musl builds of Laravel Herd Lite and
	 *   static-php-cli (UPX-packed) tested here, which fail to load any
	 *   extension with "Dynamic loading not supported". Those captures
	 *   include an executable /memfd:upx (deleted) or /dev/zero (deleted)
	 *   mapping (the latter is the opcache JIT buffer), which do not count
	 *   as another executable file. A fully static glibc build looks the
	 *   same and is skipped too, but none was tested.
	 *
	 * @param string|false $executable the target of /proc/self/exe
	 */
	public static function shouldTryLinuxBinaries(string|false $selfMaps, string|false $executable): bool
	{
		if ($selfMaps === false) {
			return true;
		}

		$executableMapped = false;
		$otherExecutableMapped = false;
		foreach (explode("\n", $selfMaps) as $line) {
			// address perms offset dev inode pathname, the pathname may contain
			// spaces and ends with " (deleted)" when the file was replaced, the
			// same as the /proc/self/exe target then
			if (preg_match('~^\S+\s+(\S+)\s+\S+\s+\S+\s+\S+\s+(/.*)~', $line, $matches) !== 1) {
				continue;
			}

			$path = $matches[2];
			if (
				preg_match('~\Alibc-(\d+\.\d+)(?:\.\d+)*\.so(?: \(deleted\))?\z~', basename($path), $versionMatches) === 1
				&& version_compare($versionMatches[1], '2.34', '<')
			) {
				return false;
			}

			if ($path === $executable) {
				$executableMapped = true;
				continue;
			}

			if (strpos($matches[1], 'x') === false) {
				continue;
			}

			// executable-memory mappings observed in the tested UPX and
			// OPcache builds, not every unlinked backing file
			if (in_array($path, ['/memfd:upx (deleted)', '/dev/zero (deleted)'], true)) {
				continue;
			}

			$otherExecutableMapped = true;
		}

		return !$executableMapped || $otherExecutableMapped;
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
