<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

class Scheduler
{

	/**
	 * @param int $cpuCores
	 * @param int $jobSize
	 * @param array<string> $files
	 * @return Schedule
	 */
	public function scheduleWork(
		int $cpuCores,
		int $jobSize,
		array $files
	): Schedule
	{
		$jobs = array_chunk($files, $jobSize);
		$numberOfProcesses = min(count($jobs), $cpuCores);

		return new Schedule($numberOfProcesses, $jobs);
	}

}
