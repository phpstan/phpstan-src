<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

class Scheduler
{

	/** @var int */
	private $jobSize;

	/** @var int */
	private $maximumNumberOfProcesses;

	/** @var int */
	private $minimumNumberOfJobsPerProcess;

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
			(int) floor(count($jobs) / $this->minimumNumberOfJobsPerProcess),
			$cpuCores
		);

		return new Schedule(min($numberOfProcesses, $this->maximumNumberOfProcesses), $jobs);
	}

}
