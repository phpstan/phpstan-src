<?php declare(strict_types=1);

namespace FilterVar;

use stdClass;
use function PHPStan\Testing\assertType;

class FilterVar
{

	/**
	 * @param array<string, mixed> $stringMixedMap
	 */
	public function doFoo($mixed, array $stringMixedMap): void
	{
		assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT));
		assertType('int|null', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]));

		assertType('17', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_SCALAR]));
		assertType('false', filter_var([17], FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_SCALAR]));

		assertType('false', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]));
		assertType('null', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('false', filter_var('foo', FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]));
		assertType('null', filter_var('foo', FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('array<int>|false', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]));
		assertType('array<int>|null', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('array<string, int>|false', filter_var($stringMixedMap, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]));
		assertType('array<string, int>|null', filter_var($stringMixedMap, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]));

		assertType('array<17>', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<17>', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('array<false>', filter_var('foo', FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<null>', filter_var('foo', FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
	 	assertType('array<int|false>', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<int|null>', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('array<string, int|false>', filter_var($stringMixedMap, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<string, int|null>', filter_var($stringMixedMap, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));

		assertType('false', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY]));
		assertType('null', filter_var(17, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('false', filter_var('foo', FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY]));
		assertType('null', filter_var('foo', FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('array<int>|false', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY]));
		assertType('array<int>|null', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('array<string, int>|false', filter_var($stringMixedMap, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY]));
		assertType('array<string, int>|null', filter_var($stringMixedMap, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));

		assertType('0|int<17, 19>', filter_var($mixed, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 17, 'max_range' => 19]]));

		assertType('array<false>', filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_FORCE_ARRAY | FILTER_NULL_ON_FAILURE));
	}

	/**
	 * @param int<17, 19> $range1
	 * @param int<1, 5> $range2
	 * @param int<18, 19> $range3
	 */
	public function intRanges(int $int, int $min, int $max, int $range1, int $range2, int $range3): void
	{
		assertType('int<17, 19>|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => 19]]));
		assertType('false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => 19, 'max_range' => 17]]));
		assertType('0|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => null, 'max_range' => null]]));
		assertType('int<17, 19>|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => '17', 'max_range' => '19']]));
		assertType('int<min, 19>|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => 19]]));
		assertType('int<17, max>|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => $max]]));
		assertType('int<17, 19>', filter_var($range1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => 19]]));
		assertType('false', filter_var(9, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => 19]]));
		assertType('18', filter_var(18, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => 19]]));
		assertType('18', filter_var(18, FILTER_VALIDATE_INT, ['options' => ['min_range' => '17', 'max_range' => '19']]));
		assertType('false', filter_var(-18, FILTER_VALIDATE_INT, ['options' => ['min_range' => null, 'max_range' => 19]]));
		assertType('false', filter_var(18, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => null]]));
		assertType('false', filter_var($range2, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => 19]]));
		assertType('int<18, 19>', filter_var($range3, FILTER_VALIDATE_INT, ['options' => ['min_range' => 17, 'max_range' => 19]]));
		assertType('int|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]));
		assertType('int|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min]]));
		assertType('int|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['max_range' => $max]]));
	}

	/** @param resource $resource */
	public function invalidInput(array $arr, object $object, $resource): void
	{
		assertType('false', filter_var($arr));
		assertType('false', filter_var($object));
		assertType('false', filter_var($resource));
		assertType('null', filter_var(new stdClass(), FILTER_DEFAULT, FILTER_NULL_ON_FAILURE));
		assertType("'invalid'", filter_var(new stdClass(), FILTER_DEFAULT, ['options' => ['default' => 'invalid']]));
	}

	public function intToInt(int $int, array $options): void
	{
		assertType('int', filter_var($int, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($int, FILTER_VALIDATE_INT, $options));
		assertType('int<0, max>|false', filter_var($int, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]));
	}

	/**
	 * @param int<0, 9> $intRange
	 * @param non-empty-string $nonEmptyString
	 */
	public function scalars(bool $bool, float $float, int $int, string $string, int $intRange, string $nonEmptyString): void
	{
		assertType('bool', filter_var($bool, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('true', filter_var(true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('false', filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var($float, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var(17.0, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('bool|null', filter_var(17.1, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('bool|null', filter_var($int, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var($intRange, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var(17, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('bool|null', filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var($nonEmptyString, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var('17', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('bool|null', filter_var('17.1', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('null', filter_var(null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));

		assertType('float|false', filter_var($bool, FILTER_VALIDATE_FLOAT));
		assertType('1.0', filter_var(true, FILTER_VALIDATE_FLOAT));
		assertType('false', filter_var(false, FILTER_VALIDATE_FLOAT));
		assertType('float', filter_var($float, FILTER_VALIDATE_FLOAT));
		assertType('17.0', filter_var(17.0, FILTER_VALIDATE_FLOAT));
		assertType('17.1', filter_var(17.1, FILTER_VALIDATE_FLOAT));
		assertType('float', filter_var($int, FILTER_VALIDATE_FLOAT));
		assertType('float', filter_var($intRange, FILTER_VALIDATE_FLOAT));
		assertType('17.0', filter_var(17, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var($string, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var($nonEmptyString, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var('17', FILTER_VALIDATE_FLOAT)); // could be 17.0
		assertType('float|false', filter_var('17.1', FILTER_VALIDATE_FLOAT)); // could be 17.1
		assertType('false', filter_var(null, FILTER_VALIDATE_FLOAT));

		assertType('int|false', filter_var($bool, FILTER_VALIDATE_INT));
		assertType('1', filter_var(true, FILTER_VALIDATE_INT));
		assertType('false', filter_var(false, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($float, FILTER_VALIDATE_INT));
		assertType('17', filter_var(17.0, FILTER_VALIDATE_INT));
		assertType('false', filter_var(17.1, FILTER_VALIDATE_INT));
		assertType('int', filter_var($int, FILTER_VALIDATE_INT));
		assertType('int<0, 9>', filter_var($intRange, FILTER_VALIDATE_INT));
		assertType('17', filter_var(17, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($string, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($nonEmptyString, FILTER_VALIDATE_INT));
		assertType('17', filter_var('17', FILTER_VALIDATE_INT));
		assertType('false', filter_var('17.1', FILTER_VALIDATE_INT));
		assertType('false', filter_var(null, FILTER_VALIDATE_INT));

		assertType("''|'1'", filter_var($bool));
		assertType("'1'", filter_var(true));
		assertType("''", filter_var(false));
		assertType('numeric-string', filter_var($float));
		assertType("'17'", filter_var(17.0));
		assertType("'17.1'", filter_var(17.1));
		assertType('numeric-string', filter_var($int));
		assertType('numeric-string', filter_var($intRange));
		assertType("'17'", filter_var(17));
		assertType('string', filter_var($string));
		assertType('non-empty-string', filter_var($nonEmptyString));
		assertType("'17'", filter_var('17'));
		assertType("'17.1'", filter_var('17.1'));
		assertType("''", filter_var(null));
	}

}
