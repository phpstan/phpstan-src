<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;

class RunnableStub implements Runnable
{

	/** @var string */
	private $name;

	/** @var Deferred */
	private $deferred;

	public function __construct(string $name)
	{
		$this->name = $name;
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
		$this->deferred->reject(new \PHPStan\Process\Runnable\RunnableCanceledException(sprintf('Runnable %s canceled', $this->getName())));
	}

}
