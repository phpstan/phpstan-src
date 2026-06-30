<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14888Nsrt;

use function PHPStan\Testing\assertType;

class Runner
{
	public function wrap(callable $cb): void
	{
		$cb();
	}
}

class LaterRunner
{
	/** @param-later-invoked-callable $cb */
	public function wrap(callable $cb): void
	{
	}
}

class Service
{
	/** @var list<int> */
	private array $cb = [];

	private ?string $conn = null;

	public function __construct(private Runner $runner, private LaterRunner $later)
	{
	}

	public function viaClosure(): void
	{
		$this->cb = [];
		$this->runner->wrap(function (): void {
			$this->cb[] = 1;
		});
		assertType('list<int>', $this->cb);
	}

	public function viaArrowFunction(): void
	{
		$this->cb = [];
		$this->runner->wrap(fn () => $this->cb[] = 1);
		assertType('list<int>', $this->cb);
	}

	public function viaVariable(): void
	{
		$this->cb = [];
		$fn = function (): void {
			$this->cb[] = 1;
		};
		$this->runner->wrap($fn);
		assertType('list<int>', $this->cb);
	}

	public function laterInvokedClosureKeepsNarrowing(): void
	{
		$this->conn = null;
		$this->later->wrap(function (): void {
			$this->conn = 'x';
		});
		assertType('null', $this->conn);
	}

	public function laterInvokedArrowKeepsNarrowing(): void
	{
		$this->conn = null;
		$this->later->wrap(fn () => $this->conn = 'x');
		assertType('null', $this->conn);
	}
}
