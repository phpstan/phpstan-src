<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Command\CommandHelper;
use function error_get_last;
use function fwrite;
use function in_array;
use function ini_get;
use function ini_set;
use function register_shutdown_function;
use function sprintf;
use function str_contains;
use function stream_set_write_buffer;
use const E_COMPILE_ERROR;
use const E_CORE_ERROR;
use const E_ERROR;
use const E_PARSE;

/**
 * Makes a fatal error in a pcntl_fork()ed child reach the parent - the
 * fork-mode counterpart of a spawned worker whose stderr is a pipe the parent
 * reads.
 *
 * A forked child inherits the parent's stdout/stderr, so the engine prints a
 * fatal error straight to the console while the parent-readable capture file
 * stays empty - the parent would report "Child process error" with no message.
 * install() suppresses that console output and registers a shutdown function
 * recreating the canonical "PHP Fatal error" line (and the memory-limit
 * message) in the capture file instead. CommandHelper's inherited shutdown
 * function steps aside in such a child: its formatted write would go to the
 * fork-inherited console fd, and its allocations can die again under OOM,
 * aborting the shutdown-function queue before the one installed here runs.
 */
final class ForkedChildCrashReporter
{

	private static bool $active = false;

	/**
	 * Whether the current process is a forked child with crash reporting
	 * installed.
	 */
	public static function isActive(): bool
	{
		return self::$active;
	}

	/**
	 * @param resource $stdErrCapture
	 */
	public static function install($stdErrCapture): void
	{
		// The engine displays a fatal error on stdout/stderr and, with no
		// error_log configured, logs it to stderr - in a forked child both
		// leak to the console. Suppress them; the shutdown function below
		// reports the crash instead. An error_log pointing to a real file
		// keeps working like it does in a spawned worker.
		ini_set('display_errors', '0');
		$errorLog = ini_get('error_log');
		if (in_array($errorLog, ['', false], true)) {
			ini_set('log_errors', '0');
		}

		// Unbuffered writes need no stream-buffer allocation, so the shutdown
		// function below can still deliver when the crash being reported is
		// the process running out of memory.
		stream_set_write_buffer($stdErrCapture, 0);

		register_shutdown_function(static function () use ($stdErrCapture): void {
			$error = error_get_last();
			if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
				return;
			}

			fwrite($stdErrCapture, sprintf("PHP Fatal error:  %s in %s on line %d\n", $error['message'], $error['file'], $error['line']));

			if (!str_contains($error['message'], 'Allowed memory size')) {
				return;
			}

			fwrite($stdErrCapture, sprintf("%s: %s\n", CommandHelper::MEMORY_LIMIT_CRASH_MESSAGE, ini_get('memory_limit')));
			fwrite($stdErrCapture, "Increase your memory limit in php.ini or run PHPStan with --memory-limit CLI option.\n");
		});

		self::$active = true;
	}

}
