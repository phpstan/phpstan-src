<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use Fidry\CpuCoreCounter\ParallelisationResult;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class CpuCoreCounter
{

	private ?ParallelisationResult $result = null;

	public function __construct(
		#[AutowiredParameter(ref: '%parallel.loadLimit%')]
		private ?float $loadLimit,
	)
	{
	}

	/**
	 * Cores PHPStan may actually use: what the machine reports, reduced by the load
	 * limit and capped by the cgroup CPU quota or, failing that, KUBERNETES_CPU_LIMIT.
	 */
	public function getNumberOfCpuCores(): int
	{
		return $this->detect()->availableCpus;
	}

	/** What the machine reports, before any limit is applied. */
	public function getDetectedNumberOfCpuCores(): int
	{
		return $this->detect()->totalCoresCount;
	}

	private function detect(): ParallelisationResult
	{
		return $this->result ??= (new FidryCpuCoreCounter())->getAvailableForParallelisation(0, null, $this->loadLimit);
	}

}
