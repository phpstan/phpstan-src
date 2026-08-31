<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use function min;

#[AutowiredService]
final class CpuCoreCounter
{

	private ?int $count = null;

	private ?int $detectedCount = null;

	public function __construct(
		#[AutowiredParameter(ref: '%parallel.loadLimit%')]
		private ?float $loadLimit,
		private SystemResources $systemResources,
	)
	{
	}

	/**
	 * Cores PHPStan may actually use: what the machine reports, capped by the CPU
	 * quota of the cgroup it runs in.
	 */
	public function getNumberOfCpuCores(): int
	{
		if ($this->count !== null) {
			return $this->count;
		}

		$count = $this->getDetectedNumberOfCpuCores();

		// fidry/cpu-core-counter has no cgroup finder, and its nproc-based default
		// honours a cpuset affinity mask but not a CFS bandwidth quota, so inside a
		// `docker run --cpus=2` container it reports the host's core count
		$quota = $this->systemResources->getCpuQuota();
		if ($quota !== null) {
			$count = min($count, $quota);
		}

		return $this->count = $count;
	}

	/** What the machine reports before any cgroup quota is applied. */
	public function getDetectedNumberOfCpuCores(): int
	{
		if ($this->detectedCount !== null) {
			return $this->detectedCount;
		}

		try {
			$this->detectedCount = (new FidryCpuCoreCounter())->getAvailableForParallelisation(0, null, $this->loadLimit)->availableCpus;
		} catch (NumberOfCpuCoreNotFound) {
			$this->detectedCount = 1;
		}

		return $this->detectedCount;
	}

}
