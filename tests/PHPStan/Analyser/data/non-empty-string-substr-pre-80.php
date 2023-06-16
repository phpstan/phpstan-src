<?php

namespace NonEmptyStringSubstr;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-string $nonEmpty
	 * @param positive-int $positiveInt
	 * @param 1|2|3 $positiveRange
	 * @param -1|-2|-3 $negativeRange
	 */
	public function doSubstr(string $s, $nonEmpty, $positiveInt, $positiveRange, $negativeRange): void
	{
		assertType('(string|false)', substr($s, 5));

		assertType('(string|false)', substr($s, -5));
		assertType('non-empty-string', substr($nonEmpty, -5));
		assertType('non-empty-string', substr($nonEmpty, $negativeRange));

		assertType('(string|false)', substr($s, 0, 5));
		assertType('non-empty-string', substr($nonEmpty, 0, 5));
		assertType('non-empty-string', substr($nonEmpty, 0, $positiveRange));

		assertType('(string|false)', substr($nonEmpty, 0, -5));

		assertType('(string|false)', substr($s, 0, $positiveInt));
		assertType('non-empty-string', substr($nonEmpty, 0, $positiveInt));
	}

}
