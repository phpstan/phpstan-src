<?php declare(strict_types=1);

namespace Bug13563;

use function PHPStan\Testing\assertType;

class Invoker
{
	/**
	 * @var array<string, \Closure>
	 */
	private array $callbacks = [];

	public function willReturnCallback(string $method, callable $callback): void
	{
		$this->callbacks[$method] = \Closure::fromCallable($callback);
	}
}

class MyTest
{
	/**
	 * @var array<int, \DateTime>
	 */
	private array $dates = [];

	/**
	 * @var array<int, \DateTime>
	 */
	private array $propNotCleared = [];

	public function setUp(): void
	{
		$invoker = new Invoker();
		$this->dates = [];

		// Arrow function should see the PHPDoc type, not the narrowed array{} from parent scope
		$invoker->willReturnCallback('get1', fn (int $id) => assertType('array<int, DateTime>', $this->dates));

		// Closure sees the PHPDoc type - this works correctly
		$invoker->willReturnCallback('get2', function (int $id) {
			assertType('array<int, DateTime>', $this->dates);
		});

		// Property not cleared - both should see PHPDoc type
		$invoker->willReturnCallback('get3', fn (int $id) => assertType('array<int, DateTime>', $this->propNotCleared));

		$invoker->willReturnCallback('get4', function (int $id) {
			assertType('array<int, DateTime>', $this->propNotCleared);
		});

		// Arrow function accessing property via array dim fetch - should also use PHPDoc type
		$invoker->willReturnCallback('get5', fn (int $id): ?\DateTime => $this->dates[$id] ?? null);
	}
}
