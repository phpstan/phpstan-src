<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use function array_chunk;
use function count;
use function floor;
use function max;
use function min;
use function sprintf;

#[AutowiredService]
final class Scheduler implements DiagnoseExtension
{

	/** @var array{int, int, int, int}|null */
	private ?array $storedData = null;

	/**
	 * @param positive-int $jobSize
	 * @param positive-int $maximumNumberOfProcesses
	 * @param positive-int $minimumNumberOfJobsPerProcess
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%parallel.jobSize%')]
		private int $jobSize,
		#[AutowiredParameter(ref: '%parallel.maximumNumberOfProcesses%')]
		private int $maximumNumberOfProcesses,
		#[AutowiredParameter(ref: '%parallel.minimumNumberOfJobsPerProcess%')]
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

		$usedNumberOfProcesses = min($numberOfProcesses, $this->maximumNumberOfProcesses);
		$this->storedData = [$cpuCores, count($files), count($jobs), $usedNumberOfProcesses];

		return new Schedule($usedNumberOfProcesses, $jobs);
	}

	public function print(Output $output): void
	{
		if ($this->storedData === null) {
			return;
		}

		[$cpuCores, $filesCount, $jobsCount, $usedNumberOfProcesses] = $this->storedData;

		$output->writeLineFormatted('<info>Parallel processing scheduler:</info>');
		$output->writeLineFormatted(sprintf('# of detected CPU cores:   %d', $cpuCores));
		$output->writeLineFormatted(sprintf('# of analysed files:       %d', $filesCount));
		$output->writeLineFormatted(sprintf('# of jobs:                 %d', $jobsCount));
		$output->writeLineFormatted(sprintf('# of spawned processes:    %d', $usedNumberOfProcesses));
		$output->writeLineFormatted('');
	}

}
