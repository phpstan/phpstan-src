<?php

namespace Bug4715;

/**
 * @phpstan-template TKey
 * @phpstan-template T
 * @template-extends \IteratorAggregate<TKey, T>
 */
interface Collection extends \IteratorAggregate {}

/**
 * @phpstan-template TKey
 * @phpstan-template T
 * @template-implements Collection<TKey,T>
 */
class ArrayCollection implements Collection
{
	/**
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator([]);
	}
}

class Administration {}

class Company
{
	/**
	 * @var Collection<int, Administration>|Administration[]
	 */
	protected Collection $administrations;

	public function __construct()
	{
		$this->administrations = new ArrayCollection();
	}

	/**
	 * @return Collection<int, Administration>|Administration[]
	 */
	public function getAdministrations() : Collection
	{
		return $this->administrations;
	}
}
