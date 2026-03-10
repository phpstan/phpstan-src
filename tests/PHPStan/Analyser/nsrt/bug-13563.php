<?php declare(strict_types = 1);

namespace Bug13563;

use DateTime;
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
	 * @var array<int, DateTime>
	 */
	private array $dates = [];

	/**
	 * @var array<int, DateTime>
	 */
	private array $propNotCleared = [];

	public function setUp(): void
	{
		$invoker = new Invoker();
		$this->dates = [];

		assertType('array{}', $this->dates);

		// Arrow function should see the declared property type, not the narrowed array{} type
		$invoker->willReturnCallback('get', fn (int $id) => assertType('array<int, DateTime>', $this->dates));

		// Closure correctly sees the declared property type
		$invoker->willReturnCallback('get', function (int $id) {
			assertType('array<int, DateTime>', $this->dates);
		});

		// Property not cleared - both should see the declared type
		assertType('array<int, DateTime>', $this->propNotCleared);
		$invoker->willReturnCallback('get', fn (int $id) => assertType('array<int, DateTime>', $this->propNotCleared));
		$invoker->willReturnCallback('get', function (int $id) {
			assertType('array<int, DateTime>', $this->propNotCleared);
		});
	}
}
