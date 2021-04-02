<?php

namespace Bug3546;

interface MyInterface{}
class MyClass implements MyInterface{}

/** @phpstan-template T of MyInterface */
interface SomeInterface{}

/**
 * @phpstan-template T of MyInterface
 */
abstract class MyAbstractService
{
	/** @phpstan-var SomeInterface<T> */
	private $someInterface;

	/**
	 * @phpstan-param SomeInterface<T> $someInterface
	 */
	public function __construct(SomeInterface $someInterface)
	{
		$this->someInterface = $someInterface;
	}
}

/**
 * @phpstan-extends MyAbstractService<MyClass>
 */
class MyService extends MyAbstractService
{
	/**
	 * @phpstan-param SomeInterface<MyClass> $someInterface
	 */
	public function __construct(SomeInterface $someInterface)
	{
		parent::__construct($someInterface);
	}
}
