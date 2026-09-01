<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use function explode;
use function extension_loaded;
use function function_exists;
use function get_cfg_var;
use function in_array;
use function ini_get;
use function is_string;
use function max;
use function pcntl_exec;
use function php_ini_loaded_file;
use function strtolower;
use function trim;
use const PHP_BINARY;

/**
 * Restarts the main PHPStan process via pcntl_exec() when the process it
 * was started as is not the one PHPStan wants to run — for either of two
 * reasons, checked independently.
 *
 * The distributed turbo extension is available but not loaded. Parallel
 * analysis prefers pcntl_fork()ed workers over spawned ones (see
 * ForkParallelChecker) — a forked worker inherits the booted process instead
 * of paying a full re-boot. But a forked worker also inherits the parent's
 * loaded extensions: the spawn-time `-d extension=` injection
 * (ProcessHelper) cannot reach it, and dl() cannot load a binary from next
 * to the phar. So the whole process is re-executed with the extension
 * before anything else runs — forked workers then inherit the extension,
 * its stub shadowing, and (when running from a phar) the phar-fork-guard
 * that keeps phar:// reads safe across fork.
 *
 * The OPcache configuration in effect is not the one PHPStan wants (see
 * resolveOpcacheArgs()) — usually because OPcache is dormant on CLI, or has
 * JIT on. The restarted process runs with it activated, JIT pinned off, and
 * forked workers share the parent's warm opcode cache. This reason stands
 * on its own so that a php.ini which already loads turbo (a source checkout
 * with a locally built extension, say) does not leave OPcache dormant just
 * because there is no extension left to restart for.
 *
 * The restart carries two marker ini entries: RESTARTED_INI is the retry
 * stop (a binary that failed to load, or an OPcache that could not start,
 * would otherwise restart forever), and EXTENSION_PATH_INI tells
 * TurboExtensionSelector that spawned workers still need the -d flag
 * (command-line -d flags, unlike php.ini, are not inherited). ProcessHelper
 * sets both on the workers it spawns, along with the OPcache entries: a
 * worker's configuration is decided by the process spawning it, so it never
 * restarts itself — before that, every spawned worker on a pcntl host
 * re-executed itself once to activate OPcache, rebuilding its command line
 * without the sys_temp_dir and extension entries of the spawn.
 */
final class TurboProcessRestarter
{

	public const EXTENSION_PATH_INI = 'phpstan.turboExtensionPath';

	/** Set by the restart, and by ProcessHelper on spawned workers, so the process never restarts itself */
	public const RESTARTED_INI = 'phpstan.restarted';

	/** Shared memory reserved (not touched) for the opcode cache, in MB — see resolveOpcacheArgs() for the sizing */
	private const OPCACHE_MEMORY_CONSUMPTION_MB_LIMIT = 256;

	/** Carved out of the memory above for interned strings, in MB */
	private const OPCACHE_INTERNED_STRINGS_BUFFER_MB_LIMIT = 64;

	private const OPCACHE_MAX_ACCELERATED_FILES_LIMIT = 20000;

	/** PHP's default opcache.optimization_level, pinned so the optimizer (and the extension's pass in it) always runs */
	private const OPCACHE_OPTIMIZATION_LEVEL = '0x7FFEBFFF';

	/** The php.ini directives resolveOpcacheArgs() reacts to */
	private const OPCACHE_INI_INPUTS = [
		'opcache.file_cache_only',
		'opcache.preload',
		'opcache.memory_consumption',
		'opcache.interned_strings_buffer',
		'opcache.max_accelerated_files',
	];

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
	 * On success the call never returns — the process image is replaced.
	 *
	 * @param list<string> $argv
	 */
	public static function restartIfSuitable(array $argv): void
	{
		if (get_cfg_var(self::RESTARTED_INI) !== false) {
			// already restarted — whatever did not take effect (a binary that
			// failed to load, an OPcache that could not start) will not on a
			// second try either
			return;
		}
		if (
			!function_exists('pcntl_exec')
			|| !function_exists('pcntl_fork')
			|| !function_exists('pcntl_waitpid')
			|| !function_exists('pcntl_wifexited')
			|| !function_exists('pcntl_wexitstatus')
			|| !function_exists('posix_kill')
		) {
			// fork mode is impossible here (see ForkParallelChecker) and
			// spawned workers get the extension from ProcessHelper already
			return;
		}

		$extensionPath = extension_loaded('phpstan_turbo') ? null : TurboExtensionSelector::findExtension();
		$opcacheArgs = self::getOpcacheArgs();
		if (
			$extensionPath === null
			&& !self::resolveOpcacheRestartNeeded($opcacheArgs, self::getCurrentIniValues($opcacheArgs))
		) {
			return;
		}

		$args = [];
		$phpIni = php_ini_loaded_file();
		if ($phpIni !== false) {
			$args[] = '-c';
			$args[] = $phpIni;
		}
		$args[] = '-d';
		$args[] = 'memory_limit=' . ini_get('memory_limit');
		foreach ($opcacheArgs as $opcacheArg) {
			$args[] = '-d';
			$args[] = $opcacheArg;
		}
		if ($extensionPath !== null) {
			$args[] = '-d';
			$args[] = 'extension=' . $extensionPath;
			$args[] = '-d';
			$args[] = self::EXTENSION_PATH_INI . '=' . $extensionPath;
		}
		$args[] = '-d';
		$args[] = self::RESTARTED_INI . '=1';
		foreach ($argv as $arg) {
			$args[] = $arg;
		}

		pcntl_exec(PHP_BINARY, $args);
		// pcntl_exec() returns only on failure — continue as we are
	}

