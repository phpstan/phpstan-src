<?php declare(strict_types = 1);

namespace TraitGeneralizedConsts;

use function PHPStan\Testing\assertType;

trait MyLogic
{
	public function fetchValue() : string
	{
		assertType('mixed', self::MY_CONST);
		assertType('mixed', static::MY_CONST);

		if (self::MY_CONST === 'hallo') {
			assertType("'hallo'", self::MY_CONST);
			return 'foo';
		}
		assertType("mixed~'hallo'", self::MY_CONST);
		if (self::MY_CONST === 1) {
			assertType('1', self::MY_CONST);
			return 'foo1';
		}
		assertType("mixed~1|'hallo'", self::MY_CONST);

		assertType('class-string&literal-string', self::class);
		assertType('class-string&literal-string', static::class);

		assertType('1', FirstConsumer::MY_CONST);
	}

	public function fetchValue2() : string {
		assertType('mixed', self::MY_CONST_ARRAY);
		if (array_key_exists('valueToFetch', self::MY_CONST_ARRAY)) {
			assertType("array&hasOffset('valueToFetch')", self::MY_CONST_ARRAY);
			return self::MY_CONST_ARRAY['valueToFetch'];
		}
		assertType("mixed~hasOffset('valueToFetch')", self::MY_CONST_ARRAY);

		assertType("array{someValue: 'abc', valueToFetch: '123'}", FirstConsumer::MY_CONST_ARRAY);

		return 'defaultValue';
	}
}

final class FirstConsumer
{
	use MyLogic;

	private const MY_CONST = 1;

	private const MY_CONST_ARRAY = [
		'someValue'    => 'abc',
		'valueToFetch' => '123',
	];

	public function fetchOwnValue() : string
	{
		assertType('1', self::MY_CONST);
		if (self::MY_CONST === 'hallo') {
			assertType('*NEVER*', self::MY_CONST);
			return 'foo';
		}
		assertType('1', self::MY_CONST);
		if (self::MY_CONST === 1) {
			assertType('1', self::MY_CONST);
			return 'foo1';
		}
		assertType('*NEVER*', self::MY_CONST);
	}

	public function fetchOwnValue2() : string {
		assertType("array{someValue: 'abc', valueToFetch: '123'}", self::MY_CONST_ARRAY);
		if (array_key_exists('valueToFetch', self::MY_CONST_ARRAY)) {
			assertType("array{someValue: 'abc', valueToFetch: '123'}", self::MY_CONST_ARRAY);
			return self::MY_CONST_ARRAY['valueToFetch'];
		}
		assertType("array{someValue: 'abc', valueToFetch: '123'}", self::MY_CONST_ARRAY);

		return 'defaultValue';
	}
}
