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

function doBaz(int $i): void
{
	/**
	 * @var int<0, max> $i
	 * @phpstan-ignore-var
	 */
	echo $i;
	assertType('int', $i);
}

function doStatic(int $i): void
{
	/**
	 * @var array<int, string> $cache
	 * @phpstan-ignore-var
	 */
	static $cache = [];
	assertType('array<int, \'x\'>', $cache);
	$cache[$i] = 'x';
}
