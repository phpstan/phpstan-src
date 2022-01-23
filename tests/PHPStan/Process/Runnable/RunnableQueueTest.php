<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

use Exception;
use PHPUnit\Framework\TestCase;
use function sprintf;

class RunnableQueueTest extends TestCase
{

	public function testQueuedProcessIsRun(): void
	{
		$logger = new RunnableQueueLoggerStub();
		$queue = new RunnableQueue($logger, 8);

		$one = new RunnableStub('1');
		$queue->queue($one, 1);
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(1, $queue->getRunningSize());
		$one->finish();
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(0, $queue->getRunningSize());
		$this->assertSame([
			'Queue not full - looking at first item in the queue',
			'Removing top item from queue - new size is 1',
			'Running process 1',
			'Process 1 finished successfully',
			'Queue empty',
		], $logger->getMessages());
	}

	public function testComplexScenario(): void
	{
		$logger = new RunnableQueueLoggerStub();
		$queue = new RunnableQueue($logger, 8);

		$one = new RunnableStub('1');
		$two = new RunnableStub('2');
		$three = new RunnableStub('3');
		$four = new RunnableStub('4');
		$queue->queue($one, 4);
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(4, $queue->getRunningSize());

		$queue->queue($two, 2);
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(6, $queue->getRunningSize());
		$queue->queue($three, 3);
		$this->assertSame(3, $queue->getQueueSize());
		$this->assertSame(6, $queue->getRunningSize());
		$queue->queue($four, 4);
		$this->assertSame(7, $queue->getQueueSize());
		$this->assertSame(6, $queue->getRunningSize());

		$one->finish();
		$this->assertSame(4, $queue->getQueueSize());
		$this->assertSame(5, $queue->getRunningSize());

		$two->finish();
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(7, $queue->getRunningSize());

		$three->finish();
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(4, $queue->getRunningSize());

		$four->finish();
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(0, $queue->getRunningSize());

		$this->assertSame([
			0 => 'Queue not full - looking at first item in the queue',
			1 => 'Removing top item from queue - new size is 4',
			2 => 'Running process 1',
			3 => 'Queue not full - looking at first item in the queue',
			4 => 'Removing top item from queue - new size is 6',
			5 => 'Running process 2',
			6 => 'Queue not full - looking at first item in the queue',
			7 => 'Canot remote first item from the queue - it has size 3, current queue size is 6, new size would be 9',
			8 => 'Queue not full - looking at first item in the queue',
			9 => 'Canot remote first item from the queue - it has size 3, current queue size is 6, new size would be 9',
			10 => 'Process 1 finished successfully',
			11 => 'Queue not full - looking at first item in the queue',
			12 => 'Removing top item from queue - new size is 5',
			13 => 'Running process 3',
			14 => 'Process 2 finished successfully',
			15 => 'Queue not full - looking at first item in the queue',
			16 => 'Removing top item from queue - new size is 7',
			17 => 'Running process 4',
			18 => 'Process 3 finished successfully',
			19 => 'Queue empty',
			20 => 'Process 4 finished successfully',
			21 => 'Queue empty',
		], $logger->getMessages());
	}

	public function testCancel(): void
	{
		$logger = new RunnableQueueLoggerStub();
		$queue = new RunnableQueue($logger, 8);
		$one = new RunnableStub('1');
		$promise = $queue->queue($one, 4);
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(4, $queue->getRunningSize());

		$promise->then(static function () use ($logger): void {
			$logger->log('Should not happen');
		}, static function (Exception $e) use ($logger): void {
			$logger->log(sprintf('Else callback in test called: %s', $e->getMessage()));
		});
		$promise->cancel();

		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(0, $queue->getRunningSize());

		$this->assertSame([
			0 => 'Queue not full - looking at first item in the queue',
			1 => 'Removing top item from queue - new size is 4',
			2 => 'Running process 1',
			3 => 'Process 1 finished unsuccessfully: Runnable 1 canceled',
			4 => 'Else callback in test called: Runnable 1 canceled',
			5 => 'Queue empty',
		], $logger->getMessages());
	}

	public function testCancelAll(): void
	{
		$logger = new RunnableQueueLoggerStub();
		$queue = new RunnableQueue($logger, 6);
		$one = new RunnableStub('1');
		$two = new RunnableStub('2');
		$three = new RunnableStub('3');
		$queue->queue($one, 3);
		$queue->queue($two, 2);
		$queue->queue($three, 3);

		$this->assertSame(3, $queue->getQueueSize());
		$this->assertSame(5, $queue->getRunningSize());

		$queue->cancelAll();
		$this->assertSame(0, $queue->getQueueSize());
		$this->assertSame(0, $queue->getRunningSize());

		$this->assertSame([
			0 => 'Queue not full - looking at first item in the queue',
			1 => 'Removing top item from queue - new size is 3',
			2 => 'Running process 1',
			3 => 'Queue not full - looking at first item in the queue',
			4 => 'Removing top item from queue - new size is 5',
			5 => 'Running process 2',
			6 => 'Queue not full - looking at first item in the queue',
			7 => 'Canot remote first item from the queue - it has size 3, current queue size is 5, new size would be 8',
			8 => 'Process 1 finished unsuccessfully: Runnable 1 canceled',
			9 => 'Queue not full - looking at first item in the queue',
			10 => 'Removing top item from queue - new size is 5',
			11 => 'Running process 3',
			12 => 'Process 3 finished unsuccessfully: Runnable 3 canceled',
			13 => 'Queue empty',
			14 => 'Process 2 finished unsuccessfully: Runnable 2 canceled',
			15 => 'Queue empty',
		], $logger->getMessages());
	}

}
