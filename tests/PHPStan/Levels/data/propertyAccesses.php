<?php

namespace Levels\PropertyAccesses;

class Foo
{

	/** @var self */
	public $foo;

	public function doFoo(int $i)
	{
		$foo = $this->foo;
		var_dump($foo);
		echo $this->bar;

		$foo = new self();
		$foo = $foo->foo;
		echo $foo->bar;
	}

}

class Bar
{

	/** @var self */
	public static $bar;

	public static function doBar(int $i)
	{
		$bar = Bar::$bar;
		var_dump($bar);
		echo Lorem::$bar;

		$bar = new Bar();
		$bar = $bar::$bar;
		echo $bar::$foo;
	}

}

class Baz
{

	/**
	 * @param Foo|Bar $fooOrBar
	 * @param Foo|null $fooOrNull
	 * @param Foo|Bar|null $fooOrBarOrNull
	 * @param Bar|Baz $barOrBaz
	 */
	public function doBaz(
		$fooOrBar,
		?Foo $fooOrNull,
		$fooOrBarOrNull,
		$barOrBaz
	)
	{
		$foo = $fooOrBar->foo;
		$bar =$fooOrBar->bar;
		var_dump($foo, $bar);

		$foo = $fooOrNull->foo;
		$bar = $fooOrNull->bar;
		var_dump($foo, $bar);

		$foo = $fooOrBarOrNull->foo;
		$bar = $fooOrBarOrNull->bar;
		var_dump($foo, $bar);

		$foo = $barOrBaz->foo;
		var_dump($foo);
	}

}

class ClassWithMagicMethod
{

	public function doFoo()
	{
		$this->test = 'test';
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set(string $name, $value)
	{

	}

}

class AnotherClassWithMagicMethod
{

	public function doFoo()
	{
		echo $this->test;
	}

	public function __get(string $name)
	{

	}

}

class Ipsum
{

	/**
	 * @return Foo|Bar
	 */
	private function makeFooOrBar()
	{
		if (rand(0, 1) === 0) {
			return new Foo();
		} else {
			return new Bar();
		}
	}

	/**
	 * @return Foo|null
	 */
	private function makeFooOrNull()
	{
		if (rand(0, 1) === 0) {
			return new Foo();
		} else {
			return null;
		}
	}

	/**
	 * @return Foo|Bar|null
	 */
	public function makeFooOrBarOrNull()
	{
		if (rand(0, 1) === 0) {
			return new Foo();
		} elseif (rand(0, 1) === 1) {
			return new Bar();
		} else {
			return null;
		}
	}

	/**
	 * @return Bar|Baz
	 */
	public function makeBarOrBaz()
	{
		if (rand(0, 1) === 0) {
			return new Bar();
		} else {
			return new Baz();
		}
	}

	public function doBaz()
	{
		$fooOrBar = $this->makeFooOrBar();
		$foo = $fooOrBar->foo;
		$bar =$fooOrBar->bar;
		var_dump($foo, $bar);

		$fooOrNull = $this->makeFooOrNull();
		$foo = $fooOrNull->foo;
		$bar = $fooOrNull->bar;
		var_dump($foo, $bar);

		$fooOrBarOrNull = $this->makeFooOrBarOrNull();
		$foo = $fooOrBarOrNull->foo;
		$bar = $fooOrBarOrNull->bar;
		var_dump($foo, $bar);

		$barOrBaz = $this->makeBarOrBaz();
		$foo = $barOrBaz->foo;
		var_dump($foo);
	}

}

class ObjectWithIsset
{

	public function doFoo(): void
	{
		$test = new \stdClass;

		if (isset($test->foo)) {
			echo $test->foo;
			echo $test->bar;
			echo $test->baz;
		}
	}

	/**
	 * @param mixed $test
	 */
	public function doBar($test): void
	{
		if (isset($test->foo) && isset($test->bar)) {
			echo $test->foo;
			echo $test->bar;
			echo $test->baz;
		}
	}

}
