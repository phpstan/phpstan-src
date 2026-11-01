<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_fill;
use function array_keys;
use function array_map;
use function array_merge;
use function ceil;
use function count;
use function floor;
use function max;
use function min;
use function sort;
use function sprintf;

class SchedulerTest extends TestCase
{

	public static function dataSchedule(): array
	{
		return [
			[
				1,
				16,
				1,
				50,
				115,
				1,
				[39, 38, 38],
			],
			[
				16,
				16,
				1,
				30,
				124,
				12,
				[11, 11, 11, 11, 10, 10, 10, 10, 10, 10, 10, 10],
			],
			[
				16,
				3,
				1,
				30,
				124,
				3,
				[11, 11, 11, 11, 10, 10, 10, 10, 10, 10, 10, 10],
			],
			[
				16,
				16,
				1,
				10,
				298,
				16,
				[10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 9, 9],
			],
			[
				16,
				16,
				2,
				30,
				124,
				6,
				[11, 11, 11, 11, 10, 10, 10, 10, 10, 10, 10, 10],
			],
			[
				16,
				16,
				2,
				30,
				900,
				15,
				[30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30, 30],
			],
			[
				16,
				16,
				2,
				20,
				1,
				1,
				[1],
			],
		];
	}

	/**
	 * @param positive-int $jobSize
	 * @param positive-int $maximumNumberOfProcesses
	 * @param positive-int $minimumNumberOfJobsPerProcess
	 * @param 0|positive-int $numberOfFiles
	 * @param array<int> $expectedJobSizes
	 */
	#[DataProvider('dataSchedule')]
	public function testSchedule(
		int $cpuCores,
		int $maximumNumberOfProcesses,
		int $minimumNumberOfJobsPerProcess,
		int $jobSize,
		int $numberOfFiles,
		int $expectedNumberOfProcesses,
		array $expectedJobSizes,
	): void
	{
		$files = array_fill(0, $numberOfFiles, 'file.php');
		$scheduler = new Scheduler($jobSize, $maximumNumberOfProcesses, $minimumNumberOfJobsPerProcess);
		$schedule = $scheduler->scheduleWork($cpuCores, $files, static fn (string $file): int => 0);

		$this->assertSame($expectedNumberOfProcesses, $schedule->getNumberOfProcesses());
		$jobSizes = array_map(static fn (array $job): int => count($job), $schedule->getJobs());
		$this->assertSame($expectedJobSizes, $jobSizes);
	}

	public function testHeaviestFilesAreSpreadAcrossJobs(): void
	{
		$fileSizes = [
			'a.php' => 100,
			'b.php' => 200,
			'c.php' => 300,
			'd.php' => 400,
			'e.php' => 500,
			'f.php' => 600,
		];

		$scheduler = new Scheduler(2, 16, 1);
		$schedule = $scheduler->scheduleWork(16, array_keys($fileSizes), static fn (string $file): int => $fileSizes[$file] ?? 0);

		// six files, job size 2 -> three jobs; the three heaviest files must not
		// share a job, and every job pairs one heavy file with one light file,
		// listed in input order
		$this->assertSame([
			['c.php', 'f.php'],
			['b.php', 'e.php'],
			['a.php', 'd.php'],
		], $schedule->getJobs());
	}

	public function testFilesWithinAJobKeepTheirInputOrder(): void
	{
		// a small file followed by a larger one - the size sort must only
		// influence which job a file lands in, never the analysis order
		// inside the job (analysis results can be sensitive to it)
		$fileSizes = [
			'bootstrap.php' => 327,
			'src/Middleware.php' => 560,
		];

		// a single process, so the two files stay in one job
		$scheduler = new Scheduler(20, 1, 1);
		$schedule = $scheduler->scheduleWork(16, array_keys($fileSizes), static fn (string $file): int => $fileSizes[$file] ?? 0);

		$this->assertSame([['bootstrap.php', 'src/Middleware.php']], $schedule->getJobs());
	}

	public function testEveryFileIsScheduledExactlyOnce(): void
	{
		$files = [];
		$sizes = [];
		for ($i = 0; $i < 47; $i++) {
			$file = sprintf('file-%d.php', $i);
			$files[] = $file;
			$sizes[$file] = ($i * 37) % 1000;
		}

		$scheduler = new Scheduler(10, 16, 1);
		$schedule = $scheduler->scheduleWork(16, $files, static fn (string $file): int => $sizes[$file]);

		$scheduled = array_merge(...$schedule->getJobs());
		sort($scheduled);
		sort($files);
		$this->assertSame($files, $scheduled);

		foreach ($schedule->getJobs() as $job) {
			$this->assertLessThanOrEqual(10, count($job));
		}
	}

