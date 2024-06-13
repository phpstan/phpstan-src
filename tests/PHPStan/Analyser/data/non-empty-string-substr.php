<?php // onlyif PHP_VERSION_ID >= 80000

namespace NonEmptyStringSubstr;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-string $nonEmpty
	 * @param positive-int $positiveInt
	 * @param 1|2|3 $postiveRange
	 * @param -1|-2|-3 $negativeRange
	 */
	public function doSubstr(string $s, $nonEmpty, $positiveInt, $postiveRange, $negativeRange): void
	{
		assertType('string', substr($s, 5));

		assertType('string', substr($s, -5));
		assertType('non-empty-string', substr($nonEmpty, -5));
		assertType('non-empty-string', substr($nonEmpty, $negativeRange));

		assertType('string', substr($s, 0, 5));
		assertType('non-empty-string', substr($nonEmpty, 0, 5));
		assertType('non-empty-string', substr($nonEmpty, 0, $postiveRange));

		assertType('string', substr($nonEmpty, 0, -5));

		assertType('string', substr($s, 0, $positiveInt));
		assertType('non-empty-string', substr($nonEmpty, 0, $positiveInt));
	}

	/**
	 * @param non-empty-string $nonEmpty
	 * @param positive-int $positiveInt
	 * @param 1|2|3 $postiveRange
	 * @param -1|-2|-3 $negativeRange
	 */
	public function doMbSubstr(string $s, $nonEmpty, $positiveInt, $postiveRange, $negativeRange): void
	{
		assertType('string', mb_substr($s, 5));

		assertType('string', mb_substr($s, -5));
		assertType('non-empty-string', mb_substr($nonEmpty, -5));
		assertType('non-empty-string', mb_substr($nonEmpty, $negativeRange));

		assertType('string', mb_substr($s, 0, 5));
		assertType('non-empty-string', mb_substr($nonEmpty, 0, 5));
		assertType('non-empty-string', mb_substr($nonEmpty, 0, $postiveRange));

		assertType('string', mb_substr($nonEmpty, 0, -5));

		assertType('string', mb_substr($s, 0, $positiveInt));
		assertType('non-empty-string', mb_substr($nonEmpty, 0, $positiveInt));

		assertType('non-empty-string', mb_substr("déjà_vu", 0, $positiveInt));
		assertType("'déjà_vu'", mb_substr("déjà_vu", 0));
		assertType("'déj'", mb_substr("déjà_vu", 0, 3));
	}

}
