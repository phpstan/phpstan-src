<?php declare(strict_types = 1);

namespace ClosurePassedToUnionOfCallables;

use function PHPStan\Testing\assertType;

/** @template T */
final class Invokable
{

	/** @param T $a */
	public function __invoke($a, string $b): void
	{
	}

}

final class Foo
{

	/**
	 * @param callable(int, string): void|callable(int): void $cb
	 */
	public function longestFirst($cb): void
	{
	}

	/**
	 * @param callable(int): void|callable(int, string): void $cb
	 */
	public function shortestFirst($cb): void
	{
	}

	/**
	 * @param callable(int): void|Invokable<string>|callable(bool, float): void $cb
	 */
	public function withInvokable($cb): void
	{
	}

	/**
	 * @param callable(int...): void|callable(string, string): void $cb
	 */
	public function variadicFirst($cb): void
	{
	}

	/**
	 * @param callable(string, string): void|callable(int...): void $cb
	 */
	public function variadicLast($cb): void
	{
	}

	public function run(): void
	{
		$this->longestFirst(function ($a, $b): void {
			assertType('int', $a);
			assertType('string', $b);
		});

		$this->shortestFirst(function ($a, $b): void {
			assertType('int', $a);
			assertType('string', $b);
		});

		$this->longestFirst(fn ($a, $b) => assertType('int', $a));
		$this->longestFirst(fn ($a, $b) => assertType('string', $b));
		$this->shortestFirst(fn ($a, $b) => assertType('int', $a));
		$this->shortestFirst(fn ($a, $b) => assertType('string', $b));

		$this->withInvokable(function ($a, $b): void {
			assertType('bool|int|string', $a);
			assertType('float|string', $b);
		});

		$this->variadicFirst(function ($a, $b): void {
			assertType('int|string', $a);
			assertType('string', $b);
		});

		$this->variadicLast(function ($a, $b): void {
			assertType('int|string', $a);
			assertType('string', $b);
		});
	}

}
