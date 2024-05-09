<?php

namespace NonEmptyStringStrstr;

use function PHPStan\Testing\assertType;

class Foo {
	public function nonEmptyStrstr(string $s, string $needle, bool $before_needle): void
	{
		if (strstr($s, 'abc') === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strstr($s, 'abc')) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strstr($s, $needle)) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, true) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, false) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);

		if (strstr($s, $needle, $before_needle) === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if (mb_strstr($s, $needle, $before_needle) === 'hallo') {
			assertType('non-falsy-string', $s);
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

	public function nonEmptyStristr(string $s, string $needle, bool $before_needle): void
	{
		if (stristr($s, 'abc') === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === stristr($s, 'abc')) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === mb_stristr($s, 'abc')) {
			assertType('non-falsy-string', $s);
		}

		if (stristr($s, $needle, $before_needle) == '') {
			assertType('string', $s);
		}
		assertType('string', $s);
		if ('' == stristr($s, $needle, $before_needle)) {
			assertType('string', $s);
		}
		assertType('string', $s);

		$x = (stristr($s, $needle) === 'hallo');
		assertType('string', $s);
		var_dump($x);
	}


	// strchr() is an alias of strstr()
	public function nonEmptyStrchr(string $s, string $needle, bool $before_needle): void
	{
		if (strchr($s, 'abc') === 'hallo') {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strchr($s, 'abc')) {
			assertType('non-falsy-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === mb_strchr($s, 'abc')) {
			assertType('non-falsy-string', $s);
		}

		if (strchr($s, $needle, $before_needle) == '') {
			assertType('string', $s);
		}
		assertType('string', $s);
		if ('' == strchr($s, $needle, $before_needle)) {
			assertType('string', $s);
		}
		assertType('string', $s);

		$x = (strchr($s, $needle) === 'hallo');
		assertType('string', $s);
		var_dump($x);
	}
}
