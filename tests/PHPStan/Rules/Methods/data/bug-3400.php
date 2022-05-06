<?php

namespace Bug3400;

interface Immutable {}

/**
 * @template T of Immutable
 */
class Collection
{
	/** @var T[] */
	protected array $values = [];

	/**
	 * @param T[] $values
	 */
	protected function __construct(array $values = [])
	{
		$this->values = $values;
	}

	/**
	 * @param class-string<U> $type
	 *
	 * @return Collection<U>
	 *
	 * @template U of Immutable
	 */
	public static function ofType(string $type) : self
	{
		return new self();
	}
}
