<?php

namespace Bug14893;

/**
 * @template T of object
 */
class HelloWorld
{
	/**
	 * @param int $offset
	 * @param \Closure(static): T $value
	 */
	public function offsetSet($offset, \Closure $value): void
	{
	}

	/**
	 * @param (\Closure(static): T)|(\Closure&T) $value
	 */
	public function foo($value): void
	{
		$this->offsetSet(0, $value);
	}

	/**
	 * @param (\Closure(static): T)|(\Closure&T) $value
	 * @return \Closure(static): T
	 */
	public function bar($value): \Closure
	{
		return $value;
	}
}

/**
 * @param HelloWorld<\DateTimeInterface> $h
 */
function callWithMatchingClosure(HelloWorld $h): void
{
	// Closure returning the concrete T is a genuine subtype - accepted.
	$h->offsetSet(0, static fn ($self): \DateTimeInterface => new \DateTime());
}

/**
 * @param HelloWorld<\DateTimeInterface> $h
 */
function callWithMismatchedClosure(HelloWorld $h): void
{
	// Closure returning an unrelated type is not a subtype - rejected.
	$h->offsetSet(0, static fn ($self): \stdClass => new \stdClass());
}
