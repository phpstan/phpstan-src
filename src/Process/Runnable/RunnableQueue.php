<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

use PHPStan\ShouldNotHappenException;
use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use SplObjectStorage;
use Throwable;
use function array_shift;
use function count;
use function sprintf;

class RunnableQueue
{

	/** @var array<array{Runnable, int, Deferred}> */
	private array $queue = [];

	/** @var SplObjectStorage<Runnable, array{int, Deferred}> */
	private SplObjectStorage $running;

	public function __construct(private RunnableQueueLogger $logger, private int $maxSize)
	{
		/** @var SplObjectStorage<Runnable, array{int, Deferred}> $running */
		$running = new SplObjectStorage();
		$this->running = $running;
	}

	/**
	 * @impure
	 */
	public function getQueueSize(): int
	{
		$allSize = 0;
		foreach ($this->queue as [$runnable, $size, $deferred]) {
			$allSize += $size;
		}

		return $allSize;
	}

	/**
	 * @impure
	 */
	public function getRunningSize(): int
	{
		$allSize = 0;
		foreach ($this->running as $running) { // phpcs:ignore
			[$size] = $this->running->getInfo();
			$allSize += $size;
		}

		return $allSize;
	}

	public function queue(Runnable $runnable, int $size): CancellablePromiseInterface
	{
		if ($size > $this->maxSize) {
			throw new ShouldNotHappenException('Runnable size exceeds queue maxSize.');
		}

		$deferred = new Deferred(static function () use ($runnable): void {
			$runnable->cancel();
		});
		$this->queue[] = [$runnable, $size, $deferred];
		$this->drainQueue();

		/** @var CancellablePromiseInterface */
		return $deferred->promise();
	}

	private function drainQueue(): void
	{
		if (count($this->queue) === 0) {
			$this->logger->log('Queue empty');
			return;
		}

		$currentQueueSize = $this->getRunningSize();
		if ($currentQueueSize > $this->maxSize) {
			throw new ShouldNotHappenException('Running overflow');
		}

		if ($currentQueueSize === $this->maxSize) {
			$this->logger->log('Queue is full');
			return;
		}

		$this->logger->log('Queue not full - looking at first item in the queue');

		[$runnable, $runnableSize, $deferred] = $this->queue[0];

		$newSize = $currentQueueSize + $runnableSize;
		if ($newSize > $this->maxSize) {
			$this->logger->log(
				sprintf(
					'Canot remote first item from the queue - it has size %d, current queue size is %d, new size would be %d',
					$runnableSize,
					$currentQueueSize,
					$newSize,
				),
			);
			return;
		}

		$this->logger->log(sprintf('Removing top item from queue - new size is %d', $newSize));

		/** @var array{Runnable, int, Deferred} $popped */
		$popped = array_shift($this->queue);
		if ($popped[0] !== $runnable || $popped[1] !== $runnableSize || $popped[2] !== $deferred) {
			throw new ShouldNotHappenException();
		}

		$this->running->attach($runnable, [$runnableSize, $deferred]);
		$this->logger->log(sprintf('Running process %s', $runnable->getName()));
		$runnable->run()->then(function ($value) use ($runnable, $deferred): void {
			$this->logger->log(sprintf('Process %s finished successfully', $runnable->getName()));
			$deferred->resolve($value);
			$this->running->detach($runnable);
			$this->drainQueue();
		}, function (Throwable $e) use ($runnable, $deferred): void {
			$this->logger->log(sprintf('Process %s finished unsuccessfully: %s', $runnable->getName(), $e->getMessage()));
			$deferred->reject($e);
			$this->running->detach($runnable);
			$this->drainQueue();
		});
	}

	public function cancelAll(): void
	{
		foreach ($this->queue as [$runnable, $size, $deferred]) {
			$deferred->promise()->cancel(); // @phpstan-ignore-line
		}

		$runningDeferreds = [];
		foreach ($this->running as $running) { // phpcs:ignore
			[,$deferred] = $this->running->getInfo();
			$runningDeferreds[] = $deferred;
		}

		foreach ($runningDeferreds as $deferred) {
			$deferred->promise()->cancel(); // @phpstan-ignore-line
		}
	}

}
