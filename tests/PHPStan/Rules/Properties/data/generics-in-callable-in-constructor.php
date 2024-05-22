<?php

namespace GenericsInCallableInConstructor;

interface Node
{

}

/**
 * @template T
 */
class Differ
{

	/**
	 * Create differ over the given equality relation.
	 *
	 * @param callable(T, T): bool $isEqual Equality relation
	 */
	public function __construct(callable $isEqual)
	{
	}

}

class Foo
{

	/** @var Differ<Node> */
	private $differ;

	public function doFoo(): void
	{
		$this->differ = new Differ(static function ($a, $b) {
			return false;
		});
	}

}
