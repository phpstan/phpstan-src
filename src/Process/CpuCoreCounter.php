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

	/**
	 * What fidry/cpu-core-counter found: the raw count, the count left after the
	 * load limit and KUBERNETES_CPU_LIMIT were applied, and that environment limit.
	 *
	 * @var array{int, int, int|null}|null
	 */
	private ?array $detected = null;

	public function __construct(
		#[AutowiredParameter(ref: '%parallel.loadLimit%')]
		private ?float $loadLimit,
		private SystemResources $systemResources,
	)
	{
	}

	/**
	 * Cores PHPStan may actually use: what the machine reports, reduced by the load
	 * limit and capped by the CPU quota of the cgroup it runs in.
	 */
	public function getNumberOfCpuCores(): int
	{
		if ($this->count !== null) {
			return $this->count;
		}

		$count = $this->getNumberOfCpuCoresAfterLimits();

		// fidry/cpu-core-counter has no cgroup finder, and its nproc-based default
		// honours a cpuset affinity mask but not a CFS bandwidth quota, so inside a
		// `docker run --cpus=2` container it reports the host's core count
		$quota = $this->systemResources->getCpuQuota();
		if ($quota !== null) {
			$count = min($count, $quota);
		}

		return $this->count = $count;
	}

	/** What the machine reports, before any limit is applied. */
	public function getDetectedNumberOfCpuCores(): int
	{
		return $this->detect()[0];
	}

	/** The detected count after the load limit and KUBERNETES_CPU_LIMIT, before any cgroup quota. */
	public function getNumberOfCpuCoresAfterLimits(): int
	{
		return $this->detect()[1];
	}

	public function getKubernetesCpuLimit(): ?int
	{
		return $this->detect()[2];
	}

	/** @return array{int, int, int|null} */
	private function detect(): array
	{
		if ($this->detected !== null) {
			return $this->detected;
		}

		try {
			$result = (new FidryCpuCoreCounter())->getAvailableForParallelisation(0, null, $this->loadLimit);
			$this->detected = [$result->totalCoresCount, $result->availableCpus, $result->correctedCountLimit];
		} catch (NumberOfCpuCoreNotFound) {
			$this->detected = [1, 1, null];
		}

		return $this->detected;
	}

}
