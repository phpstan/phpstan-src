<?php

namespace Bug4854;

/**
 * @extends \IteratorAggregate<int>
 * @extends \ArrayAccess<int,int>
 */
interface DomainsAvailabilityInterface extends \IteratorAggregate, \ArrayAccess
{
	public const AVAILABLE = 1;
	public const UNAVAILABLE = 2;
	public const UNKNOWN = 3;
}

abstract class AbstractDomainsAvailability implements DomainsAvailabilityInterface
{
	/**
	 * @var int[]
	 */
	protected array $domains;

	/**
	 * {@inheritdoc}
	 */
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->domains);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $value): void
	{
		if ($offset === null) {
			$this->domains[] = $value;
		} else {
			$this->domains[$offset] = $value;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->domains[$offset]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset): void
	{
		unset($this->domains[$offset]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function offsetGet($offset): int
	{
		return $this->domains[$offset];
	}
}
