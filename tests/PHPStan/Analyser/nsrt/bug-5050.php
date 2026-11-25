<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug5050;

use function PHPStan\Testing\assertType;

class Processor
{
	/** @var bool */
	protected $itsErrorOccurred = false;

	/**
	 * @phpstan-impure
	 * @param array<mixed> $theMatches
	 */
	protected function DoCallback(array $theMatches): string
	{
		if (!array_key_exists('error', $theMatches)) {
			$this->itsErrorOccurred = true;
		}
		return '';
	}

	/**
	 * @phpstan-pure
	 * @param array<mixed> $theMatches
	 */
	protected function pureCallback(array $theMatches): string
	{
		return '';
	}

	public function Do(string $theTemplate): void
	{
		$this->itsErrorOccurred = false;
		\preg_replace_callback('/\{(?<error>ERROR)?\}/', [$this, 'DoCallback'], $theTemplate);
		assertType('bool', $this->itsErrorOccurred);
	}

	public function firstClassCallable(string $theTemplate): void
	{
		$this->itsErrorOccurred = false;
		\preg_replace_callback('/a/', $this->DoCallback(...), $theTemplate);
		assertType('bool', $this->itsErrorOccurred);
	}

	public function userFunction(self $other): void
	{
		$other->itsErrorOccurred = false;
		callNow([$other, 'DoCallback']);
		assertType('bool', $other->itsErrorOccurred);
	}

	public function pure(string $theTemplate): void
	{
		$this->itsErrorOccurred = false;
		\preg_replace_callback('/a/', [$this, 'pureCallback'], $theTemplate);
		assertType('false', $this->itsErrorOccurred);
	}

	public function laterInvoked(): void
	{
		$this->itsErrorOccurred = false;
		callLater([$this, 'DoCallback']);
		assertType('false', $this->itsErrorOccurred);
	}

	public function storedByConstructor(): void
	{
		$this->itsErrorOccurred = false;
		new Task($this->DoCallback(...));
		assertType('false', $this->itsErrorOccurred);
	}

	public function storedByMethod(Task $task): void
	{
		$this->itsErrorOccurred = false;
		$task->then([$this, 'DoCallback']);
		assertType('false', $this->itsErrorOccurred);
	}
}

class Task
{
	/** @var callable(array<mixed>): string */
	private $callback;

	/** @param callable(array<mixed>): string $callback */
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/** @param callable(array<mixed>): string $callback */
	public function then(callable $callback): void
	{
		$this->callback = $callback;
	}
}

/** @param callable(array<mixed>): string $cb */
function callNow(callable $cb): void
{
	$cb([]);
}

/** @param-later-invoked-callable $cb */
function callLater(callable $cb): void
{
}
