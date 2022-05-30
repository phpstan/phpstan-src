<?php

namespace NonEmptyStringStrrchr;

use function PHPStan\Testing\assertType;

class Foo {
	public function nonEmptyStrrchr(string $s, string $needle): void
	{
		if (strrchr($s, 'abc') === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strrchr($s, 'abc')) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		if (strrchr($s, $needle) === 'hallo') {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);
		if ('hallo' === strrchr($s, $needle)) {
			assertType('non-empty-string', $s);
		}
		assertType('string', $s);

		$x = (strrchr($s, $needle) === 'hallo');
		assertType('string', $s);
		var_dump($x);

		$x = (strrchr($s, $needle) !== 'hallo');
		assertType('string', $s);
		var_dump($x);
	}
}
