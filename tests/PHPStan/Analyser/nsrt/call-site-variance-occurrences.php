<?php declare(strict_types = 1);

namespace CallSiteVarianceOccurrences;

use function PHPStan\Testing\assertType;

class Bar
{

}

/**
 * @template T
 */
class Foo
{

	/**
	 * @return callable(T): T
	 */
	public function get(): callable
	{
		throw new \Exception();
	}

	/**
	 * @return array{callable(T): void, T}
	 */
	public function pair(): array
	{
		throw new \Exception();
	}

	/**
	 * @param callable(T): T $cb
	 */
	public function set(callable $cb): void
	{
	}

}

/**
 * @param Foo<covariant Bar> $foo
 */
function covariant(Foo $foo): void
{
	assertType('callable(never): CallSiteVarianceOccurrences\Bar', $foo->get());
	assertType('array{callable(never): void, CallSiteVarianceOccurrences\Bar}', $foo->pair());
	$foo->set(function ($x) {
		assertType('CallSiteVarianceOccurrences\Bar', $x);

		return $x;
	});
}

/**
 * @param Foo<contravariant Bar> $foo
 */
function contravariant(Foo $foo): void
{
	assertType('callable(CallSiteVarianceOccurrences\Bar): mixed', $foo->get());
	assertType('array{callable(CallSiteVarianceOccurrences\Bar): void, mixed}', $foo->pair());
	$foo->set(function ($x) {
		assertType('mixed', $x);

		return $x;
	});
}

/**
 * @param Foo<*> $foo
 */
function bivariant(Foo $foo): void
{
	assertType('callable(never): mixed', $foo->get());
	assertType('array{callable(never): void, mixed}', $foo->pair());
}
