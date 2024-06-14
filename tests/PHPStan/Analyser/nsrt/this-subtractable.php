<?php

namespace ThisSubtractable;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo()
	{
		assertType('$this(ThisSubtractable\Foo)', $this);

		if (!$this instanceof Bar && !$this instanceof Baz) {
			assertType('$this(ThisSubtractable\Foo~ThisSubtractable\Bar|ThisSubtractable\Baz)', $this);
		} else {
			assertType('($this(ThisSubtractable\Foo)&ThisSubtractable\Bar)|($this(ThisSubtractable\Foo)&ThisSubtractable\Baz)', $this);
		}

		assertType('$this(ThisSubtractable\Foo)', $this);
	}

	public function doBar()
	{
		$s = $this->returnStatic();
		assertType('static(ThisSubtractable\Foo)', $s);

		if (!$s instanceof Bar && !$s instanceof Baz) {
			assertType('static(ThisSubtractable\Foo~ThisSubtractable\Bar|ThisSubtractable\Baz)', $s);
		} else {
			assertType('(static(ThisSubtractable\Foo)&ThisSubtractable\Bar)|(static(ThisSubtractable\Foo)&ThisSubtractable\Baz)', $s);
		}

		assertType('static(ThisSubtractable\Foo)', $s);
	}

	public function doBaz(self $s)
	{
		assertType('ThisSubtractable\Foo', $s);

		if (!$s instanceof Lorem && !$s instanceof Ipsum) {
			assertType('ThisSubtractable\Foo', $s);
		} else {
			assertType('(ThisSubtractable\Foo&ThisSubtractable\Ipsum)|(ThisSubtractable\Foo&ThisSubtractable\Lorem)', $s);
		}

		assertType('ThisSubtractable\Foo', $s);
	}

	public function doBazz(self $s)
	{
		assertType('ThisSubtractable\Foo', $s);

		if (!$s instanceof Bar && !$s instanceof Baz) {
			assertType('ThisSubtractable\Foo~ThisSubtractable\Bar|ThisSubtractable\Baz', $s);
		} else {
			assertType('ThisSubtractable\Bar|ThisSubtractable\Baz', $s);
		}

		assertType('ThisSubtractable\Foo', $s);
	}

	public function doBazzz(self $s)
	{
		assertType('ThisSubtractable\Foo', $s);
		if (!method_exists($s, 'test123', $s)) {
			return;
		}

		assertType('ThisSubtractable\Foo&hasMethod(test123)', $s);

		if (!$s instanceof Bar && !$s instanceof Baz) {
			assertType('ThisSubtractable\Foo~ThisSubtractable\Bar|ThisSubtractable\Baz&hasMethod(test123)', $s);
		} else {
			assertType('(ThisSubtractable\Bar&hasMethod(test123))|(ThisSubtractable\Baz&hasMethod(test123))', $s);
		}

		assertType('(ThisSubtractable\Bar&hasMethod(test123))|(ThisSubtractable\Baz&hasMethod(test123))|(ThisSubtractable\Foo~ThisSubtractable\Bar|ThisSubtractable\Baz&hasMethod(test123))', $s);
	}

	/**
	 * @return static
	 */
	public function returnStatic()
	{
		return $this;
	}

}

class Bar extends Foo
{

}

class Baz extends Foo
{

}

interface Lorem
{

}

interface Ipsum
{

}
