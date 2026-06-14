<?php declare(strict_types = 1);

namespace Bug7343;

use function PHPStan\Testing\assertType;

interface I
{
}

interface I2
{
}

abstract class HelloWorld
{

	public function sayHello(): void
	{
		/** @var I&static $a */
		$a = $this->obj();
		assertType('static(Bug7343\HelloWorld)&Bug7343\I', $a);

		/** @var static&I $b */
		$b = $this->obj();
		assertType('static(Bug7343\HelloWorld)&Bug7343\I', $b);

		/** @var $this&I2 $c */
		$c = $this->obj();
		assertType('$this(Bug7343\HelloWorld)&Bug7343\I2', $c);

		/** @var I2&$this $d */
		$d = $this->obj();
		assertType('$this(Bug7343\HelloWorld)&Bug7343\I2', $d);

		/** @var self&I $e */
		$e = $this->obj();
		assertType('Bug7343\HelloWorld&Bug7343\I', $e);

		/** @var I&self $f */
		$f = $this->obj();
		assertType('Bug7343\HelloWorld&Bug7343\I', $f);

		/** @var I2&self&I $g */
		$g = $this->obj();
		assertType('Bug7343\HelloWorld&Bug7343\I&Bug7343\I2', $g);

		/** @var \ArrayAccess<int, int>&self $h */
		$h = $this->obj();
		assertType('Bug7343\HelloWorld&ArrayAccess<int, int>', $h);
	}

	abstract public function obj(): object;

}
