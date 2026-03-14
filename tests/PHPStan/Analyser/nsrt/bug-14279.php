<?php declare(strict_types=1);

namespace Bug14279;

use function PHPStan\Testing\assertType;

/**
 * @template TElement
 * @implements \IteratorAggregate<array-key, TElement>
 */
abstract class Collection implements \IteratorAggregate, \Countable
{
	/** @var array<array-key, TElement> */
	protected array $elements = [];

	/** @param iterable<TElement> $elements */
	public function __construct(iterable $elements = [])
	{
	}

	/**
	 * @param array-key $key
	 * @return TElement|null
	 */
	public function get($key)
	{
		return $this->elements[$key] ?? null;
	}

	/** @phpstan-impure */
	public function count(): int
	{
		return \count($this->elements);
	}

	/** @return \Traversable<TElement> */
	public function getIterator(): \Traversable
	{
		yield from $this->elements;
	}

	public function assignRecursive(array $options): static
	{
		return $this;
	}
}

/**
 * @template TElement
 * @extends Collection<TElement>
 */
class TestCollection extends Collection
{
}

function test(): void
{
	$data = [
		null,
		0,
		'some-string',
	];

	$collection = (new TestCollection())->assignRecursive($data);

	// assertSame($data[0], $collection->get(0)) narrows $data[0] via Identical
	// $collection->get(0) returns null (TElement=*NEVER*)
	// intersect(null, null) = null - no problem here
	assert($data[0] === $collection->get(0));
	assertType('null', $data[0]);
	assertType("'some-string'", $data[2]);

	// assertSame($data[1], $collection->get(1)) narrows $data[1] via Identical
	// $collection->get(1) returns null, $data[1] is 0
	// intersect(0, null) = *NEVER* - this must not poison the parent array $data
	assert($data[1] === $collection->get(1));
	assertType("'some-string'", $data[2]);
}
