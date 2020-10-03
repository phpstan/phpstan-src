<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

class Scheduler
{

	private int $jobSize;

	private int $maximumNumberOfProcesses;

	private int $minimumNumberOfJobsPerProcess;

	public function __construct(
		int $jobSize,
		int $maximumNumberOfProcesses,
		int $minimumNumberOfJobsPerProcess
	)
	{
		$this->jobSize = $jobSize;
		$this->maximumNumberOfProcesses = $maximumNumberOfProcesses;
		$this->minimumNumberOfJobsPerProcess = $minimumNumberOfJobsPerProcess;
	}

	/**
	 * @param int $cpuCores
	 * @param array<string> $files
	 * @return Schedule
	 */
	public function scheduleWork(
		int $cpuCores,
		array $files
	): Schedule
	{
		$jobs = array_chunk($files, $this->jobSize);
		$numberOfProcesses = min(
			max((int) floor(count($jobs) / $this->minimumNumberOfJobsPerProcess), 1),
			$cpuCores
		);

		return new Schedule(min($numberOfProcesses, $this->maximumNumberOfProcesses), $jobs);
	}

}
