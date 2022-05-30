<?php

namespace NonEmptyStringStrstr;

use function PHPStan\Testing\assertType;

class Foo {
	public function nonEmptyStrstr(string $s, string $needle, bool $before_needle): void
	{
		if (strstr($s, 'abc') === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strstr($s, 'abc')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle) === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strstr($s, -10)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, true) === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, false) === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, $before_needle) === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, $before_needle) !== 'hallo') {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, $before_needle) === '') {
			assertType('string', $s);
		}
		assertType('string', $s);
		if ('' === strstr($s, $needle, $before_needle)) {
			assertType('string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, $before_needle) == '') {
			assertType('string', $s);
		}
		assertType('string', $s);
		if ('' == strstr($s, $needle, $before_needle)) {
			assertType('string', $s);
		}
		assertType('string', $s);

		$x = (strstr($s, $needle) === 'hallo');
		assertType('string', $s);
		var_dump($x);

		$x = (strstr($s, $needle) !== 'hallo');
		assertType('string', $s);
		var_dump($x);
	}
}
