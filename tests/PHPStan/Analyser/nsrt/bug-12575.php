<?php

namespace Bug12575;

use function PHPStan\Testing\assertType;

/**
 * @template T of object
 */
class Foo
{

	/**
	 * @template U of object
	 * @param class-string<U> $class
	 * @return $this
	 * @phpstan-self-out static<T&U>
	 */
	public function add(string $class)
	{
		return $this;
	}

}

/**
 * @template T of object
 * @extends Foo<T>
 */
class Bar extends Foo
{

	public function doFoo(): void
	{
		assertType('$this(Bug12575\Bar<T of object (class Bug12575\Bar, argument)>)&static(Bug12575\Bar<Bug12575\A&T of object (class Bug12575\Bar, argument)>)', $this->add(A::class));
		assertType('$this(Bug12575\Bar<T of object (class Bug12575\Bar, argument)>)&static(Bug12575\Bar<Bug12575\A&T of object (class Bug12575\Bar, argument)>)', $this);
		assertType('T of object (class Bug12575\Bar, parameter)', $this->getT());
	}

	public function doBar(): void
	{
		$this->add(B::class);
		assertType('$this(Bug12575\Bar<T of object (class Bug12575\Bar, argument)>)&static(Bug12575\Bar<Bug12575\B&T of object (class Bug12575\Bar, argument)>)', $this);
		assertType('T of object (class Bug12575\Bar, parameter)', $this->getT());
	}

	/**
	 * @return T
	 */
	public function getT()
	{

	}

}

interface A
{

}

interface B
{

}

/**
 * @param Bar<A> $bar
 * @return void
 */
function doFoo(Bar $bar): void {
	assertType('Bug12575\\Bar<Bug12575\\A&Bug12575\\B>', $bar->add(B::class));
	assertType('Bug12575\\Bar<Bug12575\\A&Bug12575\\B>', $bar);
	assertType('Bug12575\A&Bug12575\B', $bar->getT());
};

/**
 * @param Bar<A> $bar
 * @return void
 */
function doBar(Bar $bar): void {
	$bar->add(B::class);
	assertType('Bug12575\\Bar<Bug12575\\A&Bug12575\\B>', $bar);
	assertType('Bug12575\A&Bug12575\B', $bar->getT());
};