	public function testAutoUsesAllUsableCores(): void
	{
		// 12 usable cores, plenty of jobs - auto follows the cores, not the old
		// fixed default of 8
		$scheduler = new Scheduler(1, Scheduler::AUTO, 1);
		$schedule = $scheduler->scheduleWork(12, array_fill(0, 200, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(12, $schedule->getNumberOfProcesses());
	}

	public function testAutoIsCappedAtTheProcessLimit(): void
	{
		// 64 usable cores and 5000 files worth of jobs - auto stops where the
		// returns diminish rather than spawning a worker per core
		$scheduler = new Scheduler(20, Scheduler::AUTO, 2);
		$schedule = $scheduler->scheduleWork(64, array_fill(0, 5000, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(20, $schedule->getNumberOfProcesses());
	}

	public function testAutoIsStillCappedByTheJobCount(): void
	{
		// 8 files -> 3 jobs even at the smaller size of 3, at least 2 jobs per
		// process -> a single worker no matter how many cores the machine has
		$scheduler = new Scheduler(20, Scheduler::AUTO, 2);
		$schedule = $scheduler->scheduleWork(32, array_fill(0, 8, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(1, $schedule->getNumberOfProcesses());
	}

	public function testAnExplicitLimitStillWins(): void
	{
		$scheduler = new Scheduler(1, 20, 1);
		$schedule = $scheduler->scheduleWork(32, array_fill(0, 200, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(20, $schedule->getNumberOfProcesses());
	}

	public function testSmallRunsUseSmallerJobs(): void
	{
		// 50 files -> 3 jobs at size 20, which is one worker - jobs of
		// round(sqrt(50)) = 7 files make 8 jobs for 4 workers
		$scheduler = new Scheduler(20, Scheduler::AUTO, 2);
		$schedule = $scheduler->scheduleWork(14, array_fill(0, 50, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(4, $schedule->getNumberOfProcesses());
		$this->assertCount(8, $schedule->getJobs());
	}

	public function testASingleProcessKeepsTheConfiguredJobSize(): void
	{
		// one process analyses its jobs one after another, so smaller jobs
		// would only change the order of its files
		$callback = static fn (string $file): int => 0;

		$tooFewFiles = (new Scheduler(20, Scheduler::AUTO, 2))->scheduleWork(14, array_fill(0, 8, 'file.php'), $callback);
		$this->assertSame(1, $tooFewFiles->getNumberOfProcesses());
		$this->assertCount(1, $tooFewFiles->getJobs());

		$oneProcessAllowed = (new Scheduler(20, 1, 2))->scheduleWork(14, array_fill(0, 200, 'file.php'), $callback);
		$this->assertSame(1, $oneProcessAllowed->getNumberOfProcesses());
		$this->assertCount(10, $oneProcessAllowed->getJobs());

		$oneCore = (new Scheduler(20, Scheduler::AUTO, 2))->scheduleWork(1, array_fill(0, 200, 'file.php'), $callback);
		$this->assertSame(1, $oneCore->getNumberOfProcesses());
		$this->assertCount(10, $oneCore->getJobs());
	}

	public function testLargeRunsKeepTheConfiguredJobSize(): void
	{
		foreach ([381, 400, 4524] as $numberOfFiles) {
			$schedule = (new Scheduler(20, Scheduler::AUTO, 2))->scheduleWork(14, array_fill(0, $numberOfFiles, 'file.php'), static fn (string $file): int => 0);

			$this->assertCount((int) ceil($numberOfFiles / 20), $schedule->getJobs(), sprintf('%d files', $numberOfFiles));
		}
	}

	public function testSmallerJobsNeverMeanFewerProcesses(): void
	{
		$callback = static fn (string $file): int => 0;
		for ($numberOfFiles = 1; $numberOfFiles <= 1000; $numberOfFiles++) {
			$schedule = (new Scheduler(20, Scheduler::AUTO, 2))->scheduleWork(14, array_fill(0, $numberOfFiles, 'file.php'), $callback);
			$configuredJobSizeProcesses = min(max((int) floor((int) ceil($numberOfFiles / 20) / 2), 1), 14);

			$this->assertGreaterThanOrEqual($configuredJobSizeProcesses, $schedule->getNumberOfProcesses(), sprintf('%d files', $numberOfFiles));
		}
	}

}
