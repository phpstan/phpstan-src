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

		// Arrow function after property reset - should use PHPDoc type, not narrowed empty array
		$invoker->willReturnCallback('get1', fn (int $id) => assertType('array<int, DateTime>', $this->dates));

		// Closure after property reset - should use PHPDoc type
		$invoker->willReturnCallback('get2', function (int $id) {
			assertType('array<int, DateTime>', $this->dates);
		});

		// Arrow function without property reset - should use PHPDoc type
		$invoker->willReturnCallback('get3', fn (int $id) => assertType('array<int, DateTime>', $this->propNotCleared));

		// Closure without property reset - should use PHPDoc type
		$invoker->willReturnCallback('get4', function (int $id) {
			assertType('array<int, DateTime>', $this->propNotCleared);
		});
	}
}
