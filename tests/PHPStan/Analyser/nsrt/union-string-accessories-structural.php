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
		assertType('literal-string&lowercase-string', $b ? '' : $nonFalsyLowercaseLiteral);
		assertType("''|numeric-string", $b ? '' : $nonEmptyNumeric);
		assertType('lowercase-string', $b ? '' : $nonEmptyLowercase);
	}

	/**
	 * @param __benevolent<int|lowercase-string> $benevolent
	 * @param uppercase-string $uppercase
	 * @param non-empty-string $nonEmpty
	 */
	public function benevolent($benevolent, string $uppercase, string $nonEmpty, bool $b): void
	{
		assertType('int|lowercase-string|uppercase-string', $b ? $benevolent : $uppercase);
		assertType('int|lowercase-string|non-empty-string', $b ? $benevolent : $nonEmpty);
		assertType('(int|lowercase-string)', $b ? $benevolent : 'abc');
	}

}
