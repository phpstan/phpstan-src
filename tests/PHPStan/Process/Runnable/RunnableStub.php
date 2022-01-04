<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use function sprintf;

class RunnableStub implements Runnable
{

	private Deferred $deferred;

	public function __construct(private string $name)
	{
		$this->deferred = new Deferred();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function finish(): void
	{
		$this->deferred->resolve();
	}

	public function run(): CancellablePromiseInterface
	{
		/** @var CancellablePromiseInterface */
		return $this->deferred->promise();
	}

	public function cancel(): void
	{
		$this->deferred->reject(new RunnableCanceledException(sprintf('Runnable %s canceled', $this->getName())));
	}

}
