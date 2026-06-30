<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14888;

class TransactionRunner
{
	public function wrapInTransaction(callable $cb): void
	{
		$cb();
	}
}

class Service
{
	/** @var list<callable> */
	private array $callbacks = [];

	public function __construct(private TransactionRunner $runner)
	{
	}

	public function run(): void
	{
		$this->callbacks = [];
		$this->runner->wrapInTransaction(function (): void {
			$this->callbacks[] = static function (): void {
			};
		});

		foreach ($this->callbacks as $cb) {
			$cb();
		}
	}
}
