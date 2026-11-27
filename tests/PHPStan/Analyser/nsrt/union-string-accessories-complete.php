<?php declare(strict_types = 1);

namespace UnionStringAccessoriesComplete;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param lowercase-string $lowercase
	 * @param numeric-string $numeric
	 * @param non-empty-literal-string $nonEmptyLiteral
	 * @param literal-string&lowercase-string $lowercaseLiteral
	 * @param non-empty-string|lowercase-string $nonEmptyOrLowercase
	 * @param numeric-string|non-falsy-string|lowercase-string $numericOrNonFalsyOrLowercase
	 * @param callable-string|non-empty-string|lowercase-string $callableOrNonEmptyOrLowercase
	 * @param array{a: string}|array<int>|iterable<int> $shapeOrArrayOrIterable
	 */
	public function doFoo(
		string $nonEmpty,
		string $nonFalsy,
		string $lowercase,
		string $numeric,
		string $nonEmptyLiteral,
		string $lowercaseLiteral,
		string $nonEmptyOrLowercase,
		string $numericOrNonFalsyOrLowercase,
		string $callableOrNonEmptyOrLowercase,
		iterable $shapeOrArrayOrIterable,
		bool $b
	): void
	{
		// lowercase-string holds the '' non-empty-string lacks
		assertType('string', $b ? $nonEmpty : $lowercase);
		assertType('string', $b ? $nonFalsy : $lowercase);
		assertType('literal-string', $b ? $nonEmptyLiteral : $lowercaseLiteral);
		// numeric-string holds the '0' non-falsy-string lacks
		assertType('non-empty-string', $b ? $nonFalsy : $numeric);
		// numeric-string lacks ''
		assertType('non-empty-string', $b ? $nonEmpty : $numeric);

		assertType('string', $nonEmptyOrLowercase);
		// string from non-falsy-string | lowercase-string also holds numeric-string
		assertType('string', $numericOrNonFalsyOrLowercase);
		// string from non-empty-string | lowercase-string, compared again with callable-string before it
		assertType('string', $callableOrNonEmptyOrLowercase);

		assertType('array{a: string}|iterable<int>', $shapeOrArrayOrIterable);
	}

}
