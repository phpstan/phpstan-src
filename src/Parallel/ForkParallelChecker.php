<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use function function_exists;
use function getenv;
use function opcache_get_status;
use function sprintf;

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
final class ForkParallelChecker implements DiagnoseExtension
{

	public function isSupported(): bool
	{
		return $this->getDisabledReason() === null;
	}

	public function print(Output $output): void
	{
		$output->writeLineFormatted('<info>Parallel worker creation:</info>');

		$reason = $this->getDisabledReason();
		if ($reason === null) {
			$output->writeLineFormatted('Mechanism:                 fork (pcntl_fork — experimental)');
			$output->writeLineFormatted('');
			return;
		}

		$output->writeLineFormatted('Mechanism:                 spawn (react/child-process)');
		if (getenv('PHPSTAN_PARALLEL_FORK') === '1') {
			$output->writeLineFormatted(sprintf('Reason fork not used:      %s', $reason));
		}
		$output->writeLineFormatted('');
	}

	private function getDisabledReason(): ?string
	{
		if (
			!function_exists('pcntl_fork')
			|| !function_exists('pcntl_waitpid')
			|| !function_exists('pcntl_wifexited')
			|| !function_exists('pcntl_wexitstatus')
			|| !function_exists('posix_kill')
		) {
			return 'pcntl/posix functions are not available';
		}

		if (getenv('PHPSTAN_PARALLEL_FORK') !== '1') {
			return 'PHPSTAN_PARALLEL_FORK environment variable is not set to "1"';
		}

		if ($this->isOpcacheOrJitEnabled()) {
			return 'OPcache or JIT is enabled (forked workers require both to be off — their shared memory corrupts under concurrent population)';
		}

		return null;
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
