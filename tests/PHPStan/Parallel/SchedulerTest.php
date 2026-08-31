<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_fill;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
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
				5,
				[25, 25, 25, 25, 24],
			],
			[
				16,
				3,
				1,
				30,
				124,
				3,
				[25, 25, 25, 25, 24],
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
				2,
				[25, 25, 25, 25, 24],
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

		$scheduler = new Scheduler(20, 16, 1);
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
		// 40 files -> 2 jobs at size 20, at least 2 jobs per process -> a single
		// worker no matter how many cores the machine has
		$scheduler = new Scheduler(20, Scheduler::AUTO, 2);
		$schedule = $scheduler->scheduleWork(32, array_fill(0, 40, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(1, $schedule->getNumberOfProcesses());
	}

	public function testAnExplicitLimitStillWins(): void
	{
		$scheduler = new Scheduler(1, 20, 1);
		$schedule = $scheduler->scheduleWork(32, array_fill(0, 200, 'file.php'), static fn (string $file): int => 0);

		$this->assertSame(20, $schedule->getNumberOfProcesses());
	}

}
