<?php declare(strict_types = 1);

// Kills the parallel worker the way a native crash does: a signal, with no PHP
// error, no shutdown function, and nothing written to the output the main
// process reads back. That is how the shared-memory arena's SIGBUS killed
// workers in https://github.com/phpstan/phpstan/issues/15131, and the main
// process must say so instead of treating the dead worker like one that quit.

use PHPStan\Process\ForkedChildCrashReporter;

// A forked worker has the main process's argv; a spawned one runs the worker
// command. Killing the main process instead would prove nothing.
$isWorker = ForkedChildCrashReporter::isActive()
	|| in_array('worker', $_SERVER['argv'], true);

if (!$isWorker) {
	return;
}

posix_kill(posix_getpid(), SIGKILL);
