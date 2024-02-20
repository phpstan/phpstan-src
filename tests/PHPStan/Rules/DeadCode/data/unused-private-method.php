<?php

namespace UnusedPrivateMethod;

class Foo
{

	private function doFoo()
	{
		$this->doFoo();
	}

	private function doBar()
	{
		$this->doBaz();
	}

	private function doBaz()
	{
		self::calledStatically();
	}

	private function calledStatically()
	{

	}

	private function __construct()
	{
		$this->staticMethod();
		self::anotherStaticMethod();
	}

	private static function staticMethod()
	{

	}

	private static function anotherStaticMethod()
	{

	}

	private static function unusedStaticMethod()
	{

	}

}

class Bar
{

	private function doFoo()
	{

	}

	private function doBaz()
	{
		$cb = [$this, 'doBaz'];
		$cb();
	}

	public function doBar()
	{
		$cb = [$this, 'doFoo'];
		$cb();
	}

}

class Baz
{

	private function doFoo()
	{

	}

	public function doBar(string $name)
	{
		if ($name === 'doFoo') {
			$cb = [$this, $name];
			$cb();
		}
	}

}

class Lorem
{

	private function doFoo()
	{

	}

	private function doBaz()
	{

	}

	public function doBar()
	{
		$m = 'doFoo';
		$this->{$m}();
	}

}

class Ipsum
{

	private function doFoo()
	{

	}

	public function doBar(string $s)
	{
		$this->{$s}();
	}

}

trait FooTrait
{

	private function doFoo()
	{

	}

	private function doBar()
	{

	}

	public function doBaz()
	{
		$this->doFoo();
		$this->doLorem();
	}

}

class UsingFooTrait
{

	use FooTrait;

	private function doLorem()
	{

	}

}

class StaticMethod
{

	private static function doFoo(): void
	{

	}

	public function doTest(): void
	{
		$this::doFoo();
	}

}

class IgnoredByExtension
{
	private function foo(): void
	{
	}

	private function bar(): void
	{
	}
}
