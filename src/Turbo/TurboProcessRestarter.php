<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use function extension_loaded;
use function function_exists;
use function get_cfg_var;
use function ini_get;
use function is_string;
use function pcntl_exec;
use function php_ini_loaded_file;
use const PHP_BINARY;

/**
 * Restarts the main PHPStan process via pcntl_exec() with the distributed
 * turbo extension loaded through -d extension=.
 *
 * Parallel analysis prefers pcntl_fork()ed workers over spawned ones (see
 * ForkParallelChecker) — a forked worker inherits the booted process instead
 * of paying a full re-boot. But a forked worker also inherits the parent's
 * loaded extensions: the spawn-time `-d extension=` injection
 * (ProcessHelper) cannot reach it, and dl() cannot load a binary from next
 * to the phar. So when the extension binary is available but not loaded,
 * the whole process is re-executed with it before anything else runs —
 * forked workers then inherit the extension, its stub shadowing, and (when
 * running from a phar) the phar-fork-guard that keeps phar:// reads safe
 * across fork.
 *
 * The restarted process also gets OPcache activated — with JIT pinned off,
 * whatever the user's ini says — so forked workers share the parent's warm
 * opcode cache (see getOpcacheArgs()).
 *
 * The restart carries a marker ini entry with the extension path. It doubles
 * as the retry stop (a binary that failed to load leaves the extension
 * unloaded — without the marker the restart would loop) and tells
 * TurboExtensionSelector that spawned workers still need the -d flag
 * (command-line -d flags, unlike php.ini, are not inherited).
 */
final class TurboProcessRestarter
{

	public const EXTENSION_PATH_INI = 'phpstan.turboExtensionPath';

	/**
	 * The extension path this process was restarted with, null when the
	 * process was not restarted.
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
		if (extension_loaded('phpstan_turbo')) {
			return;
		}
		if (self::getRestartExtensionPath() !== null) {
			// already restarted and the extension still did not load
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

		$extensionPath = TurboExtensionSelector::findExtension();
		if ($extensionPath === null) {
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
		foreach (self::getOpcacheArgs() as $opcacheArg) {
			$args[] = '-d';
			$args[] = $opcacheArg;
		}
		$args[] = '-d';
		$args[] = 'extension=' . $extensionPath;
		$args[] = '-d';
		$args[] = self::EXTENSION_PATH_INI . '=' . $extensionPath;
		foreach ($argv as $arg) {
			$args[] = $arg;
		}

		pcntl_exec(PHP_BINARY, $args);
		// pcntl_exec() returns only on failure — continue without the extension
	}

	/**
	 * OPcache directives for the restarted process, as `name=value` ini entries.
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
	 * The only case adding nothing is OPcache not being loaded at all — real
	 * on PHP <= 8.4, gone on 8.5+ (always built in and loaded). Loading it
	 * from here is not worth it: -d zend_extension=opcache emits a startup
	 * warning on builds without the shared object, and on 8.5+ always.
	 *
	 * @return list<string>
	 */
	private static function getOpcacheArgs(): array
	{
		if (!extension_loaded('Zend OPcache')) {
			return [];
		}

		return [
			'opcache.enable=1',
			'opcache.enable_cli=1',
			'opcache.jit=disable',
			'opcache.jit_buffer_size=0',
		];
	}

}
