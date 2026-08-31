<?php declare(strict_types = 1);

namespace PHPStan\Process;

use function constant;
use function defined;
use function sprintf;

/**
 * Names the signal a child process was killed by, for an error message.
 *
 * Signal numbers are platform-specific - SIGBUS is 7 on Linux and 10 on macOS
 * - so the names come from the running platform's own SIG* constants. Those
 * are defined by ext-pcntl, hence the bare number when it is not loaded.
 */
final class TerminationSignal
{

	/**
	 * Ordered so that the canonical name wins among the aliases sharing a
	 * number (SIGABRT over SIGIOT, SIGCHLD over SIGCLD, SIGIO over SIGPOLL).
	 */
	private const NAMES = [
		'SIGHUP',
		'SIGINT',
		'SIGQUIT',
		'SIGILL',
		'SIGTRAP',
		'SIGABRT',
		'SIGBUS',
		'SIGFPE',
		'SIGKILL',
		'SIGUSR1',
		'SIGSEGV',
		'SIGUSR2',
		'SIGPIPE',
		'SIGALRM',
		'SIGTERM',
		'SIGSTKFLT',
		'SIGCHLD',
		'SIGCONT',
		'SIGSTOP',
		'SIGTSTP',
		'SIGTTIN',
		'SIGTTOU',
		'SIGURG',
		'SIGXCPU',
		'SIGXFSZ',
		'SIGVTALRM',
		'SIGPROF',
		'SIGWINCH',
		'SIGIO',
		'SIGPWR',
		'SIGSYS',
	];

	public static function describe(int $signal): string
	{
		foreach (self::NAMES as $name) {
			if (!defined($name) || constant($name) !== $signal) {
				continue;
			}

			return sprintf('%d (%s)', $signal, $name);
		}

		return (string) $signal;
	}

}
