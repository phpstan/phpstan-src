<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class ParallelConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly int|Undefined $jobSize = Undefined::Undefined,
		private readonly float|Undefined $processTimeout = Undefined::Undefined,
		private readonly int|Undefined $maximumNumberOfProcesses = Undefined::Undefined,
		private readonly int|Undefined $minimumNumberOfJobsPerProcess = Undefined::Undefined,
		private readonly int|Undefined $buffer = Undefined::Undefined,
	)
	{
	}

}
