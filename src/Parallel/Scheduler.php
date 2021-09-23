<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

class Scheduler
{

	private int $jobSize;

	private int $minimumNumberOfProcesses;

	private int $maximumNumberOfProcesses;

	private int $minimumNumberOfJobsPerProcess;

	public function __construct(
		int $jobSize,
		int $minimumNumberOfProcesses,
		int $maximumNumberOfProcesses,
		int $minimumNumberOfJobsPerProcess
	)
	{
		$this->jobSize = $jobSize;
		$this->minimumNumberOfProcesses = $minimumNumberOfProcesses;
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
		// Split the files into a series of jobs to be assigned to processes
		$jobs = array_chunk($files, $this->jobSize);

		// We start by assuming we can create one process per CPU
		$numberOfProcesses = $cpuCores;
		// Number of processes must not be higher than configured maximum
		$numberOfProcesses = min($this->maximumNumberOfProcesses, $numberOfProcesses);
		// Number of processes must not be lower than configured minimum
		$numberOfProcesses = max($this->minimumNumberOfProcesses, $numberOfProcesses);
		// Number of processes must not be more than is required to run M jobs per process
		$numberOfProcesses = min((int) floor(count($jobs) / $this->minimumNumberOfJobsPerProcess), $numberOfProcesses);
		// Number of processes must be at least 1
		$numberOfProcesses = max(1, $numberOfProcesses);

		return new Schedule($numberOfProcesses, $jobs);
	}

}
