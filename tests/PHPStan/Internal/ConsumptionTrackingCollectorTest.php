<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class ConsumptionTrackingCollectorTest extends TestCase
{

	public function testConsumptionTrackersAreAdded(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$data = ['file' => 'phpunit', 'timeConsumed' => 123.45, 'memoryConsumed' => 123, 'totalMemoryConsumed' => 200];

		$collector->addConsumption(FileConsumptionTracker::createFromArray($data));

		$expectedTimeResult = [$data['file'] => $data['timeConsumed']];
		$expectedMemoryResult = [$data['file'] => $data['memoryConsumed']];

		$this->assertEquals($expectedTimeResult, $collector->getTopTimeConsumers());
		$this->assertEquals($expectedMemoryResult, $collector->getTopMemoryConsumers());
		$this->assertEquals($data['totalMemoryConsumed'], $collector->getTotalMemoryConsumed());
	}

	public function testTopTimeConsumersAreReturnedSorted(): void
	{
		$collector = new ConsumptionTrackingCollector(3);

		$dataSets = [];
		$dataSets['skipped 1'] = ['file' => 'skipped 1', 'timeConsumed' => 0.123, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];
		$dataSets['1st'] = ['file' => '1st', 'timeConsumed' => 6.78, 'memoryConsumed' => 2, 'totalMemoryConsumed' => 200];
		$dataSets['skipped 2'] = ['file' => 'skipped 2', 'timeConsumed' => 0.234, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];
		$dataSets['2nd'] = ['file' => '2nd', 'timeConsumed' => 4.56, 'memoryConsumed' => 4, 'totalMemoryConsumed' => 200];
		$dataSets['3rd'] = ['file' => '3rd', 'timeConsumed' => 3.45, 'memoryConsumed' => 3, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$expectedResult = [
			$dataSets['1st']['file'] => $dataSets['1st']['timeConsumed'],
			$dataSets['2nd']['file'] => $dataSets['2nd']['timeConsumed'],
			$dataSets['3rd']['file'] => $dataSets['3rd']['timeConsumed'],
		];

		$this->assertEquals($expectedResult, $collector->getTopTimeConsumers(), 'top time consumer not as expected');
	}

	public function testTopTimeConsumersWillHandleNoFilesAdded(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$this->assertEquals([], $collector->getTopTimeConsumers(), 'top memory consumers not as expected');
	}

	public function testTopTimeConsumersCanBeReturnedHumanised(): void
	{
		$collector = new ConsumptionTrackingCollector(3);

		$dataSets = [];
		$dataSets['2nd'] = ['file' => '2nd', 'timeConsumed' => 4.56, 'memoryConsumed' => 4, 'totalMemoryConsumed' => 200];
		$dataSets['1st'] = ['file' => '1st', 'timeConsumed' => 6.78, 'memoryConsumed' => 2, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$expectedResult = [
			$dataSets['1st']['file'] => TimeHelper::humaniseFractionalSeconds($dataSets['1st']['timeConsumed']),
			$dataSets['2nd']['file'] => TimeHelper::humaniseFractionalSeconds($dataSets['2nd']['timeConsumed']),
		];

		$this->assertEquals($expectedResult, $collector->getHumanisedTopTimeConsumers(), 'top time consumer not as expected');
	}

	public function testTopMemoryConsumersAreReturnedSorted(): void
	{
		$collector = new ConsumptionTrackingCollector(3);

		$dataSets = [];
		$dataSets['skipped 1'] = ['file' => 'skipped 1', 'timeConsumed' => 0.123, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];
		$dataSets['3rd'] = ['file' => '3rd', 'timeConsumed' => 6.78, 'memoryConsumed' => 2, 'totalMemoryConsumed' => 200];
		$dataSets['skipped 2'] = ['file' => 'skipped 2', 'timeConsumed' => 0.234, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];
		$dataSets['1st'] = ['file' => '1st', 'timeConsumed' => 4.56, 'memoryConsumed' => 4, 'totalMemoryConsumed' => 200];
		$dataSets['2nd'] = ['file' => '2nd', 'timeConsumed' => 3.45, 'memoryConsumed' => 3, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$expectedResult = [
			$dataSets['1st']['file'] => $dataSets['1st']['memoryConsumed'],
			$dataSets['2nd']['file'] => $dataSets['2nd']['memoryConsumed'],
			$dataSets['3rd']['file'] => $dataSets['3rd']['memoryConsumed'],
		];

		$this->assertEquals($expectedResult, $collector->getTopMemoryConsumers(), 'top memory consumers not as expected');
	}

	public function testTopMemoryConsumersWillHandleNoFilesAdded(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$this->assertEquals([], $collector->getTopMemoryConsumers(), 'top memory consumers not as expected');
	}

	public function testTopMemoryConsumersCanBeReturnedHumanised(): void
	{
		$collector = new ConsumptionTrackingCollector(3);

		$dataSets = [];
		$dataSets['2nd'] = ['file' => '2nd', 'timeConsumed' => 6.78, 'memoryConsumed' => 2, 'totalMemoryConsumed' => 200];
		$dataSets['1st'] = ['file' => '1st', 'timeConsumed' => 4.56, 'memoryConsumed' => 4, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$expectedResult = [
			$dataSets['1st']['file'] => BytesHelper::bytes($dataSets['1st']['memoryConsumed']),
			$dataSets['2nd']['file'] => BytesHelper::bytes($dataSets['2nd']['memoryConsumed']),
		];

		$this->assertEquals($expectedResult, $collector->getHumanisedTopMemoryConsumers(), 'top memory consumer not as expected');
	}

	public function testOverflowIsPurged(): void
	{
		$collector = new ConsumptionTrackingCollector(1, 1);

		$dataSets = [];
		// will be purged from time and purged from memory - poor boy
		$dataSets['1st file - purged from both'] = ['file' => '1st file', 'timeConsumed' => 0.123, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];
		// will be returned for time / purged from memory
		$dataSets['2nd file - top time'] = ['file' => '2nd file', 'timeConsumed' => 1.234, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];
		// will be purged from time / returned for memory
		$dataSets['3rd file - top memory'] = ['file' => '1st file', 'timeConsumed' => 0.234, 'memoryConsumed' => 1, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$expectedTimeResult = [$dataSets['2nd file - top time']['file'] => $dataSets['2nd file - top time']['timeConsumed']];
		$expectedMemoryResult = [$dataSets['3rd file - top memory']['file'] => $dataSets['3rd file - top memory']['memoryConsumed']];

		$this->assertEquals($expectedTimeResult, $collector->getTopTimeConsumers(), 'top time consumers not as expected');
		$this->assertEquals($expectedMemoryResult, $collector->getTopMemoryConsumers(), 'top memory consumers not as expected');
	}

	public function testExpectedTimeConsumedIsReturnedForLatestFile(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$dataSets = [];
		$dataSets['1st'] = ['file' => '1st file', 'timeConsumed' => 1.234, 'memoryConsumed' => 1, 'totalMemoryConsumed' => 200];
		$dataSets['2nd'] = ['file' => '2nd file', 'timeConsumed' => 0.123, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$this->assertEquals($dataSets['2nd']['timeConsumed'], $collector->getTimeConsumedForLatestFile());
	}


	public function testExpectedMemoryConsumedIsReturnedForLatestFile(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$dataSets = [];
		$dataSets['1st'] = ['file' => '1st file', 'timeConsumed' => 1.234, 'memoryConsumed' => 1, 'totalMemoryConsumed' => 200];
		$dataSets['2nd'] = ['file' => '2nd file', 'timeConsumed' => 0.123, 'memoryConsumed' => 0, 'totalMemoryConsumed' => 200];

		foreach ($dataSets as $dataSet) {
			$collector->addConsumption(FileConsumptionTracker::createFromArray($dataSet));
		}

		$this->assertEquals($dataSets['2nd']['memoryConsumed'], $collector->getMemoryConsumedForLatestFile());
	}

	public function testTimeConsumedForLatestFileWillThrowOnNoFilesAdded(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('no files were tracked');

		$collector->getTimeConsumedForLatestFile();
	}

	public function testMemoryConsumedForLatestFileWillThrowOnNoFilesAdded(): void
	{
		$collector = new ConsumptionTrackingCollector();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('no files were tracked');

		$collector->getMemoryConsumedForLatestFile();
	}

}
