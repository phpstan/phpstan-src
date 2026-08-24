<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Phar;
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
 * - when running from a phar, the turbo extension, active with the expected
 *   version. The extension arms the phar-fork-guard: libphar serves phar://
 *   reads through one shared per-archive fd whose seek cursor forked
 *   processes race on, corrupting reads — the guard gives each forked child
 *   a private cursor. TurboProcessRestarter re-executes the main process
 *   with the distributed binary loaded when needed (a forked worker inherits
 *   the parent's extensions; the spawn-time `-d extension=` injection cannot
 *   reach it). Outside a phar there is nothing to guard, and no turbo is
 *   lost by forking either: the distributed binaries only exist next to a
 *   phar, so spawned workers of a source checkout run without turbo too —
 *   an ini-loaded extension is inherited by fork like any other.
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

		if (Phar::running(false) !== '' && !TurboExtensionEnabler::isActive()) {
			return 'running from a phar without the active turbo extension (its fork guard protects phar:// reads in forked children)';
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
