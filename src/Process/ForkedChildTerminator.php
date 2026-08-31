<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Turbo\TurboExtensionEnabler;
use PHPStanTurbo\Runtime;
use function register_shutdown_function;

/**
 * Ends a pcntl_fork()ed worker with _exit() instead of PHP's full teardown.
 *
 * A forked child inherits the whole parent process, every loaded extension
 * with whatever background threads it started included - but fork() copies
 * only the calling thread. exit() then runs destructors and each extension's
 * module shutdown, and an extension whose shutdown waits for its threads to
 * check out (ext-grpc's grpc_shutdown() without grpc.enable_fork_support, for
 * one) waits forever for threads the child never had: the worker has
 * delivered its results, the parent keeps polling waitpid(), and the run
 * hangs at 100%. A forked child that does not exec() must end with _exit(),
 * leaving the process-wide teardown to the parent.
 *
 * install() registers that _exit() as the child's last shutdown function, so
 * it covers every way out - exit(), a fatal error, an uncaught exception -
 * and runs after the crash report (ForkedChildCrashReporter) is written.
 * Everything the parent reads from the child, the results over the socket
 * and the captured output, has gone through unbuffered fds by then.
 *
 * PHP itself has no _exit(); the turbo extension provides it, and fork mode
 * from a phar requires the extension anyway (see ForkParallelChecker). A
 * source checkout forking without it keeps the plain exit().
 */
final class ForkedChildTerminator
{

	public static function install(): void
	{
		if (!TurboExtensionEnabler::isActive()) {
			return;
		}

		register_shutdown_function(static function (): void {
			Runtime::exitImmediately();
		});
	}

}
