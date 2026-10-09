<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use Fidry\CpuCoreCounter\Finder\CgroupCpuQuotaFinder;
use Fidry\CpuCoreCounter\Finder\EnvVariableFinder;
use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Process\CpuCoreCounter;
use function sprintf;

/**
 * Reports what PHPStan believes about the machine, so a user who disagrees with the
 * number of workers it chose can see which input was wrong.
 */
#[AutowiredService]
final class SystemResourcesDiagnoseExtension implements DiagnoseExtension
{

	public function __construct(
		private CpuCoreCounter $cpuCoreCounter,
		#[AutowiredParameter(ref: '%parallel.loadLimit%')]
		private ?float $loadLimit,
	)
	{
	}

	public function print(Output $output): void
	{
		$output->writeLineFormatted('<info>System resources:</info>');
		$output->writeLineFormatted(sprintf('Detected CPU cores:        %d', $this->cpuCoreCounter->getDetectedNumberOfCpuCores()));
		$output->writeLineFormatted(sprintf('Load limit:                %s', $this->loadLimit === null ? 'none' : (string) $this->loadLimit));

		// the counter applies only the first limit found, so each is looked up on its own
		// to show a KUBERNETES_CPU_LIMIT that a cgroup quota overrides
		$this->printCores($output, 'cgroup CPU quota:          %s', (new CgroupCpuQuotaFinder())->find());
		$this->printCores($output, 'KUBERNETES_CPU_LIMIT:      %s', (new EnvVariableFinder('KUBERNETES_CPU_LIMIT'))->find());

		$output->writeLineFormatted(sprintf('Usable CPU cores:          %d', $this->cpuCoreCounter->getNumberOfCpuCores()));
		$output->writeLineFormatted('');
	}

	private function printCores(Output $output, string $format, ?int $cores): void
	{
		$output->writeLineFormatted(sprintf($format, $cores === null ? 'none' : sprintf('%d cores', $cores)));
	}

}
