<?php declare(strict_types = 1);

namespace TemplateArgumentUnconstrainedSend;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param T|null $value */
	public function __construct($value = null)
	{
	}

}

/**
 * @template T
 * @param Collection<T|null> $collection
 * @return T
 */
function read(Collection $collection)
{
}

/** @param Collection<int> $collection */
function takeInts(Collection $collection): void
{
}

function unconstrainedConsumer(): void
{
	$collection = new Collection(null);
	assertType('mixed', read($collection));
	assertType('TemplateArgumentUnconstrainedSend\Collection<mixed>', $collection);
}

function concreteConsumer(): void
{
	$collection = new Collection(null);
	assertType('int', read($collection));
	takeInts($collection);
	assertType('TemplateArgumentUnconstrainedSend\Collection<int>', $collection);
}

function untouchedCollection(): void
{
	$collection = new Collection(null);
	assertType('TemplateArgumentUnconstrainedSend\Collection<*NEVER*>', $collection);
}
