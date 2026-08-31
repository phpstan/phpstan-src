<?php declare(strict_types=1);

/**
 * Test for Runtime::exitImmediately().
 *
 * A pcntl_fork()ed child inherits the parent's whole process, and PHP's
 * exit() runs the full teardown there: destructors, then every extension's
 * module shutdown. An inherited extension that waits for its threads to
 * check out at module shutdown (ext-grpc without grpc.enable_fork_support)
 * waits forever in the child - fork() copies only the calling thread. The
 * child must end with _exit() right after its shutdown functions instead.
 *
 * The teardown that must not run is modelled in userland by a destructor
 * that never returns: it stands in for the wedged module shutdown, being the
 * first thing PHP runs after the shutdown functions.
 *
 * Two passes over the same child:
 *   1. control, plain exit() - the child must still be alive at the deadline
 *      (proves the workload hangs)
 *   2. exitImmediately() from a shutdown function - the child must be gone
 *      well within the deadline, with exit()'s status, the earlier shutdown
 *      function's marker written and the destructor's not
 *
 * Run: php -d extension=$PWD/phpstan_turbo.so tests/exit-immediately.php
 */

const DEADLINE_SECONDS = 3;
const EXIT_STATUS = 7;

if (!extension_loaded('phpstan_turbo')) {
	fwrite(STDERR, "phpstan_turbo extension is not loaded\n");
	exit(1);
}
if (!function_exists('pcntl_fork')) {
	echo "SKIP: pcntl is not available\n";
	exit(0);
}

final class WedgedTeardown
{

	public static ?self $instance = null;

	/** @param resource $markers */
	public function __construct(private $markers)
	{
	}

	public function __destruct()
	{
		fwrite($this->markers, "destructor\n");
		while (true) {
			sleep(1);
		}
	}

}

/**
 * @return array{exited: bool, status: int|null, markers: string}
 */
function forkAndExit(bool $terminate): array
{
	$markers = tmpfile();
	if ($markers === false) {
		fwrite(STDERR, "tmpfile() failed\n");
		exit(1);
	}
	stream_set_write_buffer($markers, 0);

	$pid = pcntl_fork();
	if ($pid === -1) {
		fwrite(STDERR, "fork failed\n");
		exit(1);
	}
	if ($pid === 0) {
		WedgedTeardown::$instance = new WedgedTeardown($markers);
		register_shutdown_function(static function () use ($markers): void {
			fwrite($markers, "shutdown\n");
		});
		if ($terminate) {
			register_shutdown_function(static function (): void {
				PHPStanTurbo\Runtime::exitImmediately();
			});
		}
		exit(EXIT_STATUS);
	}

	$exited = false;
	$status = null;
	$deadline = microtime(true) + DEADLINE_SECONDS;
	while (microtime(true) < $deadline) {
		$result = pcntl_waitpid($pid, $waitStatus, WNOHANG);
		if ($result === $pid) {
			$exited = true;
			$status = pcntl_wifexited($waitStatus) ? pcntl_wexitstatus($waitStatus) : null;
			break;
		}
		usleep(10000);
	}
	if (!$exited) {
		posix_kill($pid, SIGKILL);
		pcntl_waitpid($pid, $waitStatus);
	}

	rewind($markers);
	$written = (string) stream_get_contents($markers);
	fclose($markers);

	return ['exited' => $exited, 'status' => $status, 'markers' => $written];
}

$control = forkAndExit(false);
printf("control (exit):      exited %s, markers %s\n", $control['exited'] ? 'yes' : 'no', json_encode($control['markers']));

$terminated = forkAndExit(true);
printf("exitImmediately():   exited %s, status %s, markers %s\n", $terminated['exited'] ? 'yes' : 'no', var_export($terminated['status'], true), json_encode($terminated['markers']));

if ($control['exited'] || $control['markers'] !== "shutdown\ndestructor\n") {
	fwrite(STDERR, "FAIL: the control child did not wedge in its destructor - the workload no longer models the hang\n");
	exit(1);
}
if (!$terminated['exited']) {
	fwrite(STDERR, "FAIL: the child did not exit\n");
	exit(1);
}
if ($terminated['status'] !== EXIT_STATUS) {
	fwrite(STDERR, "FAIL: the child exited with the wrong status\n");
	exit(1);
}
if ($terminated['markers'] !== "shutdown\n") {
	fwrite(STDERR, "FAIL: unexpected teardown ran in the child\n");
	exit(1);
}

echo "ALL OK\n";
