<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

class Scheduler
{

	/** @var int */
	private $jobSize;

	public function __construct(int $jobSize)
	{
		$this->jobSize = $jobSize;
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
		$numberOfProcesses = min(count($jobs), $cpuCores);

		return new Schedule($numberOfProcesses, $jobs);
	}

}
