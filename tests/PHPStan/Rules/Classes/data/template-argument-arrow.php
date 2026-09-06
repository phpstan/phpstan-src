<?php declare(strict_types = 1);

namespace TemplateArgumentArrow;

/**
 * @template TKey of array-key
 * @template TValue
 */
interface ReadableCollection
{
}

/**
 * @template TKey of array-key
 * @template TValue
 * @implements ReadableCollection<TKey, TValue>
 */
class ArrayCollection implements ReadableCollection
{
	/** @param array<TKey, TValue> $values */
	public function __construct(array $values)
	{
	}
}

/** @template TValue */
class ReadOnlyCollection
{
	/** @param ReadableCollection<int, TValue> $collection */
	public function __construct(ReadableCollection $collection)
	{
	}
}

function acceptCallback(callable $callback): void
{
}

/** @param list<object> $addresses */
function callback(array $addresses): void
{
	acceptCallback(static fn (string $method) => match ($method) {
		'getAddresses' => new ReadOnlyCollection(new ArrayCollection($addresses)),
		'getDirectAddresses' => new ReadOnlyCollection(new ArrayCollection([])),
		default => null,
	});
}
