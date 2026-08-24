<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use function extension_loaded;
use function function_exists;
use function get_cfg_var;
use function getenv;
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
		if (getenv('PHPSTAN_TURBO') === '0') {
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

}
