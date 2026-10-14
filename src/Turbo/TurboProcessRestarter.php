<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use FilesystemIterator;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use function class_exists;
use function explode;
use function extension_loaded;
use function filemtime;
use function fileowner;
use function fileperms;
use function filesize;
use function function_exists;
use function get_cfg_var;
use function getenv;
use function implode;
use function in_array;
use function ini_get;
use function is_dir;
use function is_link;
use function is_string;
use function is_writable;
use function max;
use function mkdir;
use function pcntl_exec;
use function php_ini_loaded_file;
use function phpversion;
use function posix_geteuid;
use function rmdir;
use function scandir;
use function sha1;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function time;
use function touch;
use function trim;
use function unlink;
use const PHP_BINARY;
use const PHP_OS_FAMILY;
use const PHP_VERSION_ID;

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
 * its shadowing classes, and (when running from a phar) the phar-fork-guard
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

	/** A file cache directory that no run has used for this long is deleted when a new one is created */
	private const OPCACHE_FILE_CACHE_UNUSED_SECONDS_LIMIT = 7 * 24 * 60 * 60;

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
	 * Environment variables that CI services set (to a non-empty value other
	 * than "false"): a CI job usually starts with an empty temp dir, so a file
	 * cache there costs its writes on every run and is never read
	 */
	private const CI_ENVIRONMENT_VARIABLES = ['CI', 'GITHUB_ACTIONS', 'GITLAB_CI', 'BUILDKITE', 'TF_BUILD', 'JENKINS_URL', 'TEAMCITY_VERSION'];

	private static ?string $fileCacheDirectory = null;

	private static bool $fileCacheDirectoryResolved = false;

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
			isset($_SERVER['BLACKFIRE_AGENT_SOCKET'])
		) {
			// pcntl_exec() is not supported by blackfire
			// see https://support.blackfire.platform.sh/hc/en-us/articles/4843014509202-Conflicts-with-pcntl-exec-calls
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

		return self::resolveOpcacheArgs($ini, self::getFileCacheDirectory());
	}

	/**
	 * The directory for a persistent OPcache file cache, created if needed —
	 * null when there should be none. See resolveOpcacheArgs() for why the
	 * cache is safe to keep between runs.
	 *
	 * There is one directory per user under the system temp dir, and in it
	 * one per key (see resolveFileCacheKey()). The directories must be owned
	 * by this user and not writable by anyone else: whatever is in them runs
	 * as opcodes, unchecked, inside PHPStan.
	 *
	 * Only runs from the phar get one: a source checkout changes all the time
	 * and has no build to key the directory by. Not in CI (see
	 * resolveContinuousIntegration()): an empty temp dir at the start of every
	 * job would make the cache pure cost, and with a file cache the
	 * extension's trusted-types pass is off. Not on PHP older than the oldest
	 * one the extension is built for (TurboExtensionSelector::MINIMUM_PHP_VERSION_ID):
	 * the file cache was only measured on that range. Not on Windows either: every
	 * spawned worker there gets its own opcache.cache_id (see ProcessHelper),
	 * and OPcache then keeps a separate file cache per worker that no later
	 * run reuses: 2 GB after one benchmark run on a GitHub runner, and cold
	 * runs 27-50% slower.
	 */
	private static function getFileCacheDirectory(): ?string
	{
		if (self::$fileCacheDirectoryResolved) {
			return self::$fileCacheDirectory;
		}

		self::$fileCacheDirectoryResolved = true;
		if (PHP_VERSION_ID < TurboExtensionSelector::MINIMUM_PHP_VERSION_ID) {
			return null;
		}
		if (PHP_OS_FAMILY === 'Windows' || !function_exists('posix_geteuid') || !class_exists('Phar', false)) {
			return null;
		}
		if (self::resolveContinuousIntegration(getenv())) {
			return null;
		}

		$pharPath = Phar::running(false);
		if ($pharPath === '') {
			return null;
		}

		try {
			$signature = (new Phar($pharPath))->getSignature();
		} catch (Throwable) {
			return null;
		}

		$argv = $_SERVER['argv'] ?? [];
		$key = self::resolveFileCacheKey($signature['hash'], self::describeTurboBinary(), in_array('--debug', $argv, true));

		$userId = posix_geteuid();
		$baseDirectory = sys_get_temp_dir() . '/phpstan-opcache-' . $userId;
		$directory = $baseDirectory . '/' . $key;
		$created = !is_dir($directory);
		if ($created) {
			@mkdir($directory, 0700, true);
		}
		if (!self::isPrivateDirectory($baseDirectory, $userId) || !self::isPrivateDirectory($directory, $userId)) {
			return null;
		}

		// the mtime marks the directory as in use, for the pruning below
		@touch($directory);
		if ($created) {
			self::pruneFileCacheDirectories($baseDirectory, $key, time());
		}

		return self::$fileCacheDirectory = $directory;
	}

	/**
	 * Everything that changes the opcodes compiled out of the same phar on the
	 * same PHP build (OPcache itself separates builds): which extension binary
	 * is loaded, and --debug, which keeps the type checks the extension's
	 * optimizer pass drops (TurboExtensionEnabler::trustOwnTypesIfSuitable()).
	 * The extension refuses that pass while a file cache is configured, since
	 * stripped opcodes would outlive the run, so today it is off in every run
	 * with a file cache. Keying by both keeps the states apart if that ever
	 * changes.
	 *
	 * @param string $turboBinary see describeTurboBinary()
	 */
	public static function resolveFileCacheKey(string $pharSignature, string $turboBinary, bool $debug): string
	{
		return substr(sha1(implode("\0", [$pharSignature, $turboBinary, $debug ? 'debug' : ''])), 0, 16);
	}

	/**
	 * @param array<string, string> $environment getenv()
	 */
	public static function resolveContinuousIntegration(array $environment): bool
	{
		foreach (self::CI_ENVIRONMENT_VARIABLES as $name) {
			$value = $environment[$name] ?? '';
			if ($value !== '' && strtolower($value) !== 'false') {
				return true;
			}
		}

		return false;
	}

	/**
	 * The same answer before the restart and in the restarted process: the
	 * binary the restart loads with -d, or the version of one loaded by the
	 * php.ini, or none.
	 */
	private static function describeTurboBinary(): string
	{
		$path = self::getRestartExtensionPath();
		if ($path === null && !extension_loaded('phpstan_turbo')) {
			$path = TurboExtensionSelector::findExtension();
		}
		if ($path !== null) {
			return 'binary:' . $path . ':' . @filesize($path) . ':' . @filemtime($path);
		}
		if (extension_loaded('phpstan_turbo')) {
			return 'ini:' . phpversion('phpstan_turbo');
		}

		return 'none';
	}

	public static function isPrivateDirectory(string $directory, int $userId): bool
	{
		if (is_link($directory) || !is_dir($directory)) {
			return false;
		}

		$permissions = @fileperms($directory);
		if (@fileowner($directory) !== $userId || $permissions === false || ($permissions & 0022) !== 0) {
			return false;
		}

		// OPcache refuses to start at all - exit code 254 - when it cannot write to opcache.file_cache
		return is_writable($directory);
	}

	/**
	 * Deletes the other key directories that no run has used for
	 * OPCACHE_FILE_CACHE_UNUSED_SECONDS_LIMIT — the caches of PHPStan versions
	 * no longer installed. Runs only when a new key directory was created, so
	 * about once per update.
	 */
	public static function pruneFileCacheDirectories(string $baseDirectory, string $currentKey, int $now): void
	{
		$entries = @scandir($baseDirectory);
		if ($entries === false) {
			return;
		}

		foreach ($entries as $entry) {
			if ($entry === '.' || $entry === '..' || $entry === $currentKey) {
				continue;
			}
			$directory = $baseDirectory . '/' . $entry;
			if (is_link($directory) || !is_dir($directory)) {
				continue;
			}
			$mtime = @filemtime($directory);
			if ($mtime === false || $now - $mtime < self::OPCACHE_FILE_CACHE_UNUSED_SECONDS_LIMIT) {
				continue;
			}

			try {
				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
				foreach ($files as $file) {
					if ($file->isDir() && !$file->isLink()) {
						@rmdir($file->getPathname());
					} else {
						@unlink($file->getPathname());
					}
				}
			} catch (Throwable) {
				continue;
			}
			@rmdir($directory);
		}
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
	 * Without a file cache, timestamp checks are switched off: for a private
	 * cache that dies with the process they revalidate nothing, and skipping
	 * them drops a stat() per include. They also used to be the difference
	 * between caching PHPStan and not: opcache_compile_file() refuses any file
	 * whose mtime it reads as 0, which is what every member of the
	 * distributed phar carried until the build started stamping them
	 * (phar.yml, and compiler/build/resign.php fails on a member left at 0).
	 * It reads the mtime whenever opcache.validate_timestamps,
	 * opcache.file_update_protection or opcache.max_file_size is on. The
	 * uncached state is what made
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
	 * Running from the phar, the opcodes also go to a persistent file cache
	 * in a directory of PHPStan's own (see getFileCacheDirectory()), so the
	 * next run loads PHPStan instead of compiling it again: a warm run on a
	 * small project takes about half the time. The extension's trusted-types
	 * pass stays off while a file cache is configured (see
	 * resolveFileCacheKey()); on a large project that cost and the saved
	 * compilation about cancel out. A file cache is validated by
	 * the PHP build id and, only with opcache.validate_timestamps, the mtime,
	 * so the checks are on in that case, at PHP's defaults: without them it
	 * would serve the previous PHPStan's opcodes after an update (the phar
	 * path being the same), and a project's bootstrap file as it was before
	 * an edit. opcache.file_update_protection keeps a file changed in the
	 * last seconds out of the cache, because the mtime has a resolution of
	 * one second. The directory is keyed by what else changes the compiled
	 * code (resolveFileCacheKey()).
	 *
	 * Otherwise the cache must neither outlive the process nor reach outside
	 * it, which is what the remaining entries guard against in a php.ini tuned
	 * for the web server rather than for us:
	 * - opcache.file_cache is set to that directory, or blanked. The web
	 *   server's own file cache directory would fill with this run's scripts,
	 *   and it is validated with whatever that php.ini says.
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
	 * @param string|null $fileCacheDirectory see getFileCacheDirectory()
	 * @return list<string>
	 */
	public static function resolveOpcacheArgs(array $ini, ?string $fileCacheDirectory = null): array
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
			'opcache.validate_timestamps=' . ($fileCacheDirectory !== null ? '1' : '0'),
			'opcache.file_update_protection=' . ($fileCacheDirectory !== null ? '2' : '0'),
			'opcache.max_file_size=0',
			'opcache.file_cache=' . ($fileCacheDirectory ?? ''),
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
