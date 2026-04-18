<?php declare(strict_types=1);

namespace Bug14281;

use function PHPStan\Testing\assertType;

/**
 * @template TElement
 * @implements \IteratorAggregate<array-key, TElement>
 */
abstract class Collection implements \IteratorAggregate, \Countable
{
	/** @var array<array-key, TElement> */
	protected array $elements = [];

	/** @param array<TElement> $elements */
	public function __construct(array $elements = [])
	{
		$this->elements = $elements;
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
	#[\Override]
	public function count(): int
	{
		return \count($this->elements);
	}

	/** @return \Traversable<TElement> */
	#[\Override]
	public function getIterator(): \Traversable
	{
		yield from $this->elements;
	}

	/** @param array<mixed> $options */
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

class CollectionTest
{
	public function testFromAssociative(): void
	{
		$data = [
			null,
			0,
			'some-string',
			new \stdClass(),
			['some' => 'value'],
		];

		$collection = (new TestCollection())->assignRecursive($data);

		assert(count($collection) === 5);
		assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $data);

		assertType('Bug14281\TestCollection<*NEVER*>', $collection);
		assertType('mixed', $collection->get(0));
		assertType('mixed', $collection->get(1));

		assert($data[0] === $collection->get(0));
		assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $data);

		assert($data[1] === $collection->get(1));
		assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $data);

		assert($data[2] === $collection->get(2));
		assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $data);

		assert($data[3] === $collection->get(3));
		assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $data);

		assert($data[4] === $collection->get(4));
		assertType("array{null, 0, 'some-string', stdClass, array{some: 'value'}}", $data);
	}
}
