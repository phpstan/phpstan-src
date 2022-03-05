<?php

namespace ImpossibleMethodCall;

class Foo
{

	public function doFoo(
		string $foo,
		int $bar
	)
	{
		$assertion = new \PHPStan\Tests\AssertionClass();
		$assertion->assertString($foo);
		$assertion->assertString($bar);
	}

	/**
	 * @param string|int $foo
	 */
	public function doBar($foo)
	{
		$assertion = new \PHPStan\Tests\AssertionClass();
		$assertion->assertString($foo);
	}

	public function doBaz(int $foo)
	{
		$assertion = new \PHPStan\Tests\AssertionClass();
		$assertion->assertNotInt($foo);
	}

	public function doLorem(string $foo)
	{
		$assertion = new \PHPStan\Tests\AssertionClass();
		$assertion->assertNotInt($foo);
	}

	/**
	 * @param string|int $foo
	 */
	public function doIpsum($foo)
	{
		$assertion = new \PHPStan\Tests\AssertionClass();
		$assertion->assertNotInt($foo);
	}

	public function isSame($expected, $actual): bool
	{
		return $expected === $actual;
	}

	public function isNotSame($expected, $actual): bool
	{
		return $expected !== $actual;
	}

	public function doDolor(\stdClass $std1, \stdClass $std2)
	{
		if ($this->isSame(1, 1)) {

		}
		if ($this->isSame(1, 2)) {

		}
		if ($this->isNotSame(1, 1)) {

		}
		if ($this->isNotSame(1, 2)) {

		}
		if ($this->isSame(new \stdClass(), new \stdClass())) {

		}
		if ($this->isNotSame(new \stdClass(), new \stdClass())) {

		}
		if ($this->isSame($std1, $std1)) {

		}
		if ($this->isNotSame($std1, $std1)) {

		}
		if ($this->isSame($std1, $std2)) {

		}
		if ($this->isNotSame($std1, $std2)) {

		}
		if ($this->isSame($this->nullableInt(), 1)) {
			if ($this->isSame($this->nullableInt(), null)) {

			}
		}
		if ($this->isSame(self::createStdClass('a'), self::createStdClass('a'))) {

		}
		if ($this->isNotSame(self::createStdClass('b'), self::createStdClass('b'))) {

		}
		if ($this->isSame(self::returnFoo('a'), self::returnFoo('a'))) {

		}
		if ($this->isNotSame(self::returnFoo('b'), self::returnFoo('b'))) {

		}
	}

	public function nullableInt(): ?int
	{

	}

	public static function createStdClass(string $foo): \stdClass
	{
		return new \stdClass();
	}

	/**
	 * @return 'foo'
	 */
	public static function returnFoo(string $foo): string
	{
		return 'foo';
	}

}
