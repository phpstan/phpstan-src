<?php

namespace Bug1216PropertyTest;

use AllowDynamicProperties;

abstract class Foo
{
	/**
	 * @var int
	 */
	protected $foo;
}

trait Bar
{
	/**
	 * @var int
	 */
	protected $bar;

	protected $untypedBar;
}

/**
 * @property string $foo
 * @property string $bar
 * @property string $untypedBar
 */
#[AllowDynamicProperties]
class Baz extends Foo
{

	public function __construct()
	{
		$this->foo = 'foo'; // OK
		$this->bar = 'bar'; // OK
		$this->untypedBar = 123; // error
	}

}

trait DecoratorTrait
{

	/** @var \stdClass */
	public $foo;

}

/**
 * @property \Exception $foo
 */
class Dummy
{

	use DecoratorTrait;

}

function (Dummy $dummy): void {
	$dummy->foo = new \stdClass();
};

function (Dummy $dummy): void {
	$dummy->foo = new \Exception();
};
