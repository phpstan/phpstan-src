<?php

namespace UnionStringAccessoriesStructural;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-literal-string $nonEmptyLiteral
	 * @param literal-string&non-falsy-string&lowercase-string $nonFalsyLowercaseLiteral
	 * @param non-empty-string&numeric-string $nonEmptyNumeric
	 * @param non-empty-lowercase-string $nonEmptyLowercase
	 */
	public function emptyString(
		string $nonEmptyLiteral,
		string $nonFalsyLowercaseLiteral,
		string $nonEmptyNumeric,
		string $nonEmptyLowercase,
		bool $b
	): void
	{
		assertType('literal-string', $b ? '' : $nonEmptyLiteral);
		assertType('literal-string', $b ? $nonEmptyLiteral : '');
		// literal-string&lowercase-string would add '0', which neither member holds
		assertType("''|(literal-string&lowercase-string&non-falsy-string)", $b ? '' : $nonFalsyLowercaseLiteral);
		assertType("''|numeric-string", $b ? '' : $nonEmptyNumeric);
		assertType('lowercase-string', $b ? '' : $nonEmptyLowercase);
	}

	/**
	 * @param __benevolent<int|lowercase-string> $benevolent
	 * @param uppercase-string $uppercase
	 * @param non-empty-string $nonEmpty
	 * @param numeric-string $numeric
	 */
	public function benevolent($benevolent, string $uppercase, string $nonEmpty, string $numeric, bool $b): void
	{
		assertType('int|lowercase-string|uppercase-string', $b ? $benevolent : $uppercase);
		assertType('int|lowercase-string|numeric-string', $b ? $benevolent : $numeric);
		// string absorbs the benevolent lowercase-string
		assertType('(int|string)', $b ? $benevolent : $nonEmpty);
		assertType('(int|lowercase-string)', $b ? $benevolent : 'abc');
	}

}
