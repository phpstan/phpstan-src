<?php // lint >= 8.3

namespace Bug10022;

use function PHPStan\Testing\assertType;

// https://github.com/phpstan/phpstan/issues/10022
assertType('array{\'1\', \'2\', \'3\', \'4\', \'5\', \'6\', \'7\', \'8\', \'9\', \':\', \';\', \'<\', \'=\', \'>\', \'?\', \'@\', \'A\', \'B\', \'C\', \'D\', \'E\', \'F\', \'G\', \'H\', \'I\', \'J\', \'K\', \'L\', \'M\', \'N\', \'O\', \'P\', \'Q\', \'R\', \'S\', \'T\', \'U\', \'V\', \'W\', \'X\', \'Y\', \'Z\', \'[\', \'\\\\\', \']\', \'^\', \'_\', \'`\', \'a\'}', range('1', 'a'));

function doFoo(bool $flag): void
{
	if (PHP_VERSION_ID >= 80300) {
		// a negative step on an increasing range and a non-finite step throw a ValueError since PHP 8.3
		assertType('*NEVER*', range(2, 5, -1));
		assertType('*NEVER*', range('a', 'z', -1));
		assertType('*NEVER*', range('a', 'z', 0));
		assertType('*NEVER*', range(1, 10, NAN));

		// an integral float step produces ints since PHP 8.3
		assertType('non-empty-list<int<1, 200>>', range(1, 200, 1.0));
		assertType('non-empty-list<literal-string&non-empty-string>', range('A', 'z', 1.0));
	} elseif (PHP_VERSION_ID >= 80000) {
		// the sign of the step used to be ignored
		assertType('non-empty-list<int>', range(2, 5, -1));
		assertType('non-empty-list<string>', range('a', 'z', -1));

		// a float argument used to produce floats even without a fractional part
		assertType('non-empty-list<float>', range(1, 200, 1.0));
		assertType('non-empty-list<float>', range('A', 'z', 1.0));
	} else {
		// PHP 7 returns false for an invalid step, next to what the other combinations return
		assertType('non-empty-list<int>|false', range(2, 5, $flag ? -1 : 0));
		assertType('non-empty-list<float|int>|false', range(2, 5, $flag ? 0 : true));
	}

	// every combination of the constant arguments contributes to the type
	assertType('non-empty-list<0|1|(literal-string&non-empty-string)>', range($flag ? 'A' : 1, 'z'));
	assertType('non-empty-list<float|int<1, 100>>', range($flag ? 1.0 : 1, 100));
	assertType('non-empty-list<float|int<1, 100>>', range(1, 100, $flag ? 0.5 : 1));

	// ranges longer than the threshold are generalized from the returned values
	assertType('non-empty-list<float>', range(1.0, 100.0));
	assertType('non-empty-list<literal-string&non-empty-string>', range('A', 'z'));

	// a step that is neither an int nor a float is not folded at all, so nothing
	// can be said about the combination it belongs to
	assertType('non-empty-list<float|int>', range(2, 5, $flag ? 0 : true));
}
