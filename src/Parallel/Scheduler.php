<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\ShouldNotHappenException;
use function array_keys;
use function array_pop;
use function asort;
use function ceil;
use function count;
use function filesize;
use function floor;
use function max;
use function min;
use function shuffle;

class Scheduler
{

	/**
	 * @param positive-int $jobSize
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
		$sizeByFile = [];
		foreach ($files as $file) {
			$size = @filesize($file);
			if ($size === false) {
				$size = 0;
			}

			$sizeByFile[$file] = $size;
		}
		$jobsCount = (int) ceil(count($files) / $this->jobSize);
		$jobs = [];

		asort($sizeByFile);
		$files = array_keys($sizeByFile);

		for ($i = 0; $i < $jobsCount; $i++) {
			$file = array_pop($files);
			if ($file === null) {
				break;
			}
			$jobs[] = [$file];
		}

		shuffle($files);

		foreach ($jobs as $i => $job) {
			while (count($job) < $this->jobSize) {
				$file = array_pop($files);
				if ($file === null) {
					break;
				}
				$job[] = $file;
			}
			$jobs[$i] = $job;
		}

		if (count($files) > 0) {
			throw new ShouldNotHappenException();
		}

		$numberOfProcesses = min(
			max((int) floor($jobsCount / $this->minimumNumberOfJobsPerProcess), 1),
			$cpuCores,
		);

		return new Schedule(min($numberOfProcesses, $this->maximumNumberOfProcesses), $jobs);
	}

}
