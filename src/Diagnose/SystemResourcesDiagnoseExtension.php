<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Process\CpuCoreCounter;
use PHPStan\Process\SystemResources;
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
		private SystemResources $systemResources,
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

		$kubernetesLimit = $this->cpuCoreCounter->getKubernetesCpuLimit();
		$output->writeLineFormatted(sprintf(
			'KUBERNETES_CPU_LIMIT:      %s',
			$kubernetesLimit === null ? 'none' : sprintf('%d cores', $kubernetesLimit),
		));
		$output->writeLineFormatted(sprintf('Available after limits:    %d', $this->cpuCoreCounter->getNumberOfCpuCoresAfterLimits()));

		$quota = $this->systemResources->getCpuQuota();
		$output->writeLineFormatted(sprintf(
			'cgroup CPU quota:          %s',
			$quota === null ? 'none' : sprintf('%d cores', $quota),
		));

		$output->writeLineFormatted(sprintf('Usable CPU cores:          %d', $this->cpuCoreCounter->getNumberOfCpuCores()));
		$output->writeLineFormatted('');
	}

}
