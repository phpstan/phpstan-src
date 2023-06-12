<?php

namespace NonEmptyStringSubstr;

use function PHPStan\Testing\assertType;

class FooSpecifying {
	public function nonEmptySubstr(string $s, int $offset, int $length): void
	{
		if (substr($s, 10) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === substr($s, 10)) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (substr($s, -10) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === substr($s, -10)) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (substr($s, 10, 5) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (substr($s, 10, -5) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (substr($s, $offset) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (substr($s, $offset, $length) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (substr($s, $offset, $length) !== 'hallo') {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (substr($s, $offset, $length) === '') {
			assertType('string', $s);
		}
		assertType('string', $s);
		if ('' === substr($s, $offset, $length)) {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (substr($s, $offset, $length) == '') {
			assertType('string', $s);
		}
		assertType('string', $s);
		if ('' == substr($s, $offset, $length)) {
			assertType('string', $s);
		}
		assertType('string', $s);

		$x = (substr($s, 10) === 'hallo');
		assertType('string', $s);
		var_dump($x);

		$x = (substr($s, 10) !== 'hallo');
		assertType('string', $s);
		var_dump($x);

		$x = 'hallo';
		if (substr($x, 0, PHP_INT_MAX) !== 'foo') {
			assertType('\'hallo\'', $x);
		}
	}
}
