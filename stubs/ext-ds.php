<?php

namespace Ds;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use OutOfRangeException;
use Traversable;
use UnderflowException;

/**
 * @template-covariant TValue
 * @extends Traversable<mixed, TValue>
 */
interface Collection extends Traversable, Countable, JsonSerializable
{
	/**
	 * @return Collection<TValue>
	 */
	public function copy(): Collection;

	/**
	 * @return array<TValue>
	 */
	public function toArray(): array;
}

/**
 * @template TValue
 * @implements Sequence<TValue>
 * @implements ArrayAccess<int, TValue>
 * @implements IteratorAggregate<int, TValue>
 */
final class Deque implements IteratorAggregate, ArrayAccess, Sequence
{
	/**
	 * @return Deque<TValue>
	 */
	public function copy(): Deque
	{
	}

	/**
	 * @param iterable<TValue>|null $values
	 */
	public function __construct($values = null)
	{
	}

	/**
	 * @param iterable<TValue> $values
	 * @return Deque<TValue>
	 */
	public function merge($values): Deque
	{
	}

	/**
	 * @param (callable(TValue): bool)|null $callback
	 * @return Deque<TValue>
	 */
	public function filter(callable $callback = null): Deque
	{
	}

	/**
	 * @template TNewValue
	 * @param callable(TValue): TNewValue $callback
	 * @return Deque<TNewValue>
	 */
	public function map(callable $callback): Deque
	{
	}

	/**
	 * @return Deque<TValue>
	 */
	public function reversed(): Deque
	{
	}

	/**
	 * @return Deque<TValue>
	 */
	public function slice(int $offset, ?int $length = null): Deque
	{
	}
}

/**
 * @template TKey
 * @template TValue
 * @implements Collection<TValue>
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
final class Map implements IteratorAggregate, ArrayAccess, Collection
{
	/**
	 * @return Map<TKey, TValue>
	 */
	public function copy(): Map
	{
	}

	/**
	 * @param iterable<TKey, TValue>|null $values
	 */
	public function __construct($values = null)
	{
	}

	/**
	 * @param callable(TKey, TValue): TValue $callback
	 */
	public function apply(callable $callback)
	{
	}

	/**
	 * @return Pair<TKey, TValue>
	 * @throws UnderflowException
	 */
	public function first(): Pair
	{
	}

	/**
	 * @return Pair<TKey, TValue>
	 * @throws UnderflowException
	 */
	public function last(): Pair
	{
	}

	/**
	 * @return Pair<TKey, TValue>
	 * @throws OutOfRangeException
	 */
	public function skip(int $position): Pair
	{
	}

	/**
	 * @param iterable<TKey, TValue> $values
	 * @return Map<TKey, TValue>
	 */
	public function merge($values): Map
	{
	}

	/**
	 * @param Map<TKey, TValue> $map
	 * @return Map<TKey, TValue>
	 */
	public function intersect(Map $map): Map
	{
	}

	/**
	 * @param Map<TKey, TValue> $map
	 * @return Map<TKey, TValue>
	 */
	public function diff(Map $map): Map
	{
	}

	/**
	 * @param TKey $key
	 */
	public function hasKey($key): bool
	{
	}

	/**
	 * @param TValue $value
	 */
	public function hasValue($value): bool
	{
	}

	/**
	 * @param (callable(TKey, TValue): bool)|null $callback
	 * @return Map<TKey, TValue>
	 */
	public function filter(callable $callback = null): Map
	{
	}

	/**
	 * @template TDefault
	 * @param TKey $key
	 * @param TDefault $default
	 * @return TValue|TDefault
	 * @throws OutOfBoundsException
	 */
	public function get($key, $default = null)
	{
	}

	/**
	 * @return Set<TKey>
	 */
	public function keys(): Set
	{
	}

	/**
	 * @template TNewValue
	 * @param callable(TKey, TValue): TNewValue $callback
	 * @return Map<TKey, TNewValue>
	 */
	public function map(callable $callback): Map
	{
	}

	/**
	 * @return Sequence<Pair<TKey, TValue>>
	 */
	public function pairs(): Sequence
	{
	}

	/**
	 * @param TKey $key
	 * @param TValue $value
	 */
	public function put($key, $value)
	{
	}

	/**
	 * @param iterable<TKey, TValue> $values
	 */
	public function putAll($values)
	{
	}

	/**
	 * @template TCarry
	 * @param callable(TCarry, TKey, TValue): TCarry $callback
	 * @param TCarry $initial
	 * @return TCarry
	 */
	public function reduce(callable $callback, $initial = null)
	{
	}

	/**
	 * @template TDefault
	 * @param TKey $key
	 * @param TDefault $default
	 * @return TValue|TDefault
	 * @throws \OutOfBoundsException
	 */
	public function remove($key, $default = null)
	{
	}

	/**
	 * @return Map<TKey, TValue>
	 */
	public function reversed(): Map
	{
	}

	/**
	 * @return Map<TKey, TValue>
	 */
	public function slice(int $offset, ?int $length = null): Map
	{
	}

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 */
	public function sort(callable $comparator = null)
	{
	}

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 * @return Map<TKey, TValue>
	 */
	public function sorted(callable $comparator = null): Map
	{
	}

	/**
	 * @param (callable(TKey, TKey): int)|null $comparator
	 */
	public function ksort(callable $comparator = null)
	{
	}

	/**
	 * @param (callable(TKey, TKey): int)|null $comparator
	 * @return Map<TKey, TValue>
	 */
	public function ksorted(callable $comparator = null): Map
	{
	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function toArray(): array
	{
	}

	/**
	 * @return Sequence<TValue>
	 */
	public function values(): Sequence
	{
	}

	/**
	 * @param Map<TKey, TValue> $map
	 * @return Map<TKey, TValue>
	 */
	public function union(Map $map): Map
	{
	}

	/**
	 * @param Map<TKey, TValue> $map
	 * @return Map<TKey, TValue>
	 */
	public function xor(Map $map): Map
	{
	}
}

