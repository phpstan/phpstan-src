<?php

namespace TypeSpecifierEqual;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $s): void
	{
		assertType("string", $s);
		if ($s == 'one') {
			assertType("'one'", $s);
		} else {
			assertType("string", $s);
		}
		assertType("string", $s);
	}

	/** @param 'one'|'two' $s */
	public function doBar(string $s): void
	{
		assertType("'one'|'two'", $s);
		if ($s == 'one') {
			assertType("'one'", $s);
		} else {
			assertType("'two'", $s);
		}
		assertType("'one'|'two'", $s);
	}

	/** @param int<1, 3>|int<8, 13> $i */
	public function doBaz(int $i): void
	{
		assertType('int<1, 3>|int<8, 13>', $i);
		if ($i == 3) {
			assertType('3', $i);
		} else {
			assertType('int<1, 2>|int<8, 13>', $i);
		}
		assertType('int<1, 3>|int<8, 13>', $i);
	}

	public function doLorem(float $f): void
	{
		assertType('float', $f);
		if ($f == 3.5) {
			assertType('3.5', $f);
		} else {
			assertType('float', $f);
		}

		assertType('float', $f);
	}

	public function doIpsum(array $a): void
	{
		assertType('array', $a);
		if ($a == []) {
			assertType('array{}', $a);
		} else {
			assertType('non-empty-array', $a);
		}
		assertType('array', $a);
	}

	public function stdClass(\stdClass $a, \stdClass $b): void
	{
		if ($a == $a) {
			assertType('stdClass', $a);
		} else {
			assertType('*NEVER*', $a);
		}

		if ($b != $b) {
			assertType('*NEVER*', $b);
		} else {
			assertType('stdClass', $b);
		}

		if ($a == $b) {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		} else {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		}

		if ($a != $b) {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		} else {
			assertType('stdClass', $a);
			assertType('stdClass', $b);
		}

		assertType('stdClass', $a);
		assertType('stdClass', $b);
	}

	/**
	 * @param array{a: string, b: array{c: string|null}} $a
	 */
	public function arrayOffset(array $a): void
	{
		if (strlen($a['a']) > 0 && $a['a'] === $a['b']['c']) {
			assertType('array{a: non-empty-string, b: array{c: non-empty-string}}', $a);
		}
	}

}

class Bar
{

	public function doFoo(\stdClass $a, \stdClass $b): void
	{
		assertType('true', $a == $a);
		assertType('bool', $a == $b);
		assertType('false', $a != $a);
		assertType('bool', $a != $b);

		assertType('bool', self::createStdClass() == self::createStdClass());
		assertType('bool', self::createStdClass() != self::createStdClass());
	}

	public static function createStdClass(): \stdClass
	{

	}

}

class Baz
{

	public function doFoo(string $a, int $b, float $c): void
	{
		$nullableA = $a;
		if (rand(0, 1)) {
			$nullableA = null;
		}

		assertType('bool', $a == $nullableA);
		assertType('bool', $a == 'a');
		assertType('true', 'a' == 'a');
		assertType('false', 'a' == 'b');

		assertType('bool', $a != $nullableA);
		assertType('bool', $a != 'a');
		assertType('false', 'a' != 'a');
		assertType('true', 'a' != 'b');

		assertType('false', $b == 'a');
		assertType('bool', $a == 1);
		assertType('true', 1 == 1);
		assertType('false', 1 == 0);

		assertType('false', $c == 'a');
		assertType('bool', $c == 1);
		assertType('bool', $c == 1.2);
		assertType('true', 1.2 == 1.2);
		assertType('false', 1.2 == 1.3);
	}

}
