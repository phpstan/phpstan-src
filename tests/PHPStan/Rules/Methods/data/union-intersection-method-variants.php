<?php declare(strict_types = 1);

namespace UnionIntersectionMethodVariants;

interface AcceptsInt {
	public function process(int $x): string;
}

interface AcceptsString {
	public function process(string $x): string;
}

interface AcceptsNullableString {
	public function process(?string $x): string;
}

interface TwoParams {
	public function transform(int $x, string $y): void;
}

interface OneParam {
	public function transform(int $x): void;
}

class IntersectionTests
{
	/**
	 * Intersection type: object IS both AcceptsInt and AcceptsString.
	 * Implementation must handle both int and string params.
	 * So calling with int OR string should both be accepted.
	 *
	 * @param AcceptsInt&AcceptsString $obj
	 */
	public function testIntersectionParamTypes($obj): void
	{
		$obj->process(42);       // OK - int satisfies AcceptsInt
		$obj->process('hello');  // OK - string satisfies AcceptsString
		$obj->process(true);     // ERROR - bool doesn't satisfy either
	}

	/**
	 * Intersection with different param counts.
	 * TwoParams needs (int, string), OneParam needs (int).
	 * Implementation must handle both, so it has (int $x, string $y = optional).
	 *
	 * @param TwoParams&OneParam $obj
	 */
	public function testIntersectionParamCount($obj): void
	{
		$obj->transform(42, 'hello'); // OK - satisfies TwoParams
		$obj->transform(42);          // OK - satisfies OneParam
		$obj->transform();            // ERROR - both require at least 1 param
	}
}

class UnionTests
{
	/**
	 * Union with overlapping types: string vs ?string.
	 * Intersected param type: string & (string|null) = string.
	 * This is the phpstan/phpstan#9664 scenario.
	 *
	 * @param AcceptsString|AcceptsNullableString $obj
	 */
	public function testUnionOverlappingParams($obj): void
	{
		$obj->process('hello');  // OK - string accepted by both
		$obj->process(null);     // ERROR - null not accepted by AcceptsString
		$obj->process(42);       // ERROR - int not accepted by either
	}

	/**
	 * Union with completely disjoint types: int vs string.
	 * Intersected param type: int & string = never.
	 * NeverType::accepts() returns yes (bottom type semantics:
	 * unreachable code, so no parameter errors are reported).
	 *
	 * @param AcceptsInt|AcceptsString $obj
	 */
	public function testUnionDisjointParams($obj): void
	{
		$obj->process(42);       // no error (never accepts everything)
		$obj->process('hello');  // no error (never accepts everything)
		$obj->process(true);     // no error (never accepts everything)
	}

	/**
	 * Union with different param counts.
	 *
	 * @param TwoParams|OneParam $obj
	 */
	public function testUnionParamCount($obj): void
	{
		$obj->transform(42, 'hello'); // OK
		$obj->transform(42);          // OK - OneParam only needs 1
		$obj->transform();            // ERROR - both need at least 1
	}
}
