<?php

namespace Bug3981;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param string $s
	 * @param non-empty-string $nonEmptyString
	 */
	public function doFoo(string $s, string $nonEmptyString): void
	{
		assertType('non-empty-string|false', strtok($s, ' '));
		assertType('non-empty-string', strtok($nonEmptyString, ' '));
		assertType('false', strtok('', ' '));

		assertType('non-empty-string', $nonEmptyString[0]);
		assertType('non-empty-string', $nonEmptyString[1]);
		assertType('non-empty-string', $s[0]);

		assertType('string', $s);

		$s[0] = '1';
		assertType('non-empty-string', $s);
	}

	/**
	 * @param literal-string $literalString
	 */
	public function doBar(string $literalString, string $generalString): void
	{
		$literalString[0] = 'a';
		assertType('literal-string&non-empty-string', $literalString);

		$literalString[1] = $generalString;
		assertType('non-empty-string', $literalString);
	}

	/**
	 * @param non-falsy-string $nonFalsyString
	 */
	public function doBaz(string $nonFalsyString, string $generalString): void
	{
		$nonFalsyString[0] = 'a';
		assertType('non-falsy-string', $nonFalsyString);

		$nonFalsyString[1] = $generalString;
		assertType('non-empty-string', $nonFalsyString);
	}

}