/**
 * @template-covariant TKey
 * @template-covariant TValue
 */
final class Pair implements JsonSerializable
{
	/**
	 * @var TKey
	 */
	public $key;

	/**
	 * @var TValue
	 */
	public $value;

	/**
	 * @param TKey $key
	 * @param TValue $value
	 */
	public function __construct($key = null, $value = null)
	{
	}

	/**
	 * @return Pair<TKey, TValue>
	 */
	public function copy(): Pair
	{
	}
}

/**
 * @template TValue
 * @extends Collection<TValue>
 */
interface Sequence extends Collection
{
	/**
	 * @param callable(TValue): TValue $callback
	 */
	public function apply(callable $callback);

	/**
	 * @param TValue ...$values
	 */
	public function contains(...$values): bool;

	/**
	 * @param (callable(TValue): bool)|null $callback
	 * @return Sequence<TValue>
	 */
	public function filter(callable $callback = null): Sequence;

	/**
	 * @param TValue $value
	 * @return int|false
	 */
	public function find($value);

	/**
	 * @return TValue
	 * @throws \UnderflowException
	 */
	public function first();

	/**
	 * @return TValue
	 * @throws \OutOfRangeException
	 */
	public function get(int $index);

	/**
	 * @param TValue ...$values
	 * @throws \OutOfRangeException
	 */
	public function insert(int $index, ...$values);

	/**
	 * @param string $glue
	 * @return string
	 */
	public function join(string $glue = null): string;

	/**
	 * @return TValue
	 * @throws \UnderflowException
	 */
	public function last();

	/**
	 * @template TNewValue
	 * @param callable(TValue): TNewValue $callback
	 * @return Sequence<TNewValue>
	 */
	public function map(callable $callback): Sequence;

	/**
	 * @param iterable<TValue> $values
	 * @return Sequence<TValue>
	 */
	public function merge($values): Sequence;

	/**
	 * @return TValue
	 * @throws \UnderflowException
	 */
	public function pop();

	/**
	 * @param TValue ...$values
	 */
	public function push(...$values);

	/**
	 * @template TCarry
	 * @param callable(TCarry, TValue): TCarry $callback
	 * @param TCarry $initial
	 * @return TCarry
	 */
	public function reduce(callable $callback, $initial = null);

	/**
	 * @return TValue
	 * @throws \OutOfRangeException
	 */
	public function remove(int $index);

