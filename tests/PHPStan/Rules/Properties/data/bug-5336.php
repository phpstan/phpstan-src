<?php

namespace Bug5336Rule;

interface Stub
{
}

interface ProxyQueryInterface
{
}

/**
 * @phpstan-template T of ProxyQueryInterface
 */
class Pager
{
	/**
	 * @var T
	 */
	private $query;

	/**
	 * @phpstan-param T $query
	 */
	public function __construct(ProxyQueryInterface $query) {
		$this->query = $query;
	}
}

abstract class Test
{
	/**
	 * @var Pager<ProxyQueryInterface&Stub>
	 */
	private $pager;

	/**
	 * @template T of object
	 * @param class-string<T> $originalClassName
	 * @return T&Stub
	 */
	abstract public function createStub(string $originalClassName): Stub;

	public function sayHello(): void
	{
		$query = $this->createStub(ProxyQueryInterface::class);
		$this->pager = new Pager($query);
	}
}
