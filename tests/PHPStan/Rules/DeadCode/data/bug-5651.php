<?php

namespace Bug5651;

#[\Attribute]
class MyAttribute
{

	/** @var string[] */
	public $values;

	/**
	 * @param string[] $values
	 */
	public function __construct(array $values)
	{
		$this->values = $values;
	}
}

class HelloWorld
{
	private const BAR = 'bar';

	#[MyAttribute(['foo' => self::BAR])]
	public function sayHello(): void
	{

	}
}
