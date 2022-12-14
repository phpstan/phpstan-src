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
		if ($this->isSame(self::createStdClass('a')->foo, self::createStdClass('a')->foo)) {

		}
		if ($this->isNotSame(self::createStdClass('b')->foo, self::createStdClass('b')->foo)) {

		}
		if ($this->isSame([], [])) {

		}
		if ($this->isNotSame([], [])) {

		}
		if ($this->isSame([1, 3], [1, 3])) {

		}
		if ($this->isNotSame([1, 3], [1, 3])) {

		}
		$std3 = new \stdClass();
		if ($this->isSame(1, $std3)) {

		}
		$std4 = new \stdClass();
		if ($this->isNotSame(1, $std4)) {

		}
		if ($this->isSame('1', new \stdClass())) {

		}
		if ($this->isNotSame('1', new \stdClass())) {

		}
		if ($this->isSame(['a', 'b'], [1, 2])) {

		}
		if ($this->isNotSame(['a', 'b'], [1, 2])) {

		}
		if ($this->isSame(new \stdClass(), '1')) {

		}
		if ($this->isNotSame(new \stdClass(), '1')) {

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

	public function nonEmptyString()
	{
		$s = '';
		$this->isSame($s, '');
		$this->isNotSame($s, '');
	}

	public function stdClass(\stdClass $a)
	{
		$this->isSame($a, new \stdClass());
	}

	public function stdClass2(\stdClass $a)
	{
		$this->isNotSame($a, new \stdClass());
	}

	public function scalars()
	{
		$i = 1;
		$this->isSame($i, 1);

		$j = 2;
		$this->isNotSame($j, 2);
	}

}

class ConditionalAlwaysTrue
{
	public function sayHello(?int $date): void
	{
		if ($date === null) {
		} elseif ($this->isInt($date)) { // always-true should not be reported because last condition
		}

		if ($date === null) {
		} elseif ($this->isInt($date)) { // always-true should be reported, because another condition below
		} elseif (rand(0,1)) {
		}
	}

	/**
	 * @phpstan-assert-if-true int $value
	 */
	public function isInt($value): bool {
	}
}
