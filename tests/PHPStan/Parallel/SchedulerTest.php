<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use PHPUnit\Framework\TestCase;

class SchedulerTest extends TestCase
{

	public function dataSchedule(): array
	{
		return [
			'should chunk files into job size requested' => [
				'cpuCores' => 3,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 50,
				'numberOfFiles' => 115,
				'expectedNumberOfProcesses' => 3,
				'expectedJobSizes' => [50, 50, 15],
			],
			'should not create more jobs than CPU cores for the same workload' => [
				'cpuCores' => 2,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 50,
				'numberOfFiles' => 115,
				'expectedNumberOfProcesses' => 2,
				'expectedJobSizes' => [50, 50, 15],
			],
			'should not create more processes than there are jobs' => [
				'cpuCores' => 16,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 30,
				'numberOfFiles' => 124,
				'expectedNumberOfProcesses' => 5,
				'expectedJobSizes' => [30, 30, 30, 30, 4],
			],
			'should not create more than the maximum number of processes' => [
				'cpuCores' => 16,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 3,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 30,
				'numberOfFiles' => 124,
				'expectedNumberOfProcesses' => 3,
				'expectedJobSizes' => [30, 30, 30, 30, 4],
			],
			'should use all available cpu cores to run processes if number of jobs is high' => [
				'cpuCores' => 16,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 10,
				'numberOfFiles' => 298,
				'expectedNumberOfProcesses' => 16,
				'expectedJobSizes' => [10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 8],
			],
			'should not create more processes than required to run min jobs per process' => [
				'cpuCores' => 16,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 2,
				'jobSize' => 30,
				'numberOfFiles' => 124,
				'expectedNumberOfProcesses' => 2,
				'expectedJobSizes' => [30, 30, 30, 30, 4],
			],
			'should create at least one process even when number of files is small' => [
				'cpuCores' => 16,
				'minimumNumberOfProcesses' => 1,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 2,
				'jobSize' => 20,
				'numberOfFiles' => 1,
				'expectedNumberOfProcesses' => 1,
				'expectedJobSizes' => [1],
			],
			'should be able to insist on using more processes than cpu cores' => [
				'cpuCores' => 1,
				'minimumNumberOfProcesses' => 2,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 50,
				'numberOfFiles' => 115,
				'expectedNumberOfProcesses' => 2,
				'expectedJobSizes' => [50, 50, 15],
			],
			'should not create more processes than jobs even if min number of processes specified' => [
				'cpuCores' => 1,
				'minimumNumberOfProcesses' => 8,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 1,
				'jobSize' => 10,
				'numberOfFiles' => 50,
				'expectedNumberOfProcesses' => 5,
				'expectedJobSizes' => [10, 10, 10, 10, 10],
			],
			'should not create more processes than required to run min jobs per process even if min number of processes specified' => [
				'cpuCores' => 1,
				'minimumNumberOfProcesses' => 8,
				'maximumNumberOfProcesses' => 16,
				'minimumNumberOfJobsPerProcess' => 2,
				'jobSize' => 10,
				'numberOfFiles' => 50,
				'expectedNumberOfProcesses' => 2,
				'expectedJobSizes' => [10, 10, 10, 10, 10],
			],
		];
	}

	/**
	 * @dataProvider dataSchedule
	 * @param int $cpuCores
	 * @param int $minimumNumberOfProcesses
	 * @param int $maximumNumberOfProcesses
	 * @param int $minimumNumberOfJobsPerProcess
	 * @param int $jobSize
	 * @param 0|positive-int $numberOfFiles
	 * @param int $expectedNumberOfProcesses
	 * @param array<int> $expectedJobSizes
	 */
	public function testSchedule(
		int $cpuCores,
		int $minimumNumberOfProcesses,
		int $maximumNumberOfProcesses,
		int $minimumNumberOfJobsPerProcess,
		int $jobSize,
		int $numberOfFiles,
		int $expectedNumberOfProcesses,
		array $expectedJobSizes
	): void
	{
		$files = array_fill(0, $numberOfFiles, 'file.php');
		$scheduler = new Scheduler($jobSize, $minimumNumberOfProcesses, $maximumNumberOfProcesses, $minimumNumberOfJobsPerProcess);
		$schedule = $scheduler->scheduleWork($cpuCores, $files);

		$this->assertSame($expectedNumberOfProcesses, $schedule->getNumberOfProcesses());
		$jobSizes = array_map(static function (array $job): int {
			return count($job);
		}, $schedule->getJobs());
		$this->assertSame($expectedJobSizes, $jobSizes);
	}

}
