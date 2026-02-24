<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class CpuCoreCounter
{

	private ?int $count = null;

	public function __construct(
		#[AutowiredParameter(ref: '%parallel.loadLimit%')]
		private ?float $loadLimit,
	)
	{
	}

	public function getNumberOfCpuCores(): int
	{
		if ($this->count !== null) {
			return $this->count;
		}

		try {
			$this->count = (new FidryCpuCoreCounter())->getAvailableForParallelisation(0, null, $this->loadLimit)->availableCpus;
		} catch (NumberOfCpuCoreNotFound) {
			$this->count = 1;
		}

		return $this->count;
	}

}
