<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Diagnose\DiagnoseExtension;
use function array_values;
use function ceil;
use function count;
use function floor;
use function max;
use function min;
use function sprintf;
use function usort;

#[AutowiredService]
final class Scheduler implements DiagnoseExtension
{

	public const AUTO = 'auto';

	/**
	 * Where auto scaling stops, because the returns diminish: on a 16c/32t
	 * workstation going from 8 to 16 workers cut wall time by a third for 20 %
	 * more CPU time, 16 to 32 bought 6 % for 73 % more, and memory grows with
	 * every worker (https://github.com/phpstan/phpstan-src/pull/6256).
	 */
	private const AUTO_PROCESSES_LIMIT = 20;

	/** @var array{int, int, int, int, string}|null */
	private ?array $storedData = null;

	/**
	 * @param positive-int $jobSize
	 * @param positive-int|self::AUTO $maximumNumberOfProcesses
	 * @param positive-int $minimumNumberOfJobsPerProcess
	 */
	public function __construct(
		#[AutowiredParameter(ref: '%parallel.jobSize%')]
		private int $jobSize,
		#[AutowiredParameter(ref: '%parallel.maximumNumberOfProcesses%')]
		private int|string $maximumNumberOfProcesses,
		#[AutowiredParameter(ref: '%parallel.minimumNumberOfJobsPerProcess%')]
		private int $minimumNumberOfJobsPerProcess,
	)
	{
	}

	/**
	 * @param array<string> $files
	 * @param callable(string): int $fileSizeCallback
	 */
	public function scheduleWork(
		int $cpuCores,
		array $files,
		callable $fileSizeCallback,
	): Schedule
	{
		// sort by size and deal files round-robin across jobs so every job mixes
		// large and small files - chunking a sorted list would concentrate the
		// heaviest files into a single job and create one long-running straggler
		$fileSizes = [];
		$originalOrder = [];
		foreach ($files as $i => $file) {
			$fileSizes[$file] = $fileSizeCallback($file);
			$originalOrder[$file] = $i;
		}
		usort($files, static fn (string $a, string $b): int => $fileSizes[$b] <=> $fileSizes[$a]);

		$numberOfJobs = (int) ceil(count($files) / $this->jobSize);
		$stripedJobs = [];
		foreach ($files as $i => $file) {
			$stripedJobs[$i % $numberOfJobs][] = $file;
		}

		// only the job composition should change, not the order in which files
		// of a job get analysed - analysis results can be sensitive to it
		foreach ($stripedJobs as &$stripedJob) {
			usort($stripedJob, static fn (string $a, string $b): int => $originalOrder[$a] <=> $originalOrder[$b]);
		}
		unset($stripedJob);

		$jobs = array_values($stripedJobs);
		$numberOfProcesses = min(
			max((int) floor(count($jobs) / $this->minimumNumberOfJobsPerProcess), 1),
			$cpuCores,
		);

		[$maximumNumberOfProcesses, $decision] = $this->resolveMaximumNumberOfProcesses($cpuCores);
		$usedNumberOfProcesses = min($numberOfProcesses, $maximumNumberOfProcesses);
		$this->storedData = [$cpuCores, count($files), count($jobs), $usedNumberOfProcesses, $decision];

		return new Schedule($usedNumberOfProcesses, $jobs);
	}

	/**
	 * How many workers may run at once, and a human-readable account of why - which
	 * `diagnose` prints, because a user who thinks the number is wrong needs to see
	 * which input produced it.
	 *
	 * @return array{positive-int, string}
	 */
	private function resolveMaximumNumberOfProcesses(int $cpuCores): array
	{
		if ($this->maximumNumberOfProcesses !== self::AUTO) {
			return [$this->maximumNumberOfProcesses, 'configured'];
		}

		if ($cpuCores > self::AUTO_PROCESSES_LIMIT) {
			return [
				self::AUTO_PROCESSES_LIMIT,
				sprintf('auto, capped at %d processes (%d usable CPU cores)', self::AUTO_PROCESSES_LIMIT, $cpuCores),
			];
		}

		return [
			max(1, $cpuCores),
			sprintf('auto, limited by %d usable CPU cores', $cpuCores),
		];
	}

	public function print(Output $output): void
	{
		if ($this->storedData === null) {
			return;
		}

		[$cpuCores, $filesCount, $jobsCount, $usedNumberOfProcesses, $decision] = $this->storedData;

		$output->writeLineFormatted('<info>Parallel processing scheduler:</info>');
		$output->writeLineFormatted(sprintf('# of detected CPU cores:   %d', $cpuCores));
		$output->writeLineFormatted(sprintf('# of analysed files:       %d', $filesCount));
		$output->writeLineFormatted(sprintf('# of jobs:                 %d', $jobsCount));
		$output->writeLineFormatted(sprintf('# of spawned processes:    %d', $usedNumberOfProcesses));
		$output->writeLineFormatted(sprintf('Process limit:             %s', $decision));
		$output->writeLineFormatted('');
	}

}