	/**
	 * Whether the OPcache configuration the restart would set differs from
	 * the one in effect — the second reason to restart, independent of turbo:
	 * with the extension already loaded through php.ini there would be
	 * nothing else to restart for, and OPcache would stay dormant.
	 *
	 * @param list<string> $opcacheArgs `name=value` entries, see resolveOpcacheArgs()
	 * @param array<string, string|false> $currentIniValues ini_get() of each of those names
	 */
	public static function resolveOpcacheRestartNeeded(array $opcacheArgs, array $currentIniValues): bool
	{
		foreach ($opcacheArgs as $opcacheArg) {
			[$name, $value] = explode('=', $opcacheArg, 2);
			$current = $currentIniValues[$name] ?? false;
			if ($current === false) {
				// unknown directive on this PHP (opcache.jit before 8.0) — nothing a restart could change
				continue;
			}
			if (self::normalizeIniValue($current) !== self::normalizeIniValue($value)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param list<string> $opcacheArgs
	 * @return array<string, string|false>
	 */
	private static function getCurrentIniValues(array $opcacheArgs): array
	{
		$values = [];
		foreach ($opcacheArgs as $opcacheArg) {
			$name = explode('=', $opcacheArg, 2)[0];
			$values[$name] = ini_get($name);
		}

		return $values;
	}

	/**
	 * The ini parser stores booleans as "1" / "" for php.ini and -d values
	 * alike; ini_set()-style spellings are folded the same way.
	 */
	private static function normalizeIniValue(string $value): string
	{
		$value = strtolower(trim($value));
		if (in_array($value, ['', '0', 'off', 'false', 'no', 'none'], true)) {
			return '0';
		}
		if (in_array($value, ['1', 'on', 'true', 'yes'], true)) {
			return '1';
		}

		return $value;
	}

	/**
	 * `-d` entries activating OPcache for the restarted process, and for the
	 * workers ProcessHelper spawns — see resolveOpcacheArgs() for the
	 * reasoning behind each of them.
	 *
	 * Nothing is added when OPcache is not loaded at all — real on PHP <= 8.4,
	 * gone on 8.5+ (always built in and loaded). Loading it from here is not
	 * worth it: -d zend_extension=opcache emits a startup warning on builds
	 * without the shared object, and on 8.5+ always.
	 *
	 * @return list<string>
	 */
	public static function getOpcacheArgs(): array
	{
		if (!extension_loaded('Zend OPcache')) {
			return [];
		}

		$ini = [];
		foreach (self::OPCACHE_INI_INPUTS as $name) {
			$ini[$name] = ini_get($name);
		}

		return self::resolveOpcacheArgs($ini);
	}

	/**
	 * OPcache directives for the restarted process, as `name=value` ini
	 * entries, given the current values of the OPCACHE_INI_INPUTS directives
	 * (the restarted process loads the same php.ini, so they are what it
	 * would otherwise run with).
	 *
	 * The exec is paid for anyway, so it doubles as the chance to activate
	 * OPcache, usually dormant on CLI (opcache.enable_cli defaults to off):
	 * optimized opcodes and the inheritance cache speed up the whole run, and
	 * the pcntl_fork()ed workers inherit the parent's warm shared memory
	 * instead of each compiling lazily-loaded classes for itself. Concurrent
	 * population of that inherited cache is safe — it is php-fpm's normal
	 * operating model (see ForkParallelChecker).
	 *
	 * JIT is always pinned off, even when the user's ini enables it — it is a
	 * measured slowdown for PHPStan's workload and its shared code buffer is
	 * not fork-safe, so honoring it would cost twice (the same treatment the
	 * xdebug-handler restart gives xdebug). Merely flipping
	 * opcache.enable_cli=1 could also activate it behind the user's back: on
	 * PHP <= 8.3 opcache.jit defaults to `tracing`, so an ini setting just a
	 * non-zero opcache.jit_buffer_size (common web-tuning advice) suddenly
	 * JITs; on PHP >= 8.4 the buffer defaults to 64M, so an ini setting just
	 * opcache.jit does
	 * (https://php.watch/versions/8.4/opcache-jit-ini-default-changes).
	 * Pinning both directives covers both generations of defaults.
	 *
	 * Timestamp checks are switched off, or nothing of PHPStan itself would be
	 * cached: opcache_compile_file() refuses any file whose mtime it reads as
	 * 0, which is what every member of the distributed phar carried until the
	 * build started stamping them (phar.yml). It reads the mtime whenever
	 * opcache.validate_timestamps, opcache.file_update_protection or
	 * opcache.max_file_size is on, so all three go — for a private cache that
	 * dies with the process they revalidate nothing anyway, and skipping them
	 * also drops a stat() per include. The uncached state is what made
	 * OPcache *slower* than no OPcache for phar runs: code compiled under an
	 * active OPcache but not persisted never gets its strings interned into
	 * SHM, so its type names have no class-entry cache slot and every
	 * class-typed parameter, return and property check falls back to a
	 * lowercased-copy class-table lookup (measured at +27% CPU on slevomat).
	 *
	 * The buffers are raised above the stock 128M/8M/10000 for the same
	 * reason: once any of them is exhausted, everything compiled afterwards
	 * lands in that same uncached, uninterned state — the stock 8M of interned
	 * strings run out during PHPStan's own boot already, which alone costs the
	 * whole gain. PHPStan's phar needs about 35M of code; project bootstraps
	 * loaded by extensions (a Doctrine objectManagerLoader booting the whole
	 * app, say) add hundreds of MB, and the shared memory is only reserved,
	 * not touched, until used. It is not sized for the largest possible
	 * project, though — an SHM reservation that cannot be satisfied is fatal
	 * (exit code 254) in the restarted process, with no parent left to fall
	 * back to. Sizes the php.ini already grants are never lowered, and the
	 * interned strings buffer is kept below the memory it is carved out of
	 * (another fatal startup error otherwise).
	 *
	 * That private cache must neither outlive the process nor reach outside
	 * it, which is what the remaining entries guard against in a php.ini tuned
	 * for the web server rather than for us:
	 * - opcache.file_cache is blanked. A file cache is validated by the PHP
	 *   build id and (only with opcache.validate_timestamps) the mtime — so
	 *   with the checks off it would keep serving the previous PHPStan's
	 *   opcodes after an update, the phar path being the same, and it would
	 *   fill the web server's cache directory with this run's scripts.
	 * - opcache.save_comments is pinned on: stripping doc comments (a common
	 *   web tuning) breaks annotation readers in the project code the
	 *   extensions bootstrap, which worked with OPcache dormant.
	 * - opcache.optimization_level is pinned to PHP's default: the
	 *   extension's pass dropping PHPStan's own type checks
	 *   (TurboExtensionEnabler::trustOwnTypesIfSuitable()) runs inside the
	 *   optimizer, which a php.ini can switch off entirely.
	 *
	 * Two configurations are left alone entirely — no OPcache entries at all,
	 * so the restarted process runs with what the php.ini says, as before:
	 * - opcache.file_cache_only: blanking the file cache would be a fatal
	 *   startup error, and it usually means shared memory is unavailable on
	 *   that host on purpose.
	 * - opcache.preload: the application's preload script would run inside
	 *   PHPStan (there is no CLI exemption), and it cannot be blanked with -d
	 *   (the directive rejects an empty value).
	 *
	 * @param array<string, string|false> $ini
	 * @return list<string>
	 */
	public static function resolveOpcacheArgs(array $ini): array
	{
		if (self::isIniOn($ini['opcache.file_cache_only'] ?? false)) {
			return [];
		}
		$preload = $ini['opcache.preload'] ?? false;
		if ($preload !== false && $preload !== '') {
			return [];
		}

		$memory = max(self::OPCACHE_MEMORY_CONSUMPTION_MB_LIMIT, self::iniInt($ini['opcache.memory_consumption'] ?? false));
		$internedStrings = max(self::OPCACHE_INTERNED_STRINGS_BUFFER_MB_LIMIT, self::iniInt($ini['opcache.interned_strings_buffer'] ?? false));
		if ($internedStrings >= $memory) {
			$memory = $internedStrings + self::OPCACHE_MEMORY_CONSUMPTION_MB_LIMIT - self::OPCACHE_INTERNED_STRINGS_BUFFER_MB_LIMIT;
		}
		$files = max(self::OPCACHE_MAX_ACCELERATED_FILES_LIMIT, self::iniInt($ini['opcache.max_accelerated_files'] ?? false));

		return [
			'opcache.enable=1',
			'opcache.enable_cli=1',
			'opcache.jit=disable',
			'opcache.jit_buffer_size=0',
			'opcache.validate_timestamps=0',
			'opcache.file_update_protection=0',
			'opcache.max_file_size=0',
			'opcache.file_cache=',
			'opcache.save_comments=1',
			'opcache.optimization_level=' . self::OPCACHE_OPTIMIZATION_LEVEL,
			'opcache.memory_consumption=' . $memory,
			'opcache.interned_strings_buffer=' . $internedStrings,
			'opcache.max_accelerated_files=' . $files,
		];
	}

	private static function isIniOn(string|false $value): bool
	{
		return $value !== false && $value !== '' && $value !== '0';
	}

	private static function iniInt(string|false $value): int
	{
		return $value === false ? 0 : (int) $value;
	}

}
