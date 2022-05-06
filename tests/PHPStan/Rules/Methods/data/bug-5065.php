<?php declare(strict_types = 1);

namespace Bug5065;

/**
 * @template TKey of array-key
 * @template T
 */
class Collection
{
	/**
	 * @var callable(): iterable<TKey, T>
	 */
	private $source;

	/**
	 * @param callable(): iterable<TKey, T> $callable
	 */
	public function __construct(callable $callable)
	{
		$this->source = $callable;
	}

	/**
	 * @template NewTKey of array-key
	 * @template NewT
	 *
	 * @return self<NewTKey, NewT>
	 */
	public static function empty(): self
	{
		return new self(static fn(): iterable => []);
	}

	/**
	 * @template NewTKey of array-key
	 * @template NewT
	 *
	 * @return self<NewTKey, NewT>
	 */
	public static function emptyWorkaround(): self
	{
		/** @var array<NewTKey, NewT> $empty */
		$empty = [];

		return new self(static fn() => $empty);
	}

	/**
	 * @template NewTKey of array-key
	 * @template NewT
	 *
	 * @return self<NewTKey, NewT>
	 */
	public static function emptyWorkaround2(): self
	{
		/** @var Closure(): iterable<NewTKey, NewT> */
		$func = static fn(): iterable => [];

		return new self($func);
	}
}