	/**
	 * @return Sequence<TValue>
	 */
	public function reversed(): Sequence;

	/**
	 * @param TValue $value
	 * @throws \OutOfRangeException
	 */
	public function set(int $index, $value);

	/**
	 * @return TValue
	 * @throws \UnderflowException
	 */
	public function shift();

	/**
	 * @return Sequence<TValue>
	 */
	public function slice(int $index, ?int $length = null): Sequence;

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 */
	public function sort(callable $comparator = null);

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 * @return Sequence<TValue>
	 */
	public function sorted(callable $comparator = null): Sequence;

	/**
	 * @param TValue ...$values
	 */
	public function unshift(...$values);
}


/**
 * @template TValue
 * @implements Sequence<TValue>
 * @implements ArrayAccess<int, TValue>
 * @implements IteratorAggregate<int, TValue>
 */
final class Vector implements IteratorAggregate, ArrayAccess, Sequence
{
	/**
	 * @return Vector<TValue>
	 */
	public function copy(): Vector
	{
	}

	/**
	 * @param iterable<TValue>|null $values
	 */
	public function __construct($values = null)
	{
	}

	/**
	 * @return Vector<TValue>
	 */
	public function reversed(): Vector
	{
	}

	/**
	 * @return Vector<TValue>
	 */
	public function slice(int $offset, ?int $length = null): Sequence
	{
	}

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 * @return Vector<TValue>
	 */
	public function sorted(callable $comparator = null): Vector
	{
	}
	
	/**
	 * @param (callable(TValue): bool)|null $callback
	 * @return Vector<TValue>
	 */
	public function filter(callable $callback = null): Vector
	{
	}
}

/**
 * @template TValue
 * @implements Collection<TValue>
 * @implements ArrayAccess<int, TValue>
 * @implements Traversable<int, TValue>
 */
final class Set implements ArrayAccess, Collection, Traversable
{
	/**
	 * @param TValue ...$values
	 */
	public function add(...$values): void
	{
	}

	/**
	 * @param iterable<TValue>|null $values
	 */
	public function __construct($values = null)
	{
	}

	/**
	 * @param TValue ...$values
	 */
	public function contains(...$values): bool
	{
	}

	/**
	 * @return Set<TValue>
	 */
	public function copy(): Set
	{
	}

	/**
	 * @param Set<TValue> $set
	 * @return Set<TValue>
	 */
	public function diff(Set $set): Set
	{
	}

	/**
	 * @param (callable(TValue): bool)|null $callback
	 * @return Set<TValue>
	 */
	public function filter(callable $callback = null): Set
	{
	}

	/**
	 * @return TValue
	 * @throws \UnderflowException
	 */
	public function first()
	{
	}

	/**
	 * @return TValue
	 * @throws \OutOfRangeException
	 */
	public function get(int $index)
	{
	}

	/**
	 * @param Set<TValue> $set
	 * @return Set<TValue>
	 */
	public function intersect(Set $set): Set
	{
	}

	/**
	 * @return TValue
	 * @throws \UnderflowException
	 */
	public function last()
	{
	}

	/**
	 * @param iterable<TValue> $values
	 * @return Set<TValue>
	 */
	public function merge($values): Set
	{
	}

	/**
	 * @param TValue ...$values
	 */
	public function remove(...$values): void
	{
	}

	/**
	 * @return Set<TValue>
	 */
	public function reversed(): Set
	{
	}

	/**
	 * @return Set<TValue>
	 */
	public function slice(int $index, ?int $length = null): Set
	{
	}

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 */
	public function sort(callable $comparator = null): void
	{
	}

	/**
	 * @param (callable(TValue, TValue): int)|null $comparator
	 * @return Set<TValue>
	 */
	public function sorted(callable $comparator = null): Set
	{
	}

	/**
	 * @return array<TValue>
	 */
	public function toArray(): array
	{
	}

	/**
	 * @param Set<TValue> $set
	 * @return Set<TValue>
	 */
	public function union(Set $set): Set
	{
	}

	/**
	 * @param Set<TValue> $set
	 * @return Set<TValue>
	 */
	public function xor(Set $set): Set
	{
	}
}
