<?php declare(strict_types = 1);

namespace PHPStan\Process;

use Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;

final class CpuCoreCounter
{

	private ?int $count = null;

	public function getNumberOfCpuCores(): int
	{
		if ($this->count !== null) {
			return $this->count;
		}

		try {
			$this->count = (new FidryCpuCoreCounter())->getCount();
		} catch (NumberOfCpuCoreNotFound) {
			$this->count = 1;
		}

		return $this->count;
	}

}
