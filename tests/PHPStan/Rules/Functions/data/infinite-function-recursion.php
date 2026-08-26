<?php declare(strict_types = 1); // lint >= 8.1

namespace InfiniteFunctionRecursion;

function getWorld(): string
{
	return getWorld();
}

function withSideEffect(): string
{
	echo 'x';

	return withSideEffect();
}

function concat(): string
{
	return concat() . 'x';
}

function insideArgument(): string
{
	return strtoupper(insideArgument());
}

function withBaseCase(int $i): int
{
	if ($i <= 0) {
		return 0;
	}

	return withBaseCase($i - 1);
}

function ternaryBaseCase(int $i): int
{
	return $i <= 0 ? 0 : ternaryBaseCase($i - 1);
}

function callsOther(): string
{
	return getWorld();
}

function firstClassCallable(): callable
{
	return firstClassCallable(...);
}

/**
 * @return \Generator<int, string>
 */
function generator(): \Generator
{
	yield getWorld();
}
