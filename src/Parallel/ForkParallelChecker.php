<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use PHPStan\Turbo\TurboExtensionEnabler;
use function function_exists;
use function opcache_get_status;
use function sprintf;

/**
 * Decides whether parallel analysis should fork workers via pcntl_fork()
 * (see ForkedProcess) instead of spawning fresh PHP processes (see SpawnedProcess).
 *
 * Fork is used whenever possible — a forked worker inherits the booted
 * process and skips the application re-boot a spawned one pays. It requires:
 *
 * - pcntl/posix functions (not available on Windows — always spawn there)
 * - the turbo extension, active with the expected version. A forked worker
 *   inherits the parent's extensions, so the spawn-time `-d extension=`
 *   injection cannot reach it — TurboProcessRestarter re-executes the main
 *   process with the distributed binary loaded when needed. The extension
 *   also arms the phar-fork-guard: libphar serves phar:// reads through one
 *   shared per-archive fd whose seek cursor forked processes race on,
 *   corrupting reads — the guard gives each forked child a private cursor.
 *   Without it, running from a phar would corrupt analysis, so no turbo
 *   means spawn.
 * - OPcache + JIT off — their shared memory is not safe to populate
 *   concurrently from forked children and doing so corrupts analysis
 *   results.
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
			$output->writeLineFormatted('Mechanism:                 fork (pcntl_fork)');
			$output->writeLineFormatted('');
			return;
		}

		$output->writeLineFormatted('Mechanism:                 spawn (react/child-process)');
		$output->writeLineFormatted(sprintf('Reason fork not used:      %s', $reason));
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

		if (!TurboExtensionEnabler::isActive()) {
			return 'the turbo extension is not active (see the Turbo extension section)';
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
