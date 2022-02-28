<?php

namespace Bug6230;

class Foo
{

	/**
	 * @param ?iterable<mixed> $it
	 * @return ?iterable<mixed>
	 */
	function test($it): ?iterable
	{
		return $it;
	}

}

/**
 * @template T
 */
class Example
{
	/**
	 * @var ?iterable<T>
	 */
	private $input;


	/**
	 * @param iterable<T> $input
	 */
	public function __construct(iterable $input)
	{
		$this->input = $input;
	}

	/** @return ?iterable<T> */
	public function get(): ?iterable
	{
		return $this->input;
	}
}
