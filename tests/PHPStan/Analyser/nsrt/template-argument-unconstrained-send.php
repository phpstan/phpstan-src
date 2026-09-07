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
	assertType('TemplateArgumentUnconstrainedSend\Collection<mixed>', $collection);
}

function consumeMixed($value): void
{
}

/** @phpstan-pure */
function inspectMixed($value): void
{
}

function mixedConsumer(): void
{
	$collection = new Collection(null);
	consumeMixed($collection);
	assertType('TemplateArgumentUnconstrainedSend\Collection<mixed>', $collection);
}

function pureMixedConsumer(): void
{
	$collection = new Collection(null);
	inspectMixed($collection);
	assertType('TemplateArgumentUnconstrainedSend\Collection<mixed>', $collection);
}

/** @template ID of string|array<string, string> = string */
class Criteria
{

	/** @param array<ID>|null $ids */
	public function __construct(?array $ids = null)
	{
	}

}

abstract class Repository
{

	/**
	 * @template ID of string|array<string, string> = string
	 * @param Criteria<ID> $criteria
	 * @return list<ID>
	 */
	abstract public function searchIds(Criteria $criteria): array;

}

function defaultTemplateArgument(Repository $repository): void
{
	$criteria = new Criteria();
	$ids = $repository->searchIds($criteria);
	assertType('TemplateArgumentUnconstrainedSend\Criteria<string>', $criteria);
	assertType('list<string>', $ids);
}

function inferredTemplateArgumentOverridesDefault(Repository $repository): void
{
	$criteria = new Criteria([['id' => 'foo']]);
	$ids = $repository->searchIds($criteria);
	assertType("TemplateArgumentUnconstrainedSend\Criteria<array{id: 'foo'}>", $criteria);
	assertType("list<array{id: 'foo'}>", $ids);
}
