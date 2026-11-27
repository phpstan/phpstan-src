<?php // lint >= 8.0

namespace Bug15796;

use IteratorAggregate;
use Traversable;

/**
 * @template T
 * @implements IteratorAggregate<int, T>
 * @phpstan-consistent-constructor
 */
class Items implements IteratorAggregate
{
	/** @param list<T> $values */
	public function __construct(private array $values) {}

	/** @return Traversable<int, T> */
	#[\Override]
	public function getIterator(): Traversable
	{
		yield from $this->values;
	}

	/**
	 * @template U
	 * @param list<U> $values
	 * @return static<U>
	 */
	public function make(array $values): static
	{
		return new static($values);
	}

	/** @return static<string> */
	public function names(): static
	{
		/** @var static<string> $names */
		$names = $this->make(['Alice']);
		return $names;
	}

	/**
	 * @template U
	 * @param list<U> $values
	 * @return self<U>
	 */
	public function makeSelf(array $values): self
	{
		return new self($values);
	}

	/** @return self<string> */
	public function selfNames(): self
	{
		/** @var self<string> $names */
		$names = $this->makeSelf(['Alice']);
		return $names;
	}

	public function iterate(): void
	{
		/** @var string $name */
		foreach ($this->make(['Alice']) as $name) {
			echo $name;
		}
	}

	public function mismatch(): void
	{
		/** @var Other<string> $names */
		$names = $this->make(['Alice']);
	}
}

/**
 * @template T
 * @implements IteratorAggregate<int, T>
 */
final class Other implements IteratorAggregate
{
	/** @return Traversable<int, T> */
	public function getIterator(): Traversable
	{
		yield from [];
	}
}

/**
 * @template T of int
 * @implements IteratorAggregate<int, T>
 */
final class Ints implements IteratorAggregate
{
	/** @param list<T> $values */
	public function __construct(private array $values) {}

	/** @return Traversable<int, T> */
	public function getIterator(): Traversable
	{
		yield from $this->values;
	}

	public function iterate(): void
	{
		/** @var string $value */
		foreach ($this as $value) {
			echo $value;
		}
	}
}
