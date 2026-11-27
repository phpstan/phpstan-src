<?php

namespace TemplateBoundsVariance;

use function PHPStan\Testing\assertType;

/** @template-contravariant T */
interface Consumer
{

	/** @param T $value */
	public function accept($value): void;

}

/**
 * @template T
 * @param callable(callable(T): void): void $a
 * @param T $b
 * @return T
 */
function nestedCallable(callable $a, $b)
{
	return $b;
}

/**
 * @template T
 * @param Consumer<T> $consumer
 * @param T[] $values
 * @return T
 */
function consumerAndValues(Consumer $consumer, array $values)
{
	return $values[0];
}

/**
 * @template T
 * @param Consumer<T> $a
 * @param Consumer<T> $b
 * @return T
 */
function twoConsumers(Consumer $a, Consumer $b)
{
	throw new \Exception();
}

/** @template T */
interface Box
{

}

/**
 * @template T
 * @param Box<contravariant T> $box
 * @param T[] $values
 * @return T
 */
function callSiteContravariant(Box $box, array $values)
{
	return $values[0];
}

/**
 * @param callable(callable(int): void): void $callsWithIntCallback
 * @param Consumer<int|string> $intOrStringConsumer
 * @param Consumer<int> $intConsumer
 * @param Box<int|string> $intOrStringBox
 */
function test(callable $callsWithIntCallback, Consumer $intOrStringConsumer, Consumer $intConsumer, Box $intOrStringBox): void
{
	// T in callable(callable(T): void) is covariant: T :> int and T :> 'a'
	assertType('\'a\'|int', nestedCallable($callsWithIntCallback, 'a'));

	// Consumer<T> is contravariant: T <: int|string, T :> 1
	assertType('1', consumerAndValues($intOrStringConsumer, [1]));
	assertType('int', twoConsumers($intConsumer, $intOrStringConsumer));
	assertType('int', twoConsumers($intOrStringConsumer, $intConsumer));

	assertType('1', callSiteContravariant($intOrStringBox, [1]));
}
