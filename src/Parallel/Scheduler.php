<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use function array_chunk;
use function count;
use function floor;
use function max;
use function min;

class Scheduler
{

	/**
	 * @param positive-int $jobSize
	 * @param positive-int $maximumNumberOfProcesses
	 * @param positive-int $minimumNumberOfJobsPerProcess
	 */
	public function __construct(
		private int $jobSize,
		private int $maximumNumberOfProcesses,
		private int $minimumNumberOfJobsPerProcess,
	)
	{
	}

	/**
	 * @param array<string> $files
	 */
	public function scheduleWork(
		int $cpuCores,
		array $files,
	): Schedule
	{
		$jobs = array_chunk($files, $this->jobSize);
		$numberOfProcesses = min(
			max((int) floor(count($jobs) / $this->minimumNumberOfJobsPerProcess), 1),
			$cpuCores,
		);

		return new Schedule(min($numberOfProcesses, $this->maximumNumberOfProcesses), $jobs);
	}

}
