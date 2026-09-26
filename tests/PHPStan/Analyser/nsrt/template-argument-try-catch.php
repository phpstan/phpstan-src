<?php declare(strict_types = 1);

namespace TemplateArgumentTryCatch;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param list<T> $items */
	public function __construct(array $items)
	{
	}

}

/** @param Collection<int> $collection */
function takeInts(Collection $collection): void
{
}

function mayThrow(): void
{
}

function catchThatThrows(): void
{
	try {
		mayThrow();
	} catch (\Exception $e) {
		$collection = new Collection([1]);
		takeInts($collection);
		assertType('TemplateArgumentTryCatch\Collection<int>', $collection);
		throw $e;
	}
}

function catchThatReturns(): void
{
	try {
		mayThrow();
	} catch (\Exception $e) {
		$collection = new Collection([1]);
		takeInts($collection);
		assertType('TemplateArgumentTryCatch\Collection<int>', $collection);
		return;
	}
}

function finallyBlock(): void
{
	try {
		mayThrow();
	} finally {
		$collection = new Collection([1]);
		takeInts($collection);
		assertType('TemplateArgumentTryCatch\Collection<int>', $collection);
	}
}

function tryThatThrows(): void
{
	try {
		$collection = new Collection([1]);
		takeInts($collection);
		assertType('TemplateArgumentTryCatch\Collection<int>', $collection);
		throw new \Exception();
	} catch (\Exception $e) {
	}
}
