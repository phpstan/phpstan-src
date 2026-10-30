<?php

namespace PhpStanIgnoreVar;

use function PHPStan\Testing\assertType;

/** @return iterable<array-key, \DateTime> */
function getTimes(int $n): iterable
{
	while ($n--) {
		yield new \DateTime();
	}
}

function doFoo(): void
{
	/**
	 * @var \DateTime[] $times
	 * @phpstan-ignore-var
	 */
	$times = getTimes(3);
	assertType('iterable<(int|string), DateTime>', $times);

	/**
	 * @var list<\DateTime>
	 * @phpstan-ignore-var
	 */
	$timesWithoutVariableName = getTimes(3);
	assertType('iterable<(int|string), DateTime>', $timesWithoutVariableName);

	/**
	 * @phpstan-var list<\DateTime> $timesFromPhpStanVar
	 * @phpstan-ignore-var
	 */
	$timesFromPhpStanVar = getTimes(3);
	assertType('iterable<(int|string), DateTime>', $timesFromPhpStanVar);

	/** @var \DateTime[] $timesFromVar */
	$timesFromVar = getTimes(3);
	assertType('array<DateTime>', $timesFromVar);
}

/**
 * @param array<int, mixed> $items
 */
function doBar(array $items): void
{
	/**
	 * @var string $item
	 * @phpstan-ignore-var
	 */
	foreach ($items as $item) {
		assertType('mixed', $item);
	}
}

class Foo
{

	/**
	 * @var list<int>
	 * @phpstan-ignore-var
	 */
	public array $ids = [];

}

function doBaz(Foo $foo): void
{
	assertType('array', $foo->ids);
}
