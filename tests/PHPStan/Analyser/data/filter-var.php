<?php declare(strict_types=1);

namespace FilterVar;

use stdClass;
use function PHPStan\Testing\assertType;

class FilterVar
{

	public function doFoo($mixed): void
	{
		assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT));
		assertType('int|null', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]));
		assertType('array<int|false>', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<int|null>', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('0|int<17, 19>', filter_var($mixed, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 17, 'max_range' => 19]]));

		assertType('array<false>', filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_FORCE_ARRAY | FILTER_NULL_ON_FAILURE));
	}

	/** @param resource $resource */
	public function invalidInput(array $arr, object $object, $resource): void
	{
		assertType('false', filter_var($arr));
		assertType('false', filter_var($object));
		assertType('false', filter_var($resource));
		assertType('null', filter_var(new stdClass(), FILTER_DEFAULT, FILTER_NULL_ON_FAILURE));
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
		assertType('bool|null', filter_var($int, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var($intRange, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var(17, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('bool|null', filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var($nonEmptyString, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
		assertType('bool|null', filter_var('17', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null
		assertType('bool|null', filter_var(null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)); // could be null

		assertType('float|false', filter_var($bool, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var(true, FILTER_VALIDATE_FLOAT)); // could be 1
		assertType('float|false', filter_var(false, FILTER_VALIDATE_FLOAT)); // could be false
		assertType('float', filter_var($float, FILTER_VALIDATE_FLOAT));
		assertType('17.0', filter_var(17.0, FILTER_VALIDATE_FLOAT));
		assertType('float', filter_var($int, FILTER_VALIDATE_FLOAT));
		assertType('float', filter_var($intRange, FILTER_VALIDATE_FLOAT));
		assertType('17.0', filter_var(17, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var($string, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var($nonEmptyString, FILTER_VALIDATE_FLOAT));
		assertType('float|false', filter_var('17', FILTER_VALIDATE_FLOAT)); // could be 17.0
		assertType('float|false', filter_var(null, FILTER_VALIDATE_FLOAT)); // could be false

		assertType('int|false', filter_var($bool, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var(true, FILTER_VALIDATE_INT)); // could be 1
		assertType('int|false', filter_var(false, FILTER_VALIDATE_INT)); // could be false
		assertType('int', filter_var($float, FILTER_VALIDATE_INT));
		assertType('17', filter_var(17.0, FILTER_VALIDATE_INT));
		assertType('int', filter_var($int, FILTER_VALIDATE_INT));
		assertType('int<0, 9>', filter_var($intRange, FILTER_VALIDATE_INT));
		assertType('17', filter_var(17, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($string, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($nonEmptyString, FILTER_VALIDATE_INT));
		assertType('17', filter_var('17', FILTER_VALIDATE_INT));
		assertType('int|false', filter_var(null, FILTER_VALIDATE_INT)); // could be false

		assertType("''|'1'", filter_var($bool));
		assertType("'1'", filter_var(true));
		assertType("''", filter_var(false));
		assertType('numeric-string', filter_var($float));
		assertType("'17'", filter_var(17.0));
		assertType('numeric-string', filter_var($int));
		assertType('numeric-string', filter_var($intRange));
		assertType("'17'", filter_var(17));
		assertType('string', filter_var($string));
		assertType('non-empty-string', filter_var($nonEmptyString));
		assertType("'17'", filter_var('17'));
		assertType("''", filter_var(null));
	}

}
