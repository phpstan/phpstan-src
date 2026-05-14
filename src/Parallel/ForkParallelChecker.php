<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\DependencyInjection\AutowiredService;
use function function_exists;
use function getenv;
use function opcache_get_status;

/**
 * Decides whether parallel analysis should fork workers via pcntl_fork()
 * (see ForkedProcess) instead of spawning fresh PHP processes (see SpawnedProcess).
 *
 * Experimental and opt-in: enabled only when PHPSTAN_PARALLEL_FORK=1 is set,
 * the pcntl/posix functions exist, and OPcache + JIT are both off — their
 * shared memory is not safe to populate concurrently from forked children and
 * doing so corrupts analysis results.
 */
#[AutowiredService]
final class ForkParallelChecker
{

	public function isSupported(): bool
	{
		if (
			!function_exists('pcntl_fork')
			|| !function_exists('pcntl_waitpid')
			|| !function_exists('pcntl_wifexited')
			|| !function_exists('pcntl_wexitstatus')
			|| !function_exists('posix_kill')
		) {
			return false;
		}

		if (getenv('PHPSTAN_PARALLEL_FORK') !== '1') {
			return false;
		}

		// OPcache's shared memory and the JIT buffer are not safe to populate
		// concurrently from multiple forked children — doing so corrupts
		// analysis results. Forked workers require OPcache and JIT to be off.
		if ($this->isOpcacheOrJitEnabled()) {
			return false;
		}

		return true;
	}

	private function isOpcacheOrJitEnabled(): bool
	{
		if (!function_exists('opcache_get_status')) {
			return false;
		}

		$status = opcache_get_status(false);
		if ($status === false) {
			return false;
		}

		if (($status['opcache_enabled'] ?? false) === true) {
			return true;
		}

		return ($status['jit']['enabled'] ?? false) === true;
	}

}
