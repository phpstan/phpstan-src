<?php declare(strict_types = 1);

namespace ConditionalSelfOut;

use function PHPStan\Testing\assertType;

class Collection
{

	/**
	 * @phpstan-self-out ($size is 0 ? EmptyCollection : NonEmptyCollection)
	 */
	public function setSize(int $size): void
	{
	}

	/**
	 * @template TKey of int|string
	 * @param TKey $key
	 * @phpstan-self-out (TKey is int ? IntKeyed : StringKeyed)
	 */
	public function keyBy($key): void
	{
	}

}

class EmptyCollection extends Collection
{

}

class NonEmptyCollection extends Collection
{

}

class IntKeyed extends Collection
{

}

class StringKeyed extends Collection
{

}

function conditionalForParameter(Collection $zero, Collection $nonZero, Collection $unknown, int $i): void
{
	$zero->setSize(0);
	assertType(EmptyCollection::class, $zero);

	$nonZero->setSize(7);
	assertType(NonEmptyCollection::class, $nonZero);

	$unknown->setSize($i);
	assertType('ConditionalSelfOut\EmptyCollection|ConditionalSelfOut\NonEmptyCollection', $unknown);
}

function conditionalForTemplateType(Collection $int, Collection $string): void
{
	$int->keyBy(1);
	assertType(IntKeyed::class, $int);

	$string->keyBy('foo');
	assertType(StringKeyed::class, $string);
}
