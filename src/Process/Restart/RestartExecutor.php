<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use function array_merge;
use function extension_loaded;
use function file_exists;
use function function_exists;
use function ini_get;
use function is_resource;
use function pcntl_async_signals;
use function pcntl_exec;
use function pcntl_signal;
use function proc_close;
use function proc_open;
use function sapi_windows_set_ctrl_handler;
use function unlink;
use function uopz_allow_exit;
use const PHP_BINARY;
use const SIG_IGN;
use const SIGINT;

/**
 * Runs the restarted command.
 *
 * pcntl_exec() is preferred: the process image is replaced, so file
 * descriptors, signal handling, the exit code and the pid stay exactly as the
 * caller sees them, and nothing has to be forwarded. Where it is unavailable
 * (i.e. Windows, or a build without pcntl) the command runs as a child process
 * whose exit code is passed on, mirroring what composer/xdebug-handler does.
 */
final class RestartExecutor
{

	/** The exit code reported when the child process could not be waited for. */
	private const UNKNOWN_EXIT_CODE = 255;

	public static function isSupported(): bool
	{
		if (!file_exists(PHP_BINARY)) {
			return false;
		}

		if (function_exists('pcntl_exec')) {
			return true;
		}

		if (!function_exists('proc_open')) {
			return false;
		}

		// the passthrough loop has to exit() with the child's exit code, which
		// uopz disables at the opcode level unless asked not to
		return !self::isExitDisabledByUopz() || function_exists('uopz_allow_exit');
	}

	/**
	 * On success this never returns — either the process image is replaced, or
	 * the restarted command runs to completion and its exit code is passed on.
	 * It returns only when the restart could not be performed at all.
	 *
	 * @param list<string> $arguments php arguments, the script and its own arguments included
	 * @param string|null $temporaryIni composed ini to delete once the restarted command is done
	 */
	public static function execute(array $arguments, ?string $temporaryIni): void
	{
		if (function_exists('pcntl_exec')) {
			@pcntl_exec(PHP_BINARY, $arguments);
			// returns only on failure — fall through to the passthrough loop
		}

		if (!function_exists('proc_open')) {
			return;
		}

		if (self::isExitDisabledByUopz() && function_exists('uopz_allow_exit')) {
			@uopz_allow_exit(true);
		}

		// the child inherits the terminal, so let it be the one that reacts to
		// Ctrl+C; this process only waits for it
		self::ignoreInterrupts();

		$process = @proc_open(array_merge([PHP_BINARY], $arguments), [], $pipes);
		if (!is_resource($process)) {
			return;
		}

		$exitCode = proc_close($process);

		if ($temporaryIni !== null) {
			@unlink($temporaryIni);
		}

		exit($exitCode === -1 ? self::UNKNOWN_EXIT_CODE : $exitCode);
	}

	private static function isExitDisabledByUopz(): bool
	{
		return extension_loaded('uopz') && ini_get('uopz.disable') !== '1';
	}

	private static function ignoreInterrupts(): void
	{
		if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
			pcntl_async_signals(true);
			pcntl_signal(SIGINT, SIG_IGN);
		}

		if (!function_exists('sapi_windows_set_ctrl_handler')) {
			return;
		}

		// CTRL events are only reliably available in the child when the parent
		// ignores them
		sapi_windows_set_ctrl_handler(static function (int $event): void {
		});
	}

}
