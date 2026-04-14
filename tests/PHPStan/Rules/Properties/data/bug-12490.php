<?php declare(strict_types = 1);

namespace Bug12490Property;

/**
 * @template T
 */
class Container
{
	/** @var T */
	public $value;

	/**
	 * @param T $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}
}

class Foo
{
	/** @var Container<string|null> */
	public Container $stringContainer;

	/** @var Container<int|null> */
	public Container $intContainer;

	/**
	 * @param Container<string|null> $s
	 * @param Container<int|null> $i
	 */
	public function test(Container $s, Container $i): void
	{
		$this->stringContainer = $s;
		$this->intContainer = $i;
	}
}
